<?php

namespace App\Jobs;

use App\Models\News;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class SendNewsEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function handle()
    {
        try {
            // 1. Start log (ensure this comes FIRST in the try block)
            Log::info('[UOL RSS] Starting to fetch feed');
            
            // 2. Fetch response
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Accept' => 'application/xml',
                    'Content-Type' => 'text/xml; charset=ISO-8859-1',
                ])
                ->timeout(30)
                ->get('https://rss.uol.com.br/feed/tecnologia.xml');

            // 3. Immediate response logging
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

            // 4. Handle encoding
            $xmlString = $this->cleanXmlContent($response->body());

            // 5. XML parsing
            $xml = $this->parseXml($xmlString);
            
            // 6. Process items
            $processed = $this->processXml($xml);
            
            Log::info('[UOL RSS] Successfully processed', [
                'items_count' => count($processed['channel']['items'])
            ]);

            // 7. Here you would typically dispatch emails or process results
            // $this->sendNotifications($processed);

        } catch (\Throwable $e) {
            Log::error('[UOL RSS] Job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->fail($e);
        }
    }

    private function cleanXmlContent(string $content): string
    {
        // Remove BOM if present
        $content = preg_replace('/^\x{EF}\x{BB}\x{BF}/', '', $content);
        
        // Convert encoding
        $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        
        // Remove invalid XML characters
        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $content);
    }

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
            
            // Extract image URL from description if available
            $imageUrl = null;
            $description = (string)$item->description;
            if (preg_match("/<img[^>]+src='([^']+)'/", $description, $matches)) {
                $imageUrl = $matches[1];
                Log::info('[UOL RSS] IMAGEM', [
                    'img' => $imageUrl
                ]);
            }
            
            $newsItem = [
                'title' => trim((string)$item->title),
                'link' => trim((string)$item->link),
                'description' => trim($description),
                'pubDate' => $pubDate,
                'image_url' => $imageUrl,
            ];
            
            // Save to database
            try {
                News::updateOrCreate(
                    ['link' => $newsItem['link']],  // Find by link (unique)
                    $newsItem                       // Update or create with these attributes
                );
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
            
            // Replace Portuguese day/month names with English equivalents
            foreach ($dayMap as $pt => $en) {
            $dateString = str_replace($pt, $en, $dateString);
            }
            
            foreach ($monthMap as $pt => $en) {
            $dateString = str_replace($pt, $en, $dateString);
            }
            
            return \Carbon\Carbon::parse($dateString)->toIso8601String();
        } catch (\Exception $e) {
            Log::warning('[UOL RSS] Invalid date format', ['date' => $dateString]);
            return $dateString;
        }
    }
}