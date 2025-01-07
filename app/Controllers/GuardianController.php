<?php

namespace App\Controllers;

use GuzzleHttp\Client;
use SimpleXMLElement;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Dotenv\Dotenv;

class GuardianController
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        // https://github.com/vlucas/phpdotenv
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2)); 
        $dotenv->load();

        $this->apiKey = $_ENV['API_KEY'] ?? '';
        $this->baseUrl = $_ENV['API_URL'] ?? '';

        if (empty($this->apiKey) || empty($this->baseUrl)) {
            throw new Exception('.env is not configured properly');
        }
        
    }

    public function fetchSection(Request $request, Response $response, $args): Response
    {
        $section = $args['section'] ?? 'world';

        //cache var
        $cacheDir = dirname(__DIR__, 2) . '/cache';
        $cacheFile = $cacheDir . '/guardian_section_' . $section . '.cache';
        $cacheTTL = 600; // Cache time-to-live in seconds (10 minutes)

        // Ensure cache directory exists
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Check if a valid cache file exists
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
            $cachedResponse = file_get_contents($cacheFile);
            $response->getBody()->write($cachedResponse);
            return $response->withHeader('Content-Type', 'application/rss+xml');
        }

        //https call
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

                // Generate RSS feed
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

                // Convert RSS feed to XML and cache it
                $rssXml = $rssFeed->asXML();
                file_put_contents($cacheFile, $rssXml);

                $response->getBody()->write($rssFeed->asXML());
                return $response->withHeader('Content-Type', 'application/rss+xml');
            } else {
                $response->getBody()->write('No results found for section: ' . htmlspecialchars($section));
                return $response->withStatus(404);
            }
        } catch (Exception $e) {
            $response->getBody()->write('Error fetching data: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }
}
