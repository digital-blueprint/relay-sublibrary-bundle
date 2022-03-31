# Dbp Relay SubLibrary Bundle

[GitLab](https://gitlab.tugraz.at/dbp/library/dbp-relay-sublibrary-bundle) | [Packagist](https://packagist.org/packages/dbp/relay-sublibrary-bundle)

DISCLAIMER: This bundle is mainly for internal use of the Graz University of Technology (TU Graz)
and may need some adjustments for other use cases.

This Symfony 4.4 bundle provides API endpoints for

- assigning a call number to a book
- borrowing a book from the sublibrary
- returning a book to the sublibrary
- extending a loan period for a book
- showing sublibrary's book list
- showing sublibrary's current loans
- showing sublibrary's current book orders

for the API-Gateway.

## Prerequisites

- API Gateway with openAPI/Swagger
- Alma backend access with special configuration (for analytics)
- LDAP server to fetch the Alma User ID

## Bundle installation

You can install the bundle directly from [packagist.org](https://packagist.org/packages/dbp/relay-sublibrary-bundle).

```bash
composer require dbp/relay-sublibrary-bundle
```

## Integration into the API Server

* Add the necessary bundles to your `config/bundles.php`:

```php
...
Dbp\Relay\SublibraryBundle\DbpRelaySublibraryBundle::class => ['all' => true],
Dbp\Relay\CoreBundle\DbpRelayCoreBundle::class => ['all' => true],
];
```

* Run `composer install` to clear caches

## Configuration

The bundle has configuration values that you can specify in your app, either by hardcoding it,
or by referencing an environment variable.

For this create `config/packages/dbp_relay_sublibrary.yaml` in the app with the following
content:

```yaml
dbp_relay_sublibrary:
  api_url: '%env(ALMA_API_URL)%'
  api_key: '%env(ALMA_API_KEY)%'
  analytics_api_key: '%env(ALMA_ANALYTICS_API_KEY)%'
  readonly: '%env(bool:ALMA_READONLY)%'
  ldap:
    host: '%env(LDAP_HOST)%'
    base_dn: '%env(LDAP_BASE_DN)%'
    username: '%env(LDAP_USER)%'
    password: '%env(LDAP_PASS)%'
    encryption: 'simple_tls'
    attributes:
      identifier: '%env(LDAP_ATTRIBUTE_IDENTIFIER)%'
      alma_user_id: '%env(LDAP_ATTRIBUTE_ALMA_USER_ID)%'
```

Your `.env` file should then contain the following environment variables you need to configure the bundle:

```dotenv
###> dbp/relay-sublibrary-bundle ###
ALMA_API_URL=https://api-eu.hosted.exlibrisgroup.com/almaws/v1
ALMA_API_KEY=
ALMA_ANALYTICS_API_KEY=
ALMA_READONLY=
LDAP_HOST=directory.yourdomain.at
LDAP_BASE_DN=o=dbp
LDAP_USER=user
LDAP_PASS=pass
LDAP_ATTRIBUTE_IDENTIFIER=cn
LDAP_ATTRIBUTE_ALMA_USER_ID=CO-ALMA-PATRON-ID
###< dbp/relay-sublibrary-bundle ###
```

If you were using the [DBP API Server Template](https://gitlab.tugraz.at/dbp/relay/dbp-relay-server-template)
as template for your Symfony application, then the configuration files should have already been generated for you.

For more info on bundle configuration see <https://symfony.com/doc/current/bundles/configuration.html>.
