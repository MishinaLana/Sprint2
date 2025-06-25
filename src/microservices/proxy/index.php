<?php
/**
 * MONOLITH_URL: http://monolith:8080
 * MOVIES_SERVICE_URL: http://movies-service:8081
 * EVENTS_SERVICE_URL: http://events-service:8082
 * GRADUAL_MIGRATION: "true"
 * MOVIES_MIGRATION_PERCENT: "50"
 */

$eventsServiceUrl = getenv('EVENTS_SERVICE_URL') ?? throw new \Exception('Events Service URL not set');

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => getUrlToProxy($_SERVER['REQUEST_URI']),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_MAXREDIRS => 2,
]);

$response = curl_exec($curl);
$responseStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

header('Content-type: application/json');
http_response_code($responseStatusCode);

echo $response;

function getUrlToProxy(string $requestUrl): string
{
    $monolithUrl = getenv('MONOLITH_URL') ?? throw new \Exception('Monolith URL not set');
    $moviesServiceUrl = getenv('MOVIES_SERVICE_URL') ?? throw new \Exception('Movies Service URL not set');
    $gradualMigration = getenv('GRADUAL_MIGRATION') == "true" ;
    $moviesMigrationPercent = (int) getenv('MOVIES_MIGRATION_PERCENT') ?? 100;

    $proxyTo = $monolithUrl;
    $urlsToMigrate = ['/api/movies'];
    $hasNewService = (bool) array_filter($urlsToMigrate, fn (string $url) => str_starts_with($url,
        $_SERVER['REQUEST_URI']));


    if ($gradualMigration && $hasNewService) {
        $rand = mt_rand(1, 100);
        if ($rand > $moviesMigrationPercent) {
            $proxyTo = $moviesServiceUrl;
        }
    }

    return $proxyTo .= $requestUrl;
}
