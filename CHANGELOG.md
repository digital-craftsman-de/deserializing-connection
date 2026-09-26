# Changelog

## 0.8.0

- **[Breaking change](./UPGRADE.md#breaking-change-keys-of-decodertypes-must-not-contain-dots-anymore)**: Keys of `decoderTypes` must not contain dots (`.`) anymore, except to target nested values. A dot is now interpreted as a separator between levels. Additionally, keys must not start or end with `*` anymore.
- Added support for nested keys in `decoderTypes` (e.g. `projects.*.timeEntries.*.hours`) with the same key syntax as the result transformers. Parent values are decoded before nested values, independent of the order of definition.
- Added `DecoderTypeKeyLevelIsNotAnArray` exception, which is thrown when a level on the way to a nested value is neither an array nor `null` (e.g. when the parent JSON value wasn't decoded).
- Added optional `otherwiseThrow` parameter to `DeserializingConnection->getOne` and `DeserializingConnection->getOneFromSingleValue` to throw a custom exception instead of `ElementNotFound` when no result is found.

## 0.7.2

- Added missing explicit object check before denormalizing value for result transformer.

## 0.7.1

- Fixed handling of an array in result transformation when it's null. It's ignored now.

## 0.7.0

- Added support for PHP 8.5.
- Dropped support for PHP 8.3.
- Added support for Symfony ^8.0.
- Dropped support for Symfony 7.3 and below. Only the 7.4 LTS version is still supported.

## 0.6.1

- Fixed return type annotation of `DeserializingConnection->findArray` depending on the `indexedBy` parameter.

## 0.6.0

- Added `DecodingConnection->fetchOne`.
- Added `DecodingConnection->fetchFirstColumn`.
- Added additional optional parameter `indexedBy` to `DecodingConnection::fetchAllAssociative`.
- Added `DeserializingConnection->findOneFromSingleValue`.
- Added `DeserializingConnection->getOneFromSingleValue`.
- Added additional optional parameter `indexedBy` to `DeserializingConnection::findArray`.

## 0.5.2

- Updated `digital-craftsman/self-aware-normalizers` to use the first stable version.

## 0.5.1

- Extended the return type and parameter type in `TypedDenormalizer` as it was too narrow to allow all relevant cases.

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
