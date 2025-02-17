<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO;

final readonly class ResultTransformer
{
    /**
     * @param class-string|null                                                           $denormalizeResultToClass
     * @param (\Closure(mixed $payload, array $resultOfLevel, array $result): mixed)|null $transformer
     */
    private function __construct(
        public ResultTransformerKey $key,
        /**
         * @var class-string|null $denormalizeResultToClass
         */
        public ?string $denormalizeResultToClass,
        /**
         * @var (\Closure(mixed $payload, array $resultOfLevel, array $result): mixed)|null
         */
        public ?\Closure $transformer,
        public bool $isTransformedResultNormalized,
        public ?string $renameTo,
    ) {
    }

    /**
     * @param class-string|null                                                    $denormalizeResultToClass
     * @param \Closure(mixed $payload, array $resultOfLevel, array $result): mixed $transformer
     */
    public static function toTransform(
        string $key,
        ?string $denormalizeResultToClass,
        \Closure $transformer,
        bool $isTransformedResultNormalized,
    ): self {
        return new self(
            key: new ResultTransformerKey($key),
            denormalizeResultToClass: $denormalizeResultToClass,
            transformer: $transformer,
            isTransformedResultNormalized: $isTransformedResultNormalized,
            renameTo: null,
        );
    }

    public static function toRename(
        string $key,
        string $renameTo,
    ): self {
        return new self(
            key: new ResultTransformerKey($key),
            denormalizeResultToClass: null,
            transformer: null,
            isTransformedResultNormalized: false,
            renameTo: $renameTo,
        );
    }

    /**
     * @param class-string|null                                                    $denormalizeResultToClass
     * @param \Closure(mixed $payload, array $resultOfLevel, array $result): mixed $transformer
     */
    public static function toTransformAndRename(
        string $key,
        ?string $denormalizeResultToClass,
        \Closure $transformer,
        bool $isTransformedResultNormalized,
        string $renameTo,
    ): self {
        return new self(
            key: new ResultTransformerKey($key),
            denormalizeResultToClass: $denormalizeResultToClass,
            transformer: $transformer,
            isTransformedResultNormalized: $isTransformedResultNormalized,
            renameTo: $renameTo,
        );
    }
}
