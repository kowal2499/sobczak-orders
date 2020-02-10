<?php

use GuzzleHttp\Cookie\CookieJar;
require __DIR__ . '/vendor/autoload.php';

const SESSION_ID = 'd5f682272196f29eb354423a798cda19';
const BASE_URI = 'http://localhost:8005';

$cookieJar = CookieJar::fromArray([
    'PHPSESSID' => SESSION_ID,
], 'localhost');

$client = new GuzzleHttp\Client([
    'base_uri' => BASE_URI,
    'defaults' => [
        'exception' => false
    ]
]);

$response = $client->request('GET', '/working_days/api/2020-05-01', [
    'cookies' => $cookieJar
]);
echo $response->getBody() . PHP_EOL;
