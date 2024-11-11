# Upgrade guide

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
