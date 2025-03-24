<?php

namespace App\Listeners;

use App\Events\NewsProcessedEvent;
use App\Mail\NewsNotification;
use App\Models\News;
use App\Models\NewsUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNewsEmailListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\NewsProcessedEvent  $event
     * @return void
     */
    public function handle(NewsProcessedEvent $event)
    {
        try {
            $news = $event->news;
            
            if (is_string($news)) {
                Log::info('SendNewsEmailListener: Loading news from ID', ['news_id' => $news]);
                $news = News::find($news);
                
                if (!$news) {
                    Log::error('SendNewsEmailListener: News not found', ['news_id' => $event->news]);
                    return false;
                }
            }
            
            Log::info('SendNewsEmailListener: Processing news', [
                'news_id' => $news->id,
                'title' => $news->title
            ]);

            User::whereDoesntHave('news', function($query) use ($news) {
                $query->where('news_users.news_id', $news->id);
            })
            ->chunk(200, function ($users) use ($news) {
                Log::info('SendNewsEmailListener: Found users chunk', [
                    'user_count' => count($users)
                ]);
                
                if (count($users) > 0) {
                    $this->batchSend($users, $news);
                } else {
                    Log::info('SendNewsEmailListener: No users to process in this chunk');
                }
            });

            Log::info('SendNewsEmailListener: Completed processing');
            return true;
        } catch (\Exception $e) {
            Log::error('SendNewsEmailListener: Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function batchSend($users, $news)
    {
        try {
            $now = now();
            $emailsSent = 0;
            
            // Check if these users are new (no previous news)
            $userIds = $users->pluck('id')->toArray();
            $userNewsCount = DB::table('news_users')
                ->whereIn('user_id', $userIds)
                ->count();
            
            // If these are new users with no previous news, we'll only record all news
            // but only send email for the most recent one
            $isFirstInteraction = $userNewsCount === 0 && count($userIds) > 0;
            
            // Get the most recent news item if this is first interaction
            $mostRecentNews = $news;
            if ($isFirstInteraction) {
                // Record when we're limiting emails for first-time users
                Log::info('SendNewsEmailListener: First interaction detected - limiting emails to most recent news');
                
                // Find the most recent news item
                $mostRecentNews = News::orderBy('pubDate', 'desc')->first() ?? $news;
            }
            
            // Build user-news relationships for DB
            $inserts = $users->map(fn($user) => [
                'user_id' => $user->id,
                'news_id' => $news->id,
                'send_at' => $now,
                'created_at' => $now,
                'updated_at' => $now
            ])->toArray();
            
            // Use insertOrIgnore to prevent duplicate key errors
            DB::table('news_users')->insertOrIgnore($inserts);
            
            // Send emails - only for the most recent news if this is first interaction
            foreach ($users as $user) {
                if (!$isFirstInteraction || $news->id === $mostRecentNews->id) {
                    Mail::to($user->email)
                        ->send(new NewsNotification($news));
                    $emailsSent++;
                }
            }
            
            Log::info('SendNewsEmailListener: Batch completed', [
                'users_in_batch' => count($users),
                'emails_sent' => $emailsSent,
                'first_interaction' => $isFirstInteraction
            ]);
        } catch (\Exception $e) {
            Log::error('SendNewsEmailListener: Error in batch send', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function failed(NewsProcessedEvent $event, Throwable $exception)
    {
        try {
            $newsId = is_string($event->news) ? $event->news : $event->news->id;
            
            Log::error('Falha ao processar notícia', [
                'news_id' => $newsId,
                'error' => $exception->getMessage()
            ]);
            
            // Load model if needed
            if (is_string($event->news)) {
                $news = \App\Models\News::find($event->news);
                if ($news) {
                    $news->update(['status' => 'failed']);
                }
            } else {
                $event->news->update(['status' => 'failed']);
            }
        } catch (\Exception $e) {
            Log::error('Error in failed handler', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
