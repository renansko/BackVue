<?php

namespace App\Jobs;

use App\Events\NewsProcessedEvent;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class getNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function handle()
    {
        try {
            Log::info('[UOL RSS] Starting to fetch feed');

            //Fetch response
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Accept' => 'application/xml',
                    'Content-Type' => 'text/xml; charset=ISO-8859-1',
                ])
                ->timeout(30)
                ->get('https://rss.uol.com.br/feed/tecnologia.xml');

            Log::info('[UOL RSS] Response received', [
                'status' => $response->status(),
                'size' => strlen($response->body())
            ]);

            if (!$response->successful()) {
                Log::error('[UOL RSS] Failed response', [
                    'status' => $response->status(),
                    'body_preview' => substr($response->body(), 0, 200)
                ]);
                return;
            }

            $xmlString = $this->cleanXmlContent($response->body());

            // XML parsing
            $xml = $this->parseXml($xmlString);
            
            // Process items to dataBase
            $processed = $this->processXml($xml);
            
            Log::info('[UOL RSS] Successfully processed', [
                'items_count' => count($processed['channel']['items'])
            ]);

        } catch (\Throwable $e) {
            Log::error('[UOL RSS] Job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->fail($e);
        }
    }

    // The SimpleXml have some error to parse a uol XML this function resolve that 
    private function cleanXmlContent(string $content): string
    {
        // Remove BOM if present
        $content = preg_replace('/^\x{EF}\x{BB}\x{BF}/', '', $content);
        
        // Convert encoding
        $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        
        // Remove invalid XML characters
        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $content);
    }

    // Parsing data to can save in the database
    private function parseXml(string $xmlString): SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        
        try {
            $xml = new SimpleXMLElement(
                $xmlString, 
                LIBXML_NOCDATA | LIBXML_NOBLANKS | LIBXML_PARSEHUGE
            );
        } catch (\Exception $e) {
            Log::error('[UOL RSS] XML parsing failed', [
                'errors' => array_map(function ($error) {
                    return "Line {$error->line}: {$error->message}";
                }, libxml_get_errors()),
                'preview' => substr($xmlString, 0, 500)
            ]);
            libxml_clear_errors();
            throw $e;
        }

        if (!$xml->channel) {
            throw new \RuntimeException('Missing channel element in XML');
        }

        return $xml;
    }

    private function processXml(SimpleXMLElement $xml)
    {
        $result = [
            'channel' => [
                'title' => (string)$xml->channel->title ?: 'No title',
                'link' => (string)$xml->channel->link ?: '',
                'description' => (string)$xml->channel->description ?: '',
                'items' => []
            ]
        ];

        foreach ($xml->channel->item as $item) {

             $pubDate = $this->parseDate((string)$item->pubDate);
             
            // Verificar se existe essa noticia no banco de dados
            //  $exists = DB::transaction(function () use ($hash) {
            //      return TrackedNewsItem::where('item_hash', $hash)
            //          ->lockForUpdate()
            //          ->exists();
            //  });

            // This is a index in database make sure the news is a unique and best performance to finded
            $hash = hash('sha256', (string)$item->link . $pubDate);
            
            // Log::info('[UOL RSS] HASH', [
            //     'hash' => $hash
            // ]);

            // Soluction to parse a image and description where is in the same string
            $imageUrl = null;
            $description = (string)$item->description;
            $imageUrl = null;
            
            // Extract image URL from description
            if (preg_match("/<img[^>]+src='([^']+)'/", $description, $matches)) {
                $imageUrl = $matches[1];
                // Log::info('[UOL RSS] IMAGEM', [
                //     'img' => $imageUrl
                // ]);
                
            }
            // Remove the image and get only a description
            $description = preg_replace("/<img[^>]+>/", "", $description);
            // Clean up any extra whitespace
            $description = trim($description);

            $newsItem = [
                'title' => trim((string)$item->title),
                'link' => trim((string)$item->link),
                'description' => trim($description),
                'pubDate' => $pubDate,
                'image_url' => $imageUrl,
                'news_hash' => $hash
            ];

            try {
                $exists = News::where('news_hash', $hash)->exists();
                
                if (!$exists) {
                    $news = News::create($newsItem);
                } else {
                    $news = null;
                    Log::info('[UOL RSS] Skipping existing news', [
                        'hash' => $hash,
                        'title' => $newsItem['title']
                    ]);
                    continue;
                }
                if ($news->wasRecentlyCreated) {
                    Log::info('[UOL RSS] Saved news item', [
                        'id' => $news->id,
                        'title' => $news->title
                    ]);
                    NewsProcessedEvent::dispatch($news); 
                }

            } catch (\Exception $e) {
                Log::error('[UOL RSS] Failed to save news item', [
                    'error' => $e->getMessage(),
                    'item' => $newsItem['title']
                ]);
            }

            $result['channel']['items'][] = $newsItem;
        }

         $savedCount = News::count();
            Log::info('[UOL RSS] Processed and saved items', [
            'processed_count' => count($result['channel']['items']),
            'total_in_db' => $savedCount
        ]);

        return $result;
    }

    private function parseDate(string $dateString): string
    {
        try {
            $monthMap = [
            'Jan' => 'Jan', 'Fev' => 'Feb', 'Mar' => 'Mar', 'Abr' => 'Apr',
            'Mai' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Ago' => 'Aug',
            'Set' => 'Sep', 'Out' => 'Oct', 'Nov' => 'Nov', 'Dez' => 'Dec'
            ];
            
            $dayMap = [
            'Seg' => 'Mon', 'Ter' => 'Tue', 'Qua' => 'Wed', 
            'Qui' => 'Thu', 'Sex' => 'Fri', 'Sáb' => 'Sat', 'Dom' => 'Sun'
            ];
            
            foreach ($dayMap as $pt => $en) {
            $dateString = str_replace($pt, $en, $dateString);
            }
            
            foreach ($monthMap as $pt => $en) {
            $dateString = str_replace($pt, $en, $dateString);
            }
            
            return Carbon::parse($dateString)->toIso8601String();
        } catch (\Exception $e) {
            Log::warning('[UOL RSS] Invalid date format', ['date' => $dateString]);
            return $dateString;
        }
    }
}