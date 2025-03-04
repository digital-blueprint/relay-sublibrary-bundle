# Dbp Relay SubLibrary Bundle

[GitHub](https://github.com/digital-blueprint/relay-sublibrary-bundle) |
[Packagist](https://packagist.org/packages/dbp/relay-sublibrary-bundle) |
[Frontend Application](https://github.com/digital-blueprint/sublibrary-app)

[![Test](https://github.com/digital-blueprint/relay-sublibrary-bundle/actions/workflows/test.yml/badge.svg)](https://github.com/digital-blueprint/relay-sublibrary-bundle/actions/workflows/test.yml)

The sublibrary bundle provides an API layer for library management tasks on top
of the official ALMA API. It enables multiple sub-organizations to manage their
own library resources (books, holdings, and budgets) independently, while
operating through a single, shared ALMA API key. This approach ensures that each
sub-organization can only access and manage their own resources, maintaining
separation of concerns.

There is a corresponding frontend application that uses this API at [Sublibrary Frontend Application](https://github.com/digital-blueprint/sublibrary-app).

For more information see the [Documentation](./docs/README.md).

## Bundle installation

You can install the bundle directly from [packagist.org](https://packagist.org/packages/dbp/relay-sublibrary-bundle).

```bash
composer require dbp/relay-sublibrary-bundle
```
