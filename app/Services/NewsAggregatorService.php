<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use jcobhams\NewsApi\NewsApi;

class NewsAggregatorService
{
    public function fetchNews()
    {
        return array_merge(
            $this->fetchNewsAPI(),
            $this->fetchGuardian(),
            $this->fetchBBC()
        );
    }

    private function fetchNewsAPI()
    {
        $newsapi = new NewsApi(config('services.newsapi.key'));
        $all_articles = $newsapi->getEverything('bitcoin', null, null, null, null, null, 'en', null, '10', 1);

        $data = $all_articles->articles;

        $newsAPIArray = [];

        foreach ($data as $key => $article) {
            $newsAPIArray[$key] = [
                "source" => [
                    "id" => $article->source->id ?? null,
                    "name" => $article->source->name ?? "Unknown",
                ],
                "author" => $article->author ?? "Unknown",
                "title" => $article->title,
                "description" => $article->description ?? "No description",
                "url" => $article->url,
                "urlToImage" => $article->urlToImage ?? null,
                "publishedAt" => $article->publishedAt ?? now(),
                "content" => $article->content ?? "No content available",
            ];
        }

        $result['NewsAPI'] = $newsAPIArray;

        return $result;
    }

    private function fetchGuardian()
    {
        $response = Http::get(config('services.guardian.url') . config('services.guardian.key'));

        $data = $response->json()['response']['results'] ?? [];

        $result['Guradian'] = $data;
        return $result;
    }

    private function fetchBBC() // New method for BBC News
    {
        $response = Http::get('https://bbc-api.vercel.app/latest?lang=English');

        $data = $response->json();

        $result['BBC'] = $data['Latest'];
        return $result;
    }
}
