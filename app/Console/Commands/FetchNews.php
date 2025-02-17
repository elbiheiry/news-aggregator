<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\NewsAggregatorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchNews extends Command
{
    protected $signature = 'news:fetch';
    protected $description = 'Fetch latest news from external sources';

    public function __construct(protected NewsAggregatorService $newsService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $articles = $this->fetchNewsFromAPIs();

        foreach ($articles as $article) {
            if (!isset($article['url']) || empty($article['url'])) {
                continue;
            }

            Article::updateOrCreate(
                ['url' => $article['url']],
                [
                    'title' => $article['title'] ?? 'No Title',
                    'content' => $article['content'] ?? '',
                    'source' => $article['source'] ?? 'Unknown',
                    'image' => $article['image'] ?? null,
                    'author' => $article['author'] ?? 'unknown',
                    'url' => $article['url'] ?? 'unknown',
                    'published_at' => isset($article['published_at'])
                        ? Carbon::parse($article['published_at'])->toDateTimeString()
                        : now(),
                ]
            );
        }

        $this->info('News articles updated successfully!');
    }

    private function fetchNewsFromAPIs()
    {
        $articles = $this->newsService->fetchNews();
        $result = [];

        foreach ($articles as $sourceName => $articleList) { // $sourceName is 'NewsAPI', 'Guardian', 'BBC'
            foreach ($articleList as $item) {
                $newsItem = [];

                // NewsAPI Structure
                if (isset($item['title']) && isset($item['source'])) {
                    $newsItem = [
                        'title' => $item['title'],
                        'author' => $item['author'] ?? 'Unknown',
                        'content' => $item['content'] ?? $item['description'] ?? 'No content available',
                        'source' => $item['source']['name'] ?? $sourceName, // Fallback to API source
                        'url' => $item['url'],
                        'image' => $item['urlToImage'] ?? null,
                        'published_at' => $item['publishedAt'] ?? now(),
                    ];
                }
                // Guardian Structure
                elseif (isset($item['webTitle']) && isset($item['webUrl'])) {
                    $newsItem = [
                        'title' => $item['webTitle'],
                        'author' => 'Unknown',
                        'content' => 'No content available',
                        'source' => $item['sectionName'] ?? $sourceName,
                        'url' => $item['webUrl'],
                        'image' => null,
                        'published_at' => $item['webPublicationDate'] ?? now(),
                    ];
                }
                // BBC Structure
                elseif (isset($item['title']) && isset($item['news_link'])) {
                    $newsItem = [
                        'title' => $item['title'],
                        'author' => 'Unknown',
                        'content' => $item['summary'] ?? 'No content available',
                        'source' => $sourceName,
                        'url' => $item['news_link'],
                        'image' => $item['image_link'] ?? null,
                        'published_at' => now(),
                    ];
                }

                if (!empty($newsItem)) {
                    $result[] = $newsItem; // Store each cleaned article
                }
            }
        }

        return $result;
    }
}
