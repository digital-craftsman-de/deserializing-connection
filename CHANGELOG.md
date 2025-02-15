# Changelog

## 0.5.0

- Added result transformer concept to transform and / or rename properties in the database result after decoding and before denormalization.

## 0.4.0

- Added full support for PHP 8.4 including dependencies.

## 0.3.1

- Allowed for `digital-craftsman/self-aware-normalizers:^0.2.0`.

## 0.3.0

- Replaced own normalizers and doctrine types with the [`digital-craftsman/self-aware-normalizers`](https://github.com/digital-craftsman-de/self-aware-normalizers) package.

## 0.2.1

- Fixed `FloatNormalizableType` that expected a `float` but will receive a `string` from the database instead.

## 0.2.0

- Change name of parameter for denormalize method to `$data`.

## 0.1.0

- Initial release
