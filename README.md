# Dbp Relay SubLibrary Bundle

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

## Installation

### Step 1.

Copy this bundle to `./bundles/relay-sublibrary-bundle`

### Step 2.

Enable this bundle in `./config/bundles.php` by adding this element to the array returned:

```php
...
    return [
        ...
        Dbp\Relay\SublibraryBundle\SublibraryBundle::class => ['all' => true],
    ];
}
```

### Step 3

Add this bundle to `./symfony.lock`:

```json
...
    "dbp/relay-sublibrary-bundle": {
        "version": "@dev"
    },
...
```
