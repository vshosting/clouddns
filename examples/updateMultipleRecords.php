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

// generate unique domain name, only for purpose of this test
$domainName = bin2hex(random_bytes(16)) . ".cz.";

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

// publish domain changes
$domainRecordsResponse = $client->put(CLOUDDNS_URL . "domain/{$domainContent->id}/records", [
    'headers' => [
        'Authorization' => 'Bearer ' . $loginContent->auth->accessToken
    ],
    GuzzleHttp\RequestOptions::JSON => [
        "soaTtl" => 3600,
        "domainRecord" => [
            [
                "name" => $domainContent->domainName,
                "type" => "A",
                "content" => "1.1.1.1"
            ],
            [
                "name" => "foo." . $domainContent->domainName,
                "type" => "A",
                "content" => "8.8.8.8"
            ],
            [
                "name" => "bar." . $domainContent->domainName,
                "type" => "CNAME",
                "content" => "foo." . $domainContent->domainName
            ],
            [
                "name" => $domainContent->domainName,
                "type" => "MX",
                "content" => "10 mail." . $domainContent->domainName
            ]
        ]
    ]
]);

handleResponse($domainRecordsResponse, "Updating records for domain failed.");

// get domain by id
$domainByIdResponse = $client->get(CLOUDDNS_URL . "domain/{$domainContent->id}", [
    'headers' => [
        'Authorization' => 'Bearer ' . $loginContent->auth->accessToken
    ]
]);

handleResponse($domainByIdResponse, "Domain detail request failed.");

$domainByIdResponseContent = json_decode($domainByIdResponse->getBody());
echo "Domain $domainByIdResponseContent->domainName has " . count($domainByIdResponseContent->activeDomainRecordList) . " record(s).  \r\n";

?>