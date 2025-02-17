<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO;

/**
 * The result transformer key is the array key that represents the relevant data, in the decoded / denormalized data, that should be
 * transformed. If the transformation should happen on an item in a lower level of the array, the key can be split with a dot ".". When the
 * transformation should happen on a list of items, you can use "*" to target all items in the list.
 *
 * So a key might look like this "user.projects.*.name" for the following structure:
 *
 * [
 *   'user' => [
 *     'projects' => [
 *       [
 *         'projectId' => '1024bf03-4bed-4850-8db4-3fba951d0271'
 *         'name' => 'Project 1',
 *       ],
 *       [
 *         'projectId' => '1024bf03-4bed-4850-8db4-3fba951d0271'
 *         'name' => 'Project 1',
 *       ],
 *    ],
 * ],
 *
 * The key is the same whether the transformation happens through a `getOne` or a `getArray` method. On the `getArray` method the key is
 * relevant from the first level of the array, meaning it's not necessary to start the key like "*.user.projects.*.name". Using a "*" at the
 * start or the end of the key is not allowed.
 */
final readonly class ResultTransformerKey
{
    public const string ARRAY_KEY_IDENTIFIER = '*';

    public function __construct(
        public string $value,
    ) {
        if (str_starts_with($this->value, self::ARRAY_KEY_IDENTIFIER)) {
            throw new Exception\ResultTransformationKeyCanNotStartWithAnArrayIdentifier($this->value);
        }
        if (str_ends_with($this->value, self::ARRAY_KEY_IDENTIFIER)) {
            throw new Exception\ResultTransformationKeyCanNotEndWithAnArrayIdentifier($this->value);
        }
    }
}
