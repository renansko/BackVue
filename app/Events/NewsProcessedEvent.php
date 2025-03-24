<?php

namespace App\Events;

use App\Models\News;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NewsProcessedEvent implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public $news;

    public function __construct(News $news)
    {
        $this->news = $news;
        Log::info('NewsProcessedEvent: News processed', [
            $this->news
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('news-broad-cast');
    }
}
