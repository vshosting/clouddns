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

echo "Domain $domainName created. \r\n";

?>