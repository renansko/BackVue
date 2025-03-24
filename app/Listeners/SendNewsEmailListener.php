<?php

namespace App\Listeners;

use App\Events\NewsProcessedEvent;
use App\Mail\NewsNotification;
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
            Log::info('SendNewsEmailListener: Processing news', ['news_id' => $news->id, 'title' => $news->title]);
            // Add this for debugging
            $pivotTableName = 'news_users'; // Make sure this matches your actual table name
            $testQuery = DB::table($pivotTableName)->where('news_id', $news->id)->count();
            Log::info('Debug pivot table', [
                'table' => $pivotTableName,
                'news_id' => $news->id,
                'existing_records' => $testQuery
            ]);
            
            // Enviar e-mails para usuários que ainda não receberam a noticia nova
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
        $now = now();

        // Insert in all users a new newsletter
        $inserts = $users->map(fn($user) => [
            'user_id' => $user->id,
            'news_id' => $news->id,
            'send_at' => $now,
            'created_at' => $now,
            'updated_at' => $now
        ])->toArray();
        
        DB::table('news_users')->insert($inserts);
        
        // Disparar e-mails
        foreach ($users as $user) {
            Mail::to($user->email)
                ->send(new NewsNotification($news));
        }
    }

    public function failed(NewsProcessedEvent $event, Throwable $exception)
    {
        Log::error('Falha ao processar notícia', [
            'news_id' => $event->news->id,
            'error' => $exception->getMessage()
        ]);
        
        // Opcional: Marcar a notícia como problemática
        $event->news->update(['status' => 'failed']);
    }
}
