<?php

namespace App\Mail;

use App\Models\News;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public News $news
    ) {
        $this->news = $news;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('News: ' . $this->news->title)
            ->view('emails.news-notification')
            ->with([
                'news' => $this->news,
                'title' => $this->news->title,
                'description' => $this->news->description,
                'link' => $this->news->link,
                'imageUrl' => $this->news->image_url ?? null,
            ]);
    }
}
