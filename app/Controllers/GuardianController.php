<?php

namespace App\Controllers;

use GuzzleHttp\Client;
use SimpleXMLElement;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Dotenv\Dotenv;
use App\Services\Cache\CacheServiceInterface;

class GuardianController
{
    private $apiKey;
    private $baseUrl;
    private $cacheService;

    public function __construct(CacheServiceInterface $cacheService)
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->load();

        $this->apiKey = $_ENV['API_KEY'] ?? '';
        $this->baseUrl = $_ENV['API_URL'] ?? '';
        $this->cacheService = $cacheService;

        if (empty($this->apiKey) || empty($this->baseUrl)) {
            throw new Exception('.env is not configured properly');
        }
    }

    public function fetchSection(Request $request, Response $response, $args): Response
    {
        $section = $args['section'] ?? 'world';
        $cacheKey = 'guardian_section_' . $section;

        // Check cache first
        if ($cachedResponse = $this->cacheService->get($cacheKey)) {
            $response->getBody()->write($cachedResponse);
            return $response->withHeader('Content-Type', 'application/rss+xml');
        }

        // If not in cache, fetch from API
        $client = new Client();

        try {
            $apiResponse = $client->request('GET', $this->baseUrl, [
                'query' => [
                    'q' => $section,
                    'api-key' => $this->apiKey
                ]
            ]);

            $data = json_decode($apiResponse->getBody(), true);

            if ($data['response']['status'] === 'ok' && !empty($data['response']['results'])) {
                $results = $data['response']['results'];
                $rssXml = $this->generateRssFeed($results, $section);
                
                // Cache the response
                $this->cacheService->set($cacheKey, $rssXml);

                $response->getBody()->write($rssXml);
                return $response->withHeader('Content-Type', 'application/rss+xml');
            }

            $response->getBody()->write('No results found for section: ' . htmlspecialchars($section));
            return $response->withStatus(404);
        } catch (Exception $e) {
            $response->getBody()->write('Error fetching data: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    private function generateRssFeed(array $results, string $section): string
    {
        $rssFeed = new SimpleXMLElement('<rss></rss>');
        $rssFeed->addAttribute('version', '2.0');

        $channel = $rssFeed->addChild('channel');
        $channel->addChild('title', 'Guardian API RSS Feed - ' . ucfirst($section));
        $channel->addChild('link', $this->baseUrl);
        $channel->addChild('description', 'RSS feed generated from Guardian API for ' . $section . ' section.');

        foreach ($results as $result) {
            $item = $channel->addChild('item');
            $item->addChild('title', htmlspecialchars($result['webTitle']));
            $item->addChild('link', htmlspecialchars($result['webUrl']));
            $item->addChild('guid', htmlspecialchars($result['apiUrl']));
        }

        return $rssFeed->asXML();
    }
}