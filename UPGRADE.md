# Upgrade guide

## From 0.7.* to 0.8.0

### Breaking change: Keys of `decoderTypes` must not contain dots anymore

The keys of `decoderTypes` now support nested values with the same syntax as the result transformers (e.g. `projects.*.timeEntries.*.hours`). Therefore, a dot (`.`) in a key is interpreted as a separator between levels. A key like `project.name` no longer targets a column named `project.name`, but the value `name` within the column `project`. Additionally, keys must not start or end with `*` anymore.

If you're using column names containing dots, rename them in your SQL (e.g. `SELECT name AS "projectName"` instead of `SELECT name AS "project.name"`) and adapt the keys of `decoderTypes` accordingly.

### Optional: Replace result transformers that only decode values

Result transformers that only convert types of nested values can be replaced with nested decoder types. So instead of:

```php
resultTransformers: [
    ResultTransformer::toTransform(
        key: 'projects.*.timeEntries.*.hours',
        denormalizeResultToClass: null,
        transformer: static fn (int | float | null $hours) => $hours !== null
            ? (float) $hours
            : null,
        isTransformedResultNormalized: false,
    ),
],
```

You can use:

```php
decoderTypes: [
    'projects' => DecoderType::JSON,
    'projects.*.timeEntries.*.hours' => DecoderType::NULLABLE_FLOAT,
],
```

## From 0.6.* to 0.7.0

### Dropped support for PHP 8.3

Update to at least PHP 8.4.

### Dropped support for Symfony 7.3 and below

Update to at least the LTS version 7.4.


## From 0.5.* to 0.6.0

Nothing to do.

## From 0.4.* to 0.5.0

Nothing to do.

## From 0.3.* to 0.4.0

Nothing to do.

## From 0.2.* to 0.3.0

### Replace normalizers and doctrine types

The own normalizers and doctrine types have been replaced with the [`digital-craftsman/self-aware-normalizers`](https://github.com/digital-craftsman-de/self-aware-normalizers) package. The structure and logic is identical at this point, so you just need to adapt the namespaces via search / replace:

Before:

```php
use DigitalCraftsman\DeserializingConnection\Serializer\ArrayNormalizable;
```

After:

```php
use DigitalCraftsman\SelfAwareNormalizers\Serializer\ArrayNormalizable;
```

## From 0.1.* to 0.2.0

### Rename parameter

Change name of parameter for `denormalize` method to `$data`.

Before:

```php
public static function denormalize(array $array): self
{
    return new self($array);
}
```

After:

```php
public static function denormalize(array $data): self
{
    return new self($data);
}
```
