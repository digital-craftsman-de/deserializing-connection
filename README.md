# Get DTOs directly from the database

A Symfony bundle to get DTOs directly from the database. It's a simple and efficient way to get data from the database and convert it into DTOs without to much noise in your code.  

As it's a central part of an application, it's tested thoroughly (including mutation testing).

[![Latest Stable Version](https://img.shields.io/badge/stable-0.5.1-blue)](https://packagist.org/packages/digital-craftsman/deserializing-connection)
[![PHP Version Require](https://img.shields.io/badge/php-8.3|8.4-5b5d95)](https://packagist.org/packages/digital-craftsman/deserializing-connection)
[![codecov](https://codecov.io/gh/digital-craftsman-de/deserializing-connection/branch/main/graph/badge.svg?token=BL0JKZYLBG)](https://codecov.io/gh/digital-craftsman-de/deserializing-connection)
![Packagist Downloads](https://img.shields.io/packagist/dt/digital-craftsman/deserializing-connection)
![Packagist License](https://img.shields.io/packagist/l/digital-craftsman/deserializing-connection)

## Installation and configuration

Install package through composer:

```shell
composer require digital-craftsman/deserializing-connection
```

> ⚠️ This bundle can be used (and is being used) in production, but hasn't reached version 1.0 yet. Therefore, there will be breaking changes between minor versions. I'd recommend that you require the bundle only with the current minor version like `composer require digital-craftsman/deserializing-connection:0.5.*`. Breaking changes are described in the releases and [the changelog](./CHANGELOG.md). Updates are described in the [upgrade guide](./UPGRADE.md).

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

### Result transformers

There are cases where you're not able to do everything in the SQL query. For example when you want to calculate a value based on data of the environment or information that is only available on runtime. In those cases, you can use result transformers to run callbacks before the data is deserialized into the DTO.

This can look like this for the following DTO:

```php
final readonly class User
{
    public function __construct(
        public UserId $userId,
        public string $name,
        public string $companyLink,
    ) {
    }
}
```

```php
$this->deserializingConnection->getOne(
    sql: <<<'SQL'
        SELECT
            user_id AS "userId",
            name,
            companyLink
        FROM
            `user`
        WHERE user_id = :userId
        SQL,
    class: ReadModel\User::class,
    parameters: [
        'userId' => $userId,
    ],
    decoderTypes: [
        'company' => DecoderType::JSON,
    ],
    resultTransformers: [
        ResultTransformer::toTransformAndRename(
            key: 'companyLink',
            denormalizeResultToClass: CompanyLink::class,
            transformer: fn(CompanyLink $companyLink) => $this->router->generate(
                'company_show',
                [
                    'companyId' => $companyLink->companyId,
                ],
            ),
            isTransformedResultNormalized: false,
            renameTo: 'link',
        ),
    ],
);
```

The available variants of `ResultTransformer` are:

- `toTransform`
- `toRename`
- `toTransformAndRename`

The "rename" variants are simply renaming the property into the supplied name.

Additional documentation for the key (how it can be used in a multi level result and for arrays) can be found in the [`ResultTransformerKey.php class`](./src/Serializer/DTO/ResultTransformerKey.php). 

### Normalizers

For easier normalization, use the [`digital-craftsman/self-aware-normalizers`](https://github.com/digital-craftsman-de/self-aware-normalizers) package which is required by this package.

### Doctrine types

For easier doctrine types, use the [`digital-craftsman/self-aware-normalizers`](https://github.com/digital-craftsman-de/self-aware-normalizers) package which is required by this package.

## Additional documentation

- [Changelog](./CHANGELOG.md)
- [Upgrade guide](./UPGRADE.md)
