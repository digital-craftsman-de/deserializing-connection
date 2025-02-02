# Upgrade guide

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
