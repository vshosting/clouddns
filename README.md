## Basic information

CloudDNS service can be managed using REST API. The API offers all the capabilities of the [customer administration](https://admin.vshosting.cloud/clouddns).
It communicates using JSON format with UTF-8 encoding.

In order to use CloudDNS API, you need an active user the customer administration, that has permissions for CloudDNS.
You can get access tokens for communication with CloudDNS API by logging in against the customer administration API.

Complete documentation for CloudDNS API: https://admin.vshosting.cloud/clouddns/swagger/ \
Complete documentation for customer administration API: https://admin.vshosting.cloud/api/public/swagger/ 


## API usage examples

There are several simple PHP examples on how to use CloudDNS API available in the `examples` directory.
To run them locally, you have to execute `composer install`, which installs the necessary dependencies.


## Authentization

All API access points require a valid `accessToken` sent in header, as per the oAuth2.0 standard. CloudDNS API supports only Bearer token type.

An example call to load domain details:

```
GET /clouddns/domain/csFHCU9OROKEmRxyVekn-Q HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```

where "oenCToMxTrSEgmHShDJLew" is the value of temporary valid `accessToken`.
If `accessToken` is invalid or expired, the API returns the following error:

```json
{
   "error":{
      "code":401,
      "message":"Not authorized."
   }
}
```

You can get `accessToken` and `refreshToken` by calling `POST /auth/login` against customer administration API.

In order to communicate with the API, you need to create a user with permissions for the CloudDNS service in the customer administration.
After creating a user, you need to confirm the registration e-mail sent to them.
A user with pending confirmation cannot access CloudDNS API.


#### POST /auth/login


| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLE                | SOURCE |
| -------------- | --------- | -------- | ---------------------- | ------ |
| email          | string    | true     | example@example.com    |  body  |
| password       | string    | true     | HO0s88QGT869dEaMDEAFoQ |  body  |


Example of a login call:

```
POST /api/public/auth/login HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Cache-Control: no-cache
```
```json
{
   "email":"user@example.cz",
   "password":"z!bze$yn&Ug6^=Jp"
}
```

If the login is successful, the API returns `accessToken` and `refreshToken`. `accessToken` is valid for 30 minutes. `refreshToken` expires after 60 minutes.
`accessToken` must be included in the header of every request. To get a new set of tokens, you must make a call to `POST /auth/refresh` before `refreshToken` expires. This endpoint returns the same response as `POST /auth/login`.
If `refreshToken` is expired, you need to login again using the `POST /auth/login` endpoint.


## API limits

* The number of API queries is not limited, but the excessive usage of the API in order to overload the application will not be tolerated
* the number of failed requests to restore oAuth tokens is limited to 10,000 attempts per hour from one IP address
* the number of failed requests with an invalid `accessToken` is limited to 10,000 attempts per hour from one IP address

In case of login failure (incorrect credentials) or in case of reaching the limit above, the API will keep returning an error for all subsequent requests from the same IP address
```json
{
    "error": {
        "code": 405,
        "message": "Potentially fraud behaviour detected. Please provide additional validation by Google reCaptcha."
    }
}
```
To release the blockage, it is necessary to open the user interface of the application, and properly send the captcha on the login page. 
Alternatively, it is necessary to wait for a release of the blockage that will be made in 1 hour from 1st undesirable requirement.


## API response types

API returns several different responses, depending on the action type and whether the data sent to the API was valid.

#### Response reporting success

For example deleting a domain via `DELETED /clouddns/domain/{id}`.
```json
{
   "success": true
}
```

#### Response with current entity object

For example editing A record via `PUT /clouddns/record-a/{id}`.

```json
{
   "id":"RCFyDiTXSOiyrTqHLt0F-A",
   "domainId":"naAPLQIaSDGiNOjsmepL3A",
   "originalDomainRecordId":null,
   "status":"ACTIVE",
   "name":"efdsf.bb2.cz.",
   "class":"IN",
   "type":"A",
   "originApplicationType":"CLOUDDNS",
   "originUserFullName":"M C",
   "created":"2019-10-29T08:25:27Z",
   "lastUpdate":"2019-11-15T14:11:17Z",
   "value":"8.8.8.2"
}
```

#### Business logic error code

```json
{
   "error":{
      "code":2001,
      "message":"Invalid user credentials"
   }
}
```

#### Validation error for invalid input parameters

For example sending an API request with missing parameters.
```json
{
    "error": {
        "code": 400,
        "message": "invalid user input",
        "meta": [
            {
                "parameter": "clientId",
                "message": "field is required",
                "code": 100
            },
            {
                "parameter": "domainName",
                "message": "field is required",
                "code": 100
            }
        ]
    }
}
```

## Roles and permissions

In order to communicate with CloudDNS API, you need to create a user with permissions for the CloudDNS service in the customer administration.
After creating the user, confirm the registration e-mail.
User with pending confirmation cannot access CloudDNS API.

The user must have one of the following roles:
   * `MANAGER` - in the customer administration, this role is shown as “Administrator”
   * `TECHNICAL_USER` - in the customer administration, this role is shown as “Technical administrator”

The user must have the following options enabled:
   * `cloudDnsEnabled` - in the customer administration, this option is shown as “Can manage domains in CloudDNS?”
   * `accessToClientZone` - in the customer administration, this option is shown as “Can log in under your user account?“

Both roles and their settings can be editted after creating the account. The change in permissions will take place after refreshing token or upon logging in.

If the user does not have sufficient permissions, the API returns an error:

```json
{  
   "error":{  
      "code":402,
      "message":"Insufficient privileges."
   }
}
```

The customer administration lets user to be connected to multiple clients. Owing that, a user can use one `accessToken` to execute API calls on all clients that they have CloudDNS permissions for.

## UUID
CloudDNS has unique identifiers for all records created by the system. It uses random UUID generator based on RFC4122 v4 and converted to a URL-safe string with 22 characters.


## Creating a domain

To create a domain, you need to enter a domain name and a client id. If successful, the response will return a domain object.

####  POST /clouddns/domain

| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLE                | SOURCE |
| -------------- | --------- | -------- | ---------------------- | ------ |
| accessToken    | string    | true     | oenCToMxTrSEgmHShDJLew | header |
| clientId       | string    | true     | HO0s88QGT869dEaMDEAFoQ | body   |
| domainName     | string    | true     | example.cz.            | body   |


## Domain status

| Status                          | Explanation                                                                                                                |
| ------------------------------- | :------------------------------------------------------------------------------------------------------------------------- |
| NEW                             | New domain, records are waiting for publication.                                                                           |
| PUBLISHING_TO_INTERNET          | Domain has been published to the Internet. After TTL runs out, its availability will automatically checked.                |
| WAITING_TO_TTL                  | Changes in DNS records have been published to the Internet. After TTL runs out, the changes will be automatically checked. |
| ACTIVE                          | Domain is active and available in the Internet.                                                                            |
| DEACTIVATED                     | Domain has been deactivated by user and is not being published.                                                            |
| DELETED                         | Domain has been marked for deletion and is not being published. It will be irreversibly deleted in 14 days.                |
| DOMAIN_NOT_HEADING_TO_VSHOSTING | Domain does not have correct NS records and probably does not use CloudDNS servers.                                        |                                                   
| DOMAIN_NOT_AVAILABLE            | Domain is not available in the Internet.                                                                                   |
| WARNING                         | SOA serial check was not successful.                                                                                       |

The automatically checks domains and swiches between `ACTIVE`, `DOMAIN_NOT_AVAILABLE`, `DOMAIN_NOT_HEADING_TO_VSHOSTING`, `DOMAIN_NOT_AVAILABLE` and `WARNING` states based on availability.

After calling `POST /clouddns/domain/{id}/publish`, the changes to `PUBLISHING_TO_INTERNET` and `WAITING_TO_TTL` states.

You can switch a domain to the `DEACTIVATED` state by calling `PUT /clouddns/domain/{id}/deactivated`.

A domain enters `DELETED` state after `DELETE /clouddns/domain/{id}` is called.

## Listing domains

Domain listing supports pagination, filtering and ordering.
Pagination is controlled by the `limit` and `offset` parameters.
Sorting is controlled by `sort` parameter\
You can filter the results using `search` parameter. This parameter accepts an object array with the following parameters:

 * `name` - attribute name; used for searching
 * `operator` - filter operator name
 * `type` - default data type
 * `value` - desired value

To get a list of attributes you can search over, please call `GET /clouddns/search/columns`. This returns a list of available attributes and filter operators. 

#### Supported filter operators

| Operator | Meaning                 |
| -------- | ----------------------- |
| eq       | equality                |
| ne       | unequality              |
| search   | fulltext search         |
| lt       | less than               |
| lte      | less than or equal      |
| gt       | greater than            |
| gte      | greater than or equal   |

Domains can be sorted by attributes in asceding or descending order using the `sort` parameter.

#### POST /clouddns/domain/search

| PARAMETER NAME  | DATA TYPE                       | REQUIRED | EXAMPLE                                                                | SOURCE |
| --------------- | ------------------------------- | -------- | ---------------------------------------------------------------------- | ------ |
| accessToken     | string                          | true     | HO0s88QGT869dEaMDEAFoQ                                                 | header |
| search          | array of [EsSearchItem] objects | true     | [{"name":"clientId","operator":"eq","value":"HO0s88QGT869dEaMDEAFoQ"}] | body   |
| sort            | array of [EsSortItem] objects   | false    | [{"name":"created","ascending":true}]                                  | body   |
| offset          | int                             | false    | 10                                                                     | body   |
| limit           | int                             | false    | 10                                                                     | body   |


#### EsSearchItem object

| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLE       | 
| -------------- | --------- | -------- | ------------- | 
| name           | string    | true     | `domainName`  | 
| operator       | string    | true     | `eq`          | 
| type           | string    | true     | `string`      | 
| value          | string    | true     | `example.cz.` |  


#### EsSortItem object

| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLE | 
| -------------- | --------- | -------- | ------- | 
| name           | string    | true     | created | 
| ascending      | boolean   | true     | true    | 

#### Example endpoint call to list all client's domains:

```
POST /clouddns/domain/search HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```
```json
{
   "search":[
      {
         "name":"clientId",
         "operator":"eq",
         "value":"HO0s88QGT869dEaMDEAFoQ"
      }
   ],
   "limit":10000
}
```

## Domain details and DNS records overview

Information about domain's DNS records is available through `GET /clouddns/domain/{id}` endpoint. The returned object includes `activeDomainRecordList` and `lastDomainRecordList` fields.

`activeDomainRecordList` includes domain's published DNS records. \
`lastDomainRecordList` has the same structure as `activeDomainRecordList`. It includes unpublished changes in DNS records.

After calling `PUT /clouddns/domain/{id}/publish`, the changed in DNS records will be published to the Internet.
When publishing records, the contents of `lastDomainRecordList` are copied over to `activeDomainRecordList` and new `lastDomainRecordList` is created with currently valid records.
After publishing a domain, `activeDomainRecordList` and `lastDomainRecordList` will be identical.
 

## Other domain operations

A domain can be deleted, deactivated and restored.

Domain can be deleted using `DELETE /clouddns/domain/{id}` endpoint. It can be recovered via `PUT /clouddns/domain/{id}/recover` within 14 days.

You can deactivate a domain using `PUT /clouddns/domain/{id}/deactivate`. Deactivated domains are not published to the Internet.
You can reactivate the domain by calling `PUT /clouddns/domain/{id}/activate`. 

All changes since last published version can be rolled back using `PUT clouddns/domain/{id}/reset-changes`.

## Adding, editing and deleting DNS records

CloudDNS API supports the following records: `A`, `AAAA`, `CNAME`, `MX`, `TXT`, `SRV`, `CAA`, `SSHFP`, `TLSA`.

The various endpoints serve for adding, editing and deleting DNS records of the latest version of a domain. Changes made by these endpoints have no effect on the working copy of the domain. They become valid only after calling `PUT /clouddns/domain/{id}/publish`.

You need to keep on mind, that when you publish the changes using `PUT /clouddns/domain/{id}/publish`, IDs of all of the domain's records will be changed (due to versioning). If you want to edit/delete another DNS record, you need to load the domain's details again, search for the DNS record in `lastDomainRecordList` field and use the given ID.
 

#### Endpoints for adding and editing DNS records of `A`, `AAAA`, `CNAME`, `TXT` type accept the same input parameters

| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLE                | SOURCE |
| -------------- | --------- | -------- | ---------------------- | ------ |
| accessToken    | string    | true     | oenCToMxTrSEgmHShDJLew | header |
| domainId       | string    | true     | vy6mzJerQTSOy8ur-Iakfw | body   |
| name           | string    | true     | server.example.cz.     | body   |
| type           | string    | true     | A                      | body   |
| value          | string    | true     | 127.0.0.1              | body   |

#### Example call to add an A record

```
POST /clouddns/record-a HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```
```json
{
   "name":"example.cz.",
   "value":"1.2.3.4",
   "domainId":"vy6mzJerQTSOy8ur-Iakfw",
   "type":"A"
}
```


The methods for adding other DNS records have different input parameters.

#### Example call to add an MX record

| PARAMETER NAME | DATE TYPE | REQUIRED | EXAMPLES               | SOURCE |
| -------------- | --------- | -------- | ---------------------- | ------ |
| accessToken    | string    | true     | AAAABBBBCCCCDDDDEEEEFF | header |
| domainId       | string    | true     | vy6mzJerQTSOy8ur-Iakfw | body   |
| name           | string    | true     | example.net.           | body   |
| type           | string    | true     | MX                     | body   |
| priority       | int       | true     | 10                     | body   |
| value          | string    | true     | mx.vshosting.cloud     | body   |

```
POST /clouddns/record-mx HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```
```json
{
   "name":"example.cz.",
   "domainId":"vy6mzJerQTSOy8ur-Iakfw",
   "type":"MX",
   "priority": 10,
   "value":"mx.vshosting.cloud"
}
```

#### Example call to add a SRV record

| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLE                | SOURCE |
| -------------- | --------- | -------- | ---------------------- | ------ |
| accessToken    | string    | true     | oenCToMxTrSEgmHShDJLew | header |
| domainId       | string    | true     | vy6mzJerQTSOy8ur-Iakfw | body   |
| name           | string    | true     | _sip._tcp.example.cz.  | body   |
| type           | string    | true     | SRV                    | body   |
| priority       | int       | true     | 10                     | body   |
| weight         | int       | true     | 60                     | body   |
| port           | int       | true     | 5060                   | body   |
| value          | string    | true     | sipserver.example.cz.  | body   |

```
POST /clouddns/record-srv HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```
```json
{
   "name":"_sip._tcp.example.cz.",
   "domainId":"vy6mzJerQTSOy8ur-Iakfw",
   "type":"SRV",
   "priority": 10,
   "weight": 60,
   "port": 5060,
   "value":"sipserver.example.com."
}
```

#### Example call to add a CAA record

| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLE                | SOURCE |
| -------------- | --------- | -------- | ---------------------- | ------ |
| accessToken    | string    | true     | AAAABBBBCCCCDDDDEEEEFF | header |
| domainId       | string    | true     | AAAABBBBCCCCDDDDEEEEFF | body   |
| name           | string    | true     | example.cz.            | body   |
| type           | string    | true     | CAA                    | body   |
| flag           | int       | true     | 0                      | body   |
| tag            | string    | true     | issue                  | body   |
| value          | string    | true     | letsencrypt.org        | body   |
```
POST /clouddns/record-srv HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```
```json
{
   "name":"example.cz.",
   "domainId":"vy6mzJerQTSOy8ur-Iakfw",
   "type":"CAA",
   "flag": 0,
   "tag": "issue",
   "value":"letsencrypt.org"
}
```

#### Example call to add an SSHFP record

| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLE                                  | NOTES                                                       | SOURCE |
| -------------- | --------- | -------- | ---------------------------------------- | ----------------------------------------------------------- | ------ |
| accessToken    | string    | true     | oenCToMxTrSEgmHShDJLew                   |                                                             | header |
| domainId       | string    | true     | vy6mzJerQTSOy8ur-Iakfw                   |                                                             | body   |
| name           | string    | true     | host.example.cz.                         |                                                             | body   |
| type           | string    | true     | SSHFP                                    |                                                             | body   |
| algorithm      | string    | true     | 2                                        | `1: RSA`, `2: DSS`, `3: ECDSA`, `4: Ed25519`                | body   |
| algorithmType  | string    | true     | 1                                        | `1: SHA-1`; `2: SHA-256`;                                   | body   |
| value          | string    | true     | 123456789abcdef67890123456789abcdef67890 | fingerprint                                                 | body   |
```
POST /clouddns/record-sshfp HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```
```json
{
   "name":"host.example.cz.",
   "domainId":"vy6mzJerQTSOy8ur-Iakfw",
   "type":"SSHFP",
   "algorithm": 2,
   "algorithmType": 1,
   "value":"123456789abcdef67890123456789abcdef67890"
}
```

#### Example call to add a TLSA record


| PARAMETER NAME   | DATA TYPE | REQUIRED | EXAMPLE                                                                | NOTES                                                                                                                         | SOURCE |
| ---------------- | --------- | -------- | ---------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- | ------ |
| accessToken      | string    | true     | oenCToMxTrSEgmHShDJLew                                                 |                                                                                                                               | header |
| domainId         | string    | true     | vy6mzJerQTSOy8ur-Iakfw                                                 |                                                                                                                               | body   |
| name             | string    | true     | _443._tcp.example.com.                                                 |                                                                                                                               | body   |
| type             | string    | true     | TLSA                                                                   |                                                                                                                               | body   |
| certificateUsage | string    | true     | 1                                                                      | `0: CA constraint`, `1: Service certificate constraint`, `2: Trust Anchor Assertion`, `3: Domain issued certificate`          | body   |
| selector         | string    | true     | 1                                                                      | `0: select entire certificate`, `1: select just the public key`                                                               | body   |
| matchingType     | string    | true     | 1                                                                      | `0: entire information in certificate association data`, `1: SHA-256 hash of selected data, 1: SHA-512 hash of selected data` | body   |
| value            | string    | true     | 0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef012345 |                                                                                                                               | body   |
```
POST /clouddns/record-tlsa HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```
```json
{
   "name":"_443._tcp.example.cz.",
   "domainId":"vy6mzJerQTSOy8ur-Iakfw",
   "type":"SSHFP",
   "certificateUsage": 1,
   "selector": 1,
   "matchingType": 1,
   "value":"123456789abcdef67890123456789abcdef67890"
}
```

## Deleting and restoring a DNS record

You can delete DNS records using `DELETE /clouddns/record/{id}` endpoint.
The only input is the record's id from `lastDomainRecordList` field returned by `GET /clouddns/domain/{id}`. 
If deletion has not been published yet, you can restore the record by calling `PUT /clouddns/record/{id}/recover`.


## Publishing DNS records changes

`PUT /clouddns/domain/{id}/publish` endpoint serves for publishing newly added, changed or deleted DNS records.
`GET /clouddns/domain/{id}` endpoint returns `lastDomainVersionRecordsChanged` attribute.
If the attribute is `true`, there are unpublished changes in DNS records.

#### PUT /clouddns/domain/{id}/publish

| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLES               | SOURCE |
| -------------- | --------- | -------- | ---------------------- | ------ |
| accessToken    | string    | true     | oenCToMxTrSEgmHShDJLew | header |
| id             | string    | true     | HO0s88QGT869dEaMDEAFoQ | path   |
| soaTtl         | int       | false    | 172800                 | body   |

#### Example call to publish DNS records

```
POST /clouddns/domain/HO0s88QGT869dEaMDEAFoQ/publish HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```
```json
{
   "soaTtl": 86400
}
```

## Batch update of DNS records

By calling `PUT /clouddns/domain/{id}/records` you can update all DNS records of a domain.
As input, send a list of all DNS records that should be published. The system validates the input, updates all DNS records served in input and publishes the changes.
There is no need to call `PUT /clouddns/domain/{id}/publish` after a batch update.

#### PUT /domain/{id}/records

| PARAMETER NAME | DATA TYPE                                 | REQUIRED | EXAMPLE                                                 | SOURCE |
| -------------- | ----------------------------------------- | -------- | ------------------------------------------------------- | ------ |
| accessToken    | string                                    | true     | oenCToMxTrSEgmHShDJLew                                  | header |
| id             | string                                    | true     | HO0s88QGT869dEaMDEAFoQ                                  | path   |
| soaTtl         | int                                       | true     | 86400                                                   | body   |
| domainRecord   | array of [DomainRecordJsonObject] objects | true     | [{"name":"example.cz.","type":"A","content":"8.8.8.8"}] | body   |


#### DomainRecordJsonObject

| PARAMETER NAME | DATA TYPE | REQUIRED | EXAMPLE     | NOTES                                                                    |
| -------------- | --------- | -------- | ----------- | ------------------------------------------------------------------------ |
| name           | string    | true     | example.cz. |                                                                          |
| type           | string    | true     | A           |  `A`, `AAAA`, `CAA`, `CNAME`, `MX`, `SOA`, `SRV`, `SSHFP`, `TLSA`, `TXT` |
| content        | string    | true     | 8.8.8.8     |                                                                          |


#### Example call for batch update of DNS records

```
PUT /clouddns/domain/HO0s88QGT869dEaMDEAFoQ/records HTTP/2
Host: admin.vshosting.cloud
Content-Type: application/json
Authorization: Bearer oenCToMxTrSEgmHShDJLew
Cache-Control: no-cache
```
```json
{
   "soaTtl": 86400,
   "domainRecord": [
      {
         "name": "example.cz.",
         "type": "A",
         "content": "8.8.8.8"
      },
      {
         "name": "example.cz.",
         "type": "MX",
         "content": "10 mx.vshosting.cloud"
      }
   ]
}
```

## Activation and management of DNSSEC

You can activate DNSSEC via `PUT /clouddns/domain/{id}/dnssec-activate`. 

Following that, you retrieve DNSSEC public key by calling `GET /clouddns/domain/{id}/public-key`.

For .cz domains, you can publish the public key directly using `PUT /clouddns/domain/{id}/publish-public-key`. The change at your domain registrar should take place within 14 days.

`PUT /clouddns/domain/{id}/dnssec-reg-status` enpoint returns the current status of DNSSEC publication. It returns a domain object that contains a `dnssec` object with the following fields:

```json
{
   "dnsSec":{
      "enabledDate": <dateTime>,
      "published": <bool>,
      "publishedDate": <dateTime>,
      "regStatus": <string>,
      "regStatusDate": <dateTime>
   }
}
```

| Attribute     | Meaning                                  |
| ------------- | ---------------------------------------- |
| enabledDate   | date of DNSSEC activation                |
| published     | marks whether the key has been published |
| publishedDate | date of DNSSEC publication               |
| regStatus     | state of DNSSEC publication              |
| regStatusDate | date of the last DNSSEC check            |

#### Various states of `regStatus`

| regStatus              | Meaning                                                                |
| ---------------------- | ---------------------------------------------------------------------- |
| UNKNOWN                | DNSSEC public key has not been verified yet                            |
| REG_NO_DNSSEC_KEY      | No public key found at your registrar                                  |
| REG_INVALID_DNSSEC_KEY | Public key at your registrar does not match the DNSSEC key in CloudDNS |
| OK                     | Public key has been successfully verified, DNSSEC is active            |


Publication of DNSSEC key can be cancelled by calling `PUT /clouddns/domain/{id}/revoke-public-key`.

DNSSEC can be deactivated via `PUT /clouddns/domain/{id}/dnssec-deactivate`.
