# Changelog

## v0.4.4

* Add support for api-platform 3.2

## v0.4.3

* Add support for doctrine/collections v2

## v0.4.2

* Same workaround as 0.3.2 but for book offers

## v0.4.0

* Drop support for Symfony 5

## v0.3.2

* Work around regression in 0.3.1 which break some fields in nested entities in the book loan endpoints

## v0.3.1

* Fix the accepted content type for the new PATCH method for compatibility with the upcoming ApiPlatform upgrade

## v0.3.0

* Replace PUT with PATCH for upcoming ApiPlatform upgrade and standard compliant Http PUT

# v0.2.4

* Add support for Symfony 6

# v0.2.3

* Drop support for PHP 7.4/8.0

# v0.2.2

* Drop support for PHP 7.3

# v0.1.29

* Port from adldap2/adldap2 to directorytree/ldaprecord
* Fix some regressions for "/book-offers/{identifier}/loans"
  and "/book-offers/{identifier}/return" introduced in 0.1.27

# v0.1.28

* Finish api-platform metadata system porting

# v0.1.27

* More api-platform metadata system porting

# v0.1.26

* Partial port to the new api-platform metadata system
* Added a new SublibraryInterface so the connector doesn't need to depend on internal classes

# v0.1.25

* Adjust for Alma API changes. This fixes the loan collection endpoint.

# v0.1.24

* Support kevinrob/guzzle-cache-middleware v5

# v0.1.21

* Fix some API performance issues with api-platform 2.7

# v0.1.20

* Use the global "cache.app" adapter for caching instead of always using the filesystem adapter

# v0.1.19

* Update to api-platform 2.7

# v0.1.12

* tests: don't fail if symfony/dotenv is installed

# v0.1.11

* Fix budget not showing properly. Caused by February 2023 update which renames fund fields.

# v0.1.9

* Fix issues with book loaning and shelving (issues appeared after January 2023 update)

# v0.1.8

* Fix list book order bug due to field name change

# v0.1.7

* Fix extraction of ALMA API error messages in some cases
* Add health checks for the ALMA APIs and the LDAP connection
