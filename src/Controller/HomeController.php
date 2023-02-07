<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use \Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    private string $baseUrl;
    private HttpClientInterface $client;
    private ContainerBagInterface $params;
    private CacheInterface $cache;

    public function __construct(ContainerBagInterface $params, CacheInterface $cache)
    {
        $this->params   = $params;
        $this->baseUrl  = $this->params->get('rapid_url');
        $this->cache    = $cache;
        $this->client   = HttpClient::create(
            ['headers' => [
                'x-rapidapi-host' => $this->params->get('rapid_host'),
                'x-rapidapi-key' => $this->params->get('rapid_key'),
            ]]);
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $leaguesMatches = $this->cache->get('leagues_matches', function() {
            $leaguesResponse = $this->executeRequest("GET", "leagues", ['code'=>'FR']);
            $leaguesList = [];
            foreach ($leaguesResponse as $league) {
                $leaguesList[] = [
                    'id' => $league['league']['id'],
                    'name' => $league['league']['name'],
                    'logo' => $league['league']['logo'],
                ];
            }
            return $leaguesList;
        });
        return $this->render('home.html.twig', [
            'data' => $leaguesMatches,
        ]);
    }

    #[Route('/matches/{id}/{season}', name: 'league_matches')]
    public function matches(int $id, int $season): JsonResponse
    {
        $matches = $this->cache->get('matches_for_league_'.$id.'_'.$season, function() use ($id, $season){
            $fixturesResponse = $this->executeRequest("GET", "fixtures",
                ['league'=>$id, 'season' => $season]);
            $matchesInfo = [];
            foreach ($fixturesResponse as $fixtures)
            {
                $matchesInfo[] = [
                    'id'   => $fixtures['fixture']['id'],
                    'home' => ['team' => $fixtures['teams']['home']['name'], 'goals' => $fixtures['goals']['home']],
                    'away' => ['team' => $fixtures['teams']['away']['name'], 'goals' => $fixtures['goals']['away']]
                ];
            }

            return $matchesInfo;
        });

        return new JsonResponse(['data' => $matches]);
    }

    #[Route('/match/{id}', name: 'match_info')]
    public function match(int $id): JsonResponse
    {
        $stats = $this->cache->get('match_'.$id, function() use($id){
            return $this->executeRequest('GET', 'fixtures/statistics', ['fixture' => $id]);
        });

        return new JsonResponse(['data' => $stats]);
    }

    private function executeRequest(string $method, string $requestURL, array $query=[]) : array
    {
        $response = $this->client->request($method, $this->baseUrl.$requestURL, ['query' => $query]);
        return $response->toArray()['response'];
    }
}