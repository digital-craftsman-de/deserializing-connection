# Deserializing connection

Write plain SQL, get typed DTOs back. One method call executes the query, decodes the database values (like JSON or booleans) and denormalizes the result into your read models through the Symfony serializer – without an ORM, without hydration code and without losing any type information.

[![Latest Stable Version](https://img.shields.io/badge/stable-1.0.0-blue)](https://packagist.org/packages/digital-craftsman/deserializing-connection)
[![PHP Version Require](https://img.shields.io/badge/php-8.5|8.6-5b5d95)](https://packagist.org/packages/digital-craftsman/deserializing-connection)
[![codecov](https://codecov.io/gh/digital-craftsman-de/deserializing-connection/branch/main/graph/badge.svg?token=BL0JKZYLBG)](https://codecov.io/gh/digital-craftsman-de/deserializing-connection)
![Packagist Downloads](https://img.shields.io/packagist/dt/digital-craftsman/deserializing-connection)
![Packagist License](https://img.shields.io/packagist/l/digital-craftsman/deserializing-connection)

## Why?

Read models are usually built in one of two ways: through an ORM, which loads full entities and a lot of data you don't need, or through raw SQL, which leaves you with untyped arrays and a lot of mapping code. This bundle combines the best of both:

- **Full power of SQL.** Use CTEs, window functions, `jsonb_agg` and everything else your database offers. Load a complete nested read model with a single query instead of N+1 queries.
- **No mapping code.** No `json_decode`, no casting of strings to integers or booleans, no `new User(...)` with array lookups. You describe what you need, the bundle does the rest.
- **Type safe.** All methods are generic. `getOne(class: User::class)` returns a `User` – Psalm and PHPStan know that, and so does your IDE.
- **Transparent.** A Symfony profiler panel shows every query with its parameters inlined (ready to copy and run), the result after every step and the duration of every step.
- **Reliable.** It's a central part of an application, so it's tested thoroughly against a real PostgreSQL database, including mutation testing with 100% MSI.

### Before and after

Without this bundle:

```php
$row = $this->connection->fetchAssociative(
    'SELECT user_id, name, accessible_projects FROM users WHERE user_id = :userId',
    ['userId' => (string) $userId],
);

if ($row === false) {
    throw new UserNotFound($userId);
}

$user = new User(
    userId: UserId::fromString($row['user_id']),
    name: $row['name'],
    accessibleProjects: ProjectIdList::fromArray(
        json_decode($row['accessible_projects'], true, 512, JSON_THROW_ON_ERROR),
    ),
);
```

With this bundle:

```php
$user = $this->deserializingConnection->getOne(
    sql: <<<'SQL'
        SELECT
            user_id AS "userId",
            name,
            accessible_projects AS "accessibleProjects"
        FROM users
        WHERE user_id = :userId
        SQL,
    class: User::class,
    parameters: [
        'userId' => $userId,
    ],
    decoderTypes: [
        'accessibleProjects' => DecoderType::JSON,
    ],
    otherwiseThrow: static fn () => new UserNotFound($userId),
);
```

The DTO itself doesn't need any mapping code. Only value objects like `UserId` define once how they are normalized (see [Normalizers](#normalizers)), and that definition is reused for every query, the API and the database.

## Installation

Requires PHP 8.5 or 8.6, Symfony 7.4 or 8 and Doctrine DBAL 4.

```shell
composer require digital-craftsman/deserializing-connection
```

The services are registered automatically and can be injected through autowiring. The bundle follows [semantic versioning](https://semver.org). Updates are described in the [upgrade guide](./UPGRADE.md).

## How it works

Every method of the `DeserializingConnection` runs the same pipeline:

1. **Query** – The SQL is executed through the Doctrine DBAL connection with the given parameters and parameter types.
2. **Decode** – Database values are converted into PHP types through [decoder types](#decoder-types) (e.g. a JSON string into an array).
3. **Transform** – Optional [result transformers](#result-transformers) rename keys or compute values that can't be computed in SQL.
4. **Denormalize** – The result is denormalized into the given class through the Symfony serializer.

## Usage

### Deserializing connection

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

The column names in the SQL must match the keys the denormalizer expects. Use aliases like `user_id AS "userId"` for this.

These are the available methods:

| Method | Returns | When nothing is found |
|---|---|---|
| `getOne` | One object | Throws `ElementNotFound` (or a custom exception) |
| `findOne` | One object or `null` | Returns `null` |
| `getOneFromSingleValue` | One object from a single column | Throws `ElementNotFound` (or a custom exception) |
| `findOneFromSingleValue` | One object from a single column or `null` | Returns `null` |
| `findArray` | A list of objects (or an array indexed by a custom key) | Returns an empty array |
| `findGenerator` | A generator yielding the objects | Yields nothing |

#### Custom exceptions

By default, `getOne` and `getOneFromSingleValue` throw an `ElementNotFound` exception when no result is found. Supply a callable through `otherwiseThrow` to throw your own exception instead:

```php
$user = $this->deserializingConnection->getOne(
    sql: ...,
    class: User::class,
    parameters: [
        'userId' => $userId,
    ],
    otherwiseThrow: static fn () => new UserNotFound($userId),
);
```

#### Single values

Use `getOneFromSingleValue` or `findOneFromSingleValue` when the denormalization needs a single value instead of an associative array, for example for value objects:

```php
$duration = $this->deserializingConnection->getOneFromSingleValue(
    sql: <<<'SQL'
        SELECT duration
        FROM projects
        WHERE project_id = :projectId
        SQL,
    class: Duration::class,
    parameters: [
        'projectId' => $projectId,
    ],
);
```

#### Lists

`findArray` returns a list of objects. With `indexedBy`, the list is indexed by a key of your choice:

```php
$usersById = $this->deserializingConnection->findArray(
    sql: 'SELECT user_id AS "userId", name, accessible_projects AS "accessibleProjects" FROM users',
    class: User::class,
    decoderTypes: [
        'accessibleProjects' => DecoderType::JSON,
    ],
    indexedBy: static fn (User $user): string => (string) $user->userId,
);
```

`findGenerator` yields the objects one after another. The query is only executed when the generator is consumed.

#### Unused results

All methods of the `DeserializingConnection` and the `DecodingConnection` are marked with `#[\NoDiscard]`. When you call one of them without using the result, PHP emits a warning – most likely a bug, especially for the generator, which wouldn't even run the query. To check whether a row exists, use `DecodingConnection->fetchBool` with an `EXISTS` query instead of `getOne`. If you really don't need the result, use the `(void)` cast.

### Decoder types

Most database drivers return everything that isn't a string, integer or boolean as a string. Decoder types convert those values into the PHP types the denormalizer expects. Define them through `decoderTypes` with the column name as key:

| Decoder type | Result | Result for `null` |
|---|---|---|
| `BOOL` | `bool` | `false` |
| `NULLABLE_BOOL` | `bool` | `null` |
| `INT` | `int` | `false` |
| `NULLABLE_INT` | `int` | `null` |
| `FLOAT` | `float` | `false` |
| `NULLABLE_FLOAT` | `float` | `null` |
| `JSON` | `array` | Exception |
| `NULLABLE_JSON` | `array` | `null` |
| `JSON_WITH_EMPTY_ARRAY_ON_NULL` | `array` | `[]` (useful for `jsonb_agg`, which returns `null` instead of an empty array) |

#### Nested values

Decoder types can also target values nested within a JSON column. Levels are separated with a dot `.` and all items of a list are targeted with `*`. This way a complete nested read model can be loaded with one query:

```php
$companies = $this->deserializingConnection->findArray(
    sql: <<<'SQL'
        SELECT
            companies.name,
            (
                SELECT jsonb_agg(jsonb_build_object(
                    'name', projects.name,
                    'timeEntries', projects.time_entries
                ))
                FROM projects
                WHERE projects.company_id = companies.company_id
            ) AS projects
        FROM companies
        SQL,
    class: CompanyWithProjects::class,
    decoderTypes: [
        'projects' => DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL,
        'projects.*.timeEntries.*.hours' => DecoderType::NULLABLE_FLOAT,
    ],
);
```

Parent values are always decoded before the values nested in them, independent of the order in which they are defined. Missing keys and `null` values on the way to the nested value are ignored.

### Result transformers

Some values can't be computed in SQL, for example because they depend on services or information only available at runtime. Result transformers run callbacks on the decoded result before it's denormalized.

Given the following DTO:

```php
final readonly class Company
{
    public function __construct(
        public CompanyId $companyId,
        public CompanyName $name,
        public string $url,
    ) {
    }
}
```

The company id is selected a second time as `url`, denormalized into a `CompanyId` and transformed into the URL of the company:

```php
$company = $this->deserializingConnection->getOne(
    sql: <<<'SQL'
        SELECT
            company_id AS "companyId",
            name,
            company_id AS url
        FROM companies
        WHERE company_id = :companyId
        SQL,
    class: Company::class,
    parameters: [
        'companyId' => $companyId,
    ],
    resultTransformers: [
        ResultTransformer::toTransform(
            key: 'url',
            denormalizeResultToClass: CompanyId::class,
            transformer: fn (CompanyId $companyId): string => $this->router->generate(
                'company_show',
                [
                    'companyId' => (string) $companyId,
                ],
            ),
            isTransformedResultNormalized: false,
        ),
    ],
);
```

There are three variants:

| Factory method | Effect |
|---|---|
| `ResultTransformer::toTransform` | Replaces the value with the result of the transformer. |
| `ResultTransformer::toRename` | Renames the key. |
| `ResultTransformer::toTransformAndRename` | Both of the above. |

The parameters of the transformer:

- `denormalizeResultToClass` – When a class is given, the value is denormalized into it before it's passed to the transformer. `null` values aren't denormalized.
- `transformer` – A closure receiving the value, the array of the current level and the complete result: `fn (mixed $value, array $resultOfLevel, array $result): mixed`.
- `isTransformedResultNormalized` – When `true`, the returned object is normalized again (useful when the transformer returns an object which is denormalized into the DTO afterward).

The key uses the same syntax as the nested decoder types (e.g. `user.projects.*.name`). It's always relative to a single row, so with `findArray` you don't need to start the key with `*`. Additional documentation can be found in the [`ResultTransformerKey`](./src/Serializer/DTO/ResultTransformerKey.php) class.

### Decoding connection

When you only need scalar values or arrays, use the underlying `DecodingConnection`. It wraps the Doctrine DBAL connection and supports the same decoder types:

| Method | Returns |
|---|---|
| `fetchOne` | The first value of the first row (decoded with an optional decoder type) or `null` |
| `fetchAssociative` | The first row as an associative array or `null` |
| `fetchAllAssociative` | All rows as associative arrays (optionally indexed by a column through `indexedBy`) |
| `fetchFirstColumn` | The values of the first column |
| `fetchInt` | An `int`, throws an exception when there isn't exactly one result or it isn't an integer |
| `fetchBool` | A `bool`, throws an exception when there isn't exactly one result or it isn't a boolean |

### Normalizers

The denormalization is done through the Symfony serializer. DTOs like the `Company` above don't need any configuration: the serializer creates them through their constructor and denormalizes every parameter depending on its type.

Only value objects need to define how they are normalized and denormalized. The easiest way is the [`digital-craftsman/self-aware-normalizers`](https://github.com/digital-craftsman-de/self-aware-normalizers) package, which is required by this bundle. Implement one of its interfaces and the value object is denormalized automatically wherever it's used – in every DTO and every query:

```php
final readonly class CompanyName implements StringNormalizable
{
    public function __construct(
        public string $value,
    ) {
        if (trim($value) === '') {
            throw new CompanyNameMustNotBeEmpty();
        }
    }

    public static function denormalize(string $data): self
    {
        return new self($data);
    }

    public function normalize(): string
    {
        return $this->value;
    }
}
```

There are interfaces for `string`, `int`, `float`, `bool` and `array` values. The same value objects can also be used as Doctrine types. Ids like `CompanyId` can be created through [`digital-craftsman/ids`](https://github.com/digital-craftsman-de/ids), which builds on the same interfaces.

Any other normalizer registered in the Symfony serializer works as well.

### Symfony profiler

When the Symfony profiler is enabled (through `symfony/web-profiler-bundle`), every usage of the `DeserializingConnection` and the `DecodingConnection` is shown in a separate "Deserializing Connection" panel. For every usage it contains:

- The runnable query with all parameters inlined (highlighted like in the Doctrine panel, including a copy button).
- The raw result, the result after decoding, the result after the result transformers and the denormalized objects, each with the duration of the step.
- The configuration (class, decoder types, result transformers), the query duration, the location of the usage and a potential exception including the step in which it happened.

The runnable query is rendered with the same parser that Doctrine DBAL uses to expand parameters. Therefore, string literals, comments, casts (`::`), array parameters and escaped question marks (`??` for the JSONB operators of PostgreSQL) are handled the same way as when the query is executed. All values are inlined as string literals, which is how PDO sends parameters to PostgreSQL.

Without the profiler, a null tracer is used, so there is no overhead in production.

Profiling can be disabled (similar to the `profiling` option of the Doctrine DBAL connections), for example when the results are too large:

```php
// config/packages/deserializing_connection.php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'deserializing_connection' => [
        'profiling' => false,
    ],
]);
```

In this case, the null tracer is used, and the panel isn't shown.

## Additional documentation

- [Changelog](./CHANGELOG.md)
- [Upgrade guide](./UPGRADE.md)
