<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/helper.php';

use Noodlehaus\Config;

const CLIENT_ZONE_URL = "https://admin.vshosting.cloud/api/public/";
const CLOUDDNS_URL = "https://admin.vshosting.cloud/clouddns/";

$config = new Config("config.json");

// generate unique domain name, only for purpose of this test
$domainName = bin2hex(random_bytes(16)) . ".cz.";

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

handleResponse($loginResponse, "Login failed.");

$loginContent = json_decode($loginResponse->getBody());

// create domain
$domainResponse = $client->post(CLOUDDNS_URL . "domain", [
    'headers' => [
        'Authorization' => 'Bearer ' . $loginContent->auth->accessToken
    ],
    GuzzleHttp\RequestOptions::JSON => [
        "domainName" => $domainName,
        "clientId" => $loginContent->user->clientUserList[0]->clientId
    ]
]);

handleResponse($domainResponse, "Creating domain failed.");

$domainContent = json_decode($domainResponse->getBody());

// create A record
$recordAResponse = $client->post(CLOUDDNS_URL . "record-a", [
    'headers' => [
        'Authorization' => 'Bearer ' . $loginContent->auth->accessToken
    ],
    GuzzleHttp\RequestOptions::JSON => [
        "name" => $domainContent->domainName,
        "domainId" => $domainContent->id,
        "type" => "A",
        "value" => "8.8.8.8"
    ]
]);

handleResponse($recordAResponse, "Addding an A record failed. \r\n");

// publish domain changes
$domainPublishResponse = $client->put(CLOUDDNS_URL . "domain/{$domainContent->id}/publish", [
    'headers' => [
        'Authorization' => 'Bearer ' . $loginContent->auth->accessToken
    ],
    GuzzleHttp\RequestOptions::JSON => [
        "soaTtl" => 3600,
    ]
]);
handleResponse($domainPublishResponse, "Domain publish failed.");

$domainPublishResponseContent = json_decode($domainPublishResponse->getBody());


// after publish you need to use record id from domain.lastDomainRecordList
$recordAId = $domainPublishResponseContent->lastDomainRecordList[0]->id;

$recordAEditResponse = $client->put(CLOUDDNS_URL . "record-a/{$recordAId}", [
    'headers' => [
        'Authorization' => 'Bearer ' . $loginContent->auth->accessToken
    ],
    GuzzleHttp\RequestOptions::JSON => [
        "name" => $domainContent->domainName,
        "domainId" => $domainContent->id,
        "type" => "A",
        "value" => "1.1.1.1"
    ]
]);
handleResponse($recordAEditResponse, "Record A edit failed. \r\n");

// publish domain changes
$domainPublishResponse = $client->put(CLOUDDNS_URL . "domain/{$domainContent->id}/publish", [
    'headers' => [
        'Authorization' => 'Bearer ' . $loginContent->auth->accessToken
    ],
    GuzzleHttp\RequestOptions::JSON => [
        "soaTtl" => 3600,
    ]
]);
handleResponse($domainPublishResponse, "Domain publish with edited A record failed.");
$domainPublishResponseContent = json_decode($domainPublishResponse->getBody());

echo "Record A value changed to: " . $domainPublishResponseContent->activeDomainRecordList[0]->value . "  \r\n";
?>