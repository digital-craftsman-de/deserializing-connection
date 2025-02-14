<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO;

final readonly class ResultTransformer
{
    /**
     * @param class-string|null                                                    $denormalizeResultToClass
     * @param \Closure(mixed $payload, array $resultOfLevel, array $result): mixed $transformer
     */
    public function __construct(
        public ResultTransformerKey $key,
        /**
         * @var class-string|null $denormalizeResultToClass
         */
        public ?string $denormalizeResultToClass,
        /**
         * @var \Closure(mixed $payload, array $resultOfLevel, array $result): mixed
         */
        public \Closure $transformer,
    ) {
    }

    public static function forScalarValue(
        string $key,
        \Closure $transformer,
    ): self {
        return new self(
            key: new ResultTransformerKey($key),
            denormalizeResultToClass: null,
            transformer: $transformer,
        );
    }

    /**
     * @param class-string $denormalizeResultToClass
     */
    public static function forObjectValue(
        string $key,
        string $denormalizeResultToClass,
        \Closure $transformer,
    ): self {
        return new self(
            key: new ResultTransformerKey($key),
            denormalizeResultToClass: $denormalizeResultToClass,
            transformer: $transformer,
        );
    }
}
