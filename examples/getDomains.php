<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/helper.php';

use Noodlehaus\Config;

const CLIENT_ZONE_URL = "https://admin.vshosting.cloud/api/public/";
const CLOUDDNS_URL = "https://admin.vshosting.cloud/clouddns/";

$config = new Config("config.json");

$client = new GuzzleHttp\Client([
    "base_uri" => CLIENT_ZONE_URL,
    'http_errors' => false
]);

$loginResponse = $client->post(CLIENT_ZONE_URL . "auth/login", [
    GuzzleHttp\RequestOptions::JSON => [
        "email" => $config->get("email"),
        "password" => $config->get("password"),
    ]
]);

handleResponse($loginResponse, "Login failed");

$loginContent = json_decode($loginResponse->getBody());

// search for all domains

$domainSearchResponse = $client->post(CLOUDDNS_URL . "domain/search", [
    'headers' => [
        'Authorization' => 'Bearer ' . $loginContent->auth->accessToken
    ],
    GuzzleHttp\RequestOptions::JSON => [
        "search" => [
            [
            "name" => "clientId",
            "operator" => "eq",
            "value" => $loginContent->user->clientUserList[0]->clientId
            ]
        ],
        "limit" => 10000
    ]]);

handleResponse($domainSearchResponse, "Domain search failed.");
$domainSearchContent = json_decode($domainSearchResponse->getBody());

echo "Found exactly " . count($domainSearchContent->items) .  " domain(s) on your account. \r\n";


// search for domain by domainName

$domainName = "example.cz.";

$domainSearchByNameResonse = $client->post(CLOUDDNS_URL . "domain/search", [
    'headers' => [
        'Authorization' => 'Bearer ' . $loginContent->auth->accessToken
    ],
    GuzzleHttp\RequestOptions::JSON => [
        "search" => [
            [
                "name" => "clientId",
                "operator" => "eq",
                "value" => $loginContent->user->clientUserList[0]->clientId
            ],
            [
                "name" => "domainName",
                "operator" => "eq",
                "value" => $domainName
            ]
        ],
    ]
]);

handleResponse($domainSearchByNameResonse, "Domain search by domain name failed.");
$domainSearchByNameContent = json_decode($domainSearchByNameResonse->getBody());

echo "Found exactly " . count($domainSearchByNameContent->items) .  " result(s) for domain $domainName \r\n";

?>