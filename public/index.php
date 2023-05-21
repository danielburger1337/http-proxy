<?php declare(strict_types=1);

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once \dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (Request $request): Response {
    $url = (string) $request->query->get('url', '');

    if (!\filter_var($url, \FILTER_VALIDATE_URL)) {
        return new Response('"url" query parameter is not a valid url.', Response::HTTP_BAD_REQUEST);
    }

    $method = match ($request->query->get('method')) {
        'POST' => 'POST',
        default => 'GET'
    };

    $httpClient = HttpClient::create([
        'timeout' => 15,
    ]);
    $httpClient = new NoPrivateNetworkHttpClient($httpClient);
    $httpClient = new RetryableHttpClient($httpClient);

    try {
        $response = $httpClient->request($method, $url);

        return new JsonResponse([
            'headers' => $response->getHeaders(false),
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(false),
        ]);
    } catch (\Throwable $e) {
        $e = FlattenException::createFromThrowable($e);

        return new Response($e->getAsString(), $e->getStatusCode(), $e->getHeaders());
    }
};
