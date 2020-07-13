# Api-ALMA-Bundle

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

Copy this bundle to `./bundles/api-alma-bundle`

### Step 2.

Enable this bundle in `./config/bundles.php` by adding this element to the array returned:

```php
...
    return [
        ...
        BP\API\AlmaBundle\AlmaBundle::class => ['all' => true],
    ];
}
```

### Step 3.

Add the Entities of this bundle to `./config/packages/api_platform.yaml`:

```yaml
...
 	        paths:
                ...
	            - '%kernel.project_dir%/vendor/dbp/api-alma-bundle/src/Entity'
        exception_to_status:
...
```

### Step 4

Hide some Entities from exposure by api_platform by adding them to `./src/Swagger/SwaggerDecorator.php`:

```php
...
        $pathsToHide = [
            "/parcel_deliveries/{id}",
            "/delivery_statuses/{id}",
            "/order_items/library_book_order_items/{id}",
            ...
        ];

```

### Step 5

Add this bundle to `./symfony.lock`:

```json
...
    "dbp/api-alma-bundle": {
        "version": "@dev"
    },
...
```
