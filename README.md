# Get DTOs directly from the database

A Symfony bundle to get DTOs directly from the database. It's a simple and efficient way to get data from the database and convert it into DTOs without to much noise in your code.  

As it's a central part of an application, it's tested thoroughly (including mutation testing).

[![Latest Stable Version](https://img.shields.io/badge/stable-0.1.0-blue)](https://packagist.org/packages/digital-craftsman/deserializing-connection)
[![PHP Version Require](https://img.shields.io/badge/php-8.3|8.4-5b5d95)](https://packagist.org/packages/digital-craftsman/deserializing-connection)
[![codecov](https://codecov.io/gh/digital-craftsman-de/deserializing-connection/branch/main/graph/badge.svg?token=BL0JKZYLBG)](https://codecov.io/gh/digital-craftsman-de/deserializing-connection)
![Packagist Downloads](https://img.shields.io/packagist/dt/digital-craftsman/deserializing-connection)
![Packagist License](https://img.shields.io/packagist/l/digital-craftsman/deserializing-connection)

## Installation and configuration

Install package through composer:

```shell
composer require digital-craftsman/deserializing-connection
```

> ⚠️ This bundle can be used (and is being used) in production, but hasn't reached version 1.0 yet. Therefore, there will be breaking changes between minor versions. I'd recommend that you require the bundle only with the current minor version like `composer require digital-craftsman/deserializing-connection:0.1.*`. Breaking changes are described in the releases and [the changelog](./CHANGELOG.md). Updates are described in the [upgrade guide](./UPGRADE.md).

## Usage

### Deserializing connection

When you want DTOs, read models or value objects, you can use the `DeserializingConnection` to get them directly from the database. 

Given the following DTO:

```php
final readonly class User
{
    public function __construct(
        public UserId $userId,
        public string $name,
        public ProjectIdList $accessibleProjects,
    ) {
    }
}
```

A call for one might look like this:

```php
$user = $this->deserializingConnection->getOne(
    sql: <<<'SQL'
        SELECT
            user_id AS "userId",
            name,
            accessible_projects AS "accessibleProjects"
        FROM
            `user`
        WHERE user_id = :userId
        SQL,
    class: User::class,
    parameters: [
        'userId' => $userId,
    ],
    decoderTypes: [
        'accessibleProjects' => DecoderType::JSON,
    ],
);
```

These are the offered methods:

- `getOne` to return one object or an exception when no result is found.
- `findOne` like `getOne`, but returns `null` when no result is found.
- `findArray` to return an array of objects.
- `findGenerator` to return a generator that yields the objects.

### Decoding types

Part of the magic is the conversion from database types to PHP types. For example, when your SQL returns a JSON string, you usually need to convert it into an associative array prior to serialization. Here you just need to supply `decoderTypes` with the column name and the type of decoder you want to use. There are utilities that can handle nullable values or create a empty array when a JSON returns null (relevant for `jsonb_agg` calls). These are the available decoder types which are all pretty self-explanatory:

- `INT`
- `NULLABLE_INT`
- `FLOAT`
- `NULLABLE_FLOAT`
- `JSON`
- `NULLABLE_JSON`
- `JSON_WITH_EMPTY_ARRAY_ON_NULL`

### Decoding connection

When you want to get a scalar value or do more complex stuff, you can use the underlying `DecodingConnection`. It offers the following methods:

- `fetchAssociative`
- `fetchAllAssociative`
- `fetchInt`
- `fetchBool`

`fetchInt` and `fetchBool` will throw custom exceptions when there are no values or they are not of the expected type.

### Normalizers

To make the normalization process easier, there are the following normalizers included:

- `StringNormalizableNormalizer`
- `IntNormalizableNormalizer`
- `FloatNormalizableNormalizer`
- `ArrayNormalizableNormalizer`

Additionally, there is an interface for each of the normalizers. Every class that implements one of the interfaces, will be automatically normalized to the respected type. This means putting the logic of how serialization of a class works within the class. That's not really seen as a good practice. In my experience, the data structure and the normalization need to be changed together. So, I like it better to have both in one place. I've used this approach in multiple large scale projects for years and haven't had a single issue with it yet. But your mileage may vary.

### Doctrine types

When using the normalizers, you can also use the same logic for doctrine types. Simply create a new doctrine type extending of one of the following types and register them:

- `StringNormalizableType`
- `StringEnumType`
- `IntNormalizableType`
- `FloatNormalizableType`
- `ArrayNormalizableType`

As an added bonus, this makes sure, that the structure is always the same no matter if you're using Doctrine to read from the data or a normalizer.

## Additional documentation

- [Changelog](./CHANGELOG.md)
- [Upgrade guide](./UPGRADE.md)
