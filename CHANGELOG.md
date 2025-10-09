## Changelog

## Unreleased

## v0.5.9

- Fix some minor phpstan issues

## v0.5.8

- Add support for kevinrob/guzzle-cache-middleware v7
- Some minor cleanups

## v0.5.7

- Adjust for Alma Analytics API changes in the latest ALMA release

## v0.5.6

- Workaround erroneous phpstan type error
- Avoid api-platform boot kernel deprecation warning

## v0.5.5

- `/sublibraries` now uses the "Accept-Language" header to determine the
  language of the response. The "lang" query parameter is deprecated and will be
  removed in a future version.
- Drop support for api-platform 3.4


## v0.5.4

- Added support for api-platform 4.1+ (in addition to 3.4)
- Implemented `GET /sublibrary/sublibraries/{identifier}`
- For `GET /sublibrary/sublibraries` the `libraryManager` query parameter is no
  longer required and is deprecated. It defaults to the authenticated user now.

## v0.5.3

- Drop support for the library connector and depend on the base organization
  directly instead. All the features of the base organization connector have
  been merged into this bundle.

## v0.5.2

- Drop support for PHP 8.1
- Drop support for api-paltform 3.3
- config: Add new optional entries for configuring the person local data attribute names

## v0.5.1

- Fix various type error regressions introduced with 0.5.0

## v0.5.0

- (breaking) In the bundle config authorization.policies is replaced with
  authorization.roles
- (breaking) In the bundle config the "api_url" now only takes the API base URL, so the trailing "/almaws/v1" has to be removed.
- (breaking) "SublibraryBundle\Entity\Sublibrary" is no longer public, use SublibraryInterface instead
- New bundle config section "analytics_reports" for configuring the analytics reports to be used internally.
- Drop support for api-platform 3.2

## v0.4.14

- Book: datePublished is now a freeform text instead of a ISO date time string

## v0.4.13

- alma: adjust column name after book order analytics report changes
- Drop psalm support

## v0.4.12

- Add support for kevinrob/guzzle-cache-middleware v6
- Port to phpstan v2
- Test with PHP 8.4

## v0.4.11

- Fix various APIs returning empty results after the Alma 2024-11 release and the API changes it brought.
- Drop support for api-platform v2

## v0.4.10

- Update usage of DummyPersonProvider in tests

## v0.4.9

- Fix tests with api-platform 3.3.7

## v0.4.8

- Fix `/sublibrary/book-loans` only returning up to 100 loans, 0.4.7 regression
- Speed up `/sublibrary/book-loans` a bit more in case all loans are for the same user

## v0.4.7

- Stop depending on the "tugFunctions" local person data attribute
- Speed up the `/sublibrary/book-loans` endpoint a bit in case a user has many loans

## v0.4.6

- Port to PHPUnit 10
- Port from doctrine annotations to PHP attributes
- Add support for api-platform 3.3

## v0.4.5

- Fix some minor psalm issues

## v0.4.4

- Add support for api-platform 3.2

## v0.4.3

- Add support for doctrine/collections v2

## v0.4.2

- Same workaround as 0.3.2 but for book offers

## v0.4.0

- Drop support for Symfony 5

## v0.3.2

- Work around regression in 0.3.1 which break some fields in nested entities in the book loan endpoints

## v0.3.1

- Fix the accepted content type for the new PATCH method for compatibility with the upcoming ApiPlatform upgrade

## v0.3.0

- Replace PUT with PATCH for upcoming ApiPlatform upgrade and standard compliant Http PUT

## v0.2.4

- Add support for Symfony 6

## v0.2.3

- Drop support for PHP 7.4/8.0

## v0.2.2

- Drop support for PHP 7.3

## v0.1.29

- Port from adldap2/adldap2 to directorytree/ldaprecord
- Fix some regressions for "/book-offers/{identifier}/loans"
  and "/book-offers/{identifier}/return" introduced in 0.1.27

## v0.1.28

- Finish api-platform metadata system porting

## v0.1.27

- More api-platform metadata system porting

## v0.1.26

- Partial port to the new api-platform metadata system
- Added a new SublibraryInterface so the connector doesn't need to depend on internal classes

## v0.1.25

- Adjust for Alma API changes. This fixes the loan collection endpoint.

## v0.1.24

- Support kevinrob/guzzle-cache-middleware v5

## v0.1.21

- Fix some API performance issues with api-platform 2.7

## v0.1.20

- Use the global "cache.app" adapter for caching instead of always using the filesystem adapter

## v0.1.19

- Update to api-platform 2.7

## v0.1.12

- tests: don't fail if symfony/dotenv is installed

## v0.1.11

- Fix budget not showing properly. Caused by February 2023 update which renames fund fields.

## v0.1.9

- Fix issues with book loaning and shelving (issues appeared after January 2023 update)

## v0.1.8

- Fix list book order bug due to field name change

## v0.1.7

- Fix extraction of ALMA API error messages in some cases
- Add health checks for the ALMA APIs and the LDAP connection
