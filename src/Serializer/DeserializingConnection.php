<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Profiler\NullTracer;
use DigitalCraftsman\DeserializingConnection\Profiler\Tracer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;

final readonly class DeserializingConnection
{
    public function __construct(
        private DecodingConnection $decodingConnection,
        private TypedDenormalizer $typedDenormalizer,
        private ResultTransformerRunner $resultTransformerRunner,
        private Tracer $tracer = new NullTracer(),
    ) {
    }

    /**
     * Can be used to get a single object from the database, returns null if no result is found. Use @see getOne instead if you want to
     * throw an exception when no result is found.
     *
     * The denormalization step expects an array with every column being a key in the array. If instead you need a single value (will be
     * most likely scalar and not an array) instead, you can use the @see findOneFromSingleValue method instead.
     *
     * @template T of object
     *
     * @param non-empty-string                                                        $sql
     * @param class-string<T>                                                         $class
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     * @param array<string, DTO\DecoderType>                                          $decoderTypes
     * @param array<int, DTO\ResultTransformer>                                       $resultTransformers
     *
     * @return T|null
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function findOne(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        array $resultTransformers = [],
    ): ?object {
        $trace = $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'class' => $class,
            'decoderTypes' => $decoderTypes,
            'resultTransformers' => $resultTransformers,
        ]);

        try {
            $resultTransformerDTOs = new DTO\ResultTransformers($resultTransformers);

            $result = $this->decodingConnection->fetchAssociative(
                $sql,
                $parameters,
                $parameterTypes,
                $decoderTypes,
                trace: $trace,
            );

            if ($result === null) {
                return null;
            }

            $this->resultTransformerRunner->runTransformations(
                result: $result,
                resultTransformers: $resultTransformerDTOs,
            );

            $trace->recordTransformedResult($result);

            $denormalizedResult = $this->typedDenormalizer->denormalize($result, $class);

            $trace->recordDenormalizedResult($denormalizedResult);

            return $denormalizedResult;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * Can be used to get a single object from the database, but throws an exception if no result is found. Use @see findOne if you want to
     * return null when no result is found.
     *
     * The denormalization step expects an array with every column being a key in the array. If instead you need a single value (will be
     * most likely scalar and not an array) instead, you can use the @see getOneFromSingleValue method instead.
     *
     * @template T of object
     *
     * @param non-empty-string                                                        $sql
     * @param class-string<T>                                                         $class
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     * @param array<string, DTO\DecoderType>                                          $decoderTypes
     * @param array<int, DTO\ResultTransformer>                                       $resultTransformers
     * @param ?callable(): \Throwable                                                 $otherwiseThrow
     *
     * @return T
     *
     * @throws \Throwable
     * @throws Exception\ElementNotFound
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function getOne(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        array $resultTransformers = [],
        ?callable $otherwiseThrow = null,
    ): ?object {
        $trace = $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'class' => $class,
            'decoderTypes' => $decoderTypes,
            'resultTransformers' => $resultTransformers,
        ]);

        try {
            $resultTransformerDTOs = new DTO\ResultTransformers($resultTransformers);

            $result = $this->decodingConnection->fetchAssociative(
                $sql,
                $parameters,
                $parameterTypes,
                $decoderTypes,
                trace: $trace,
            );

            if ($result === null) {
                throw $otherwiseThrow !== null
                    ? $otherwiseThrow()
                    : new Exception\ElementNotFound();
            }

            $this->resultTransformerRunner->runTransformations(
                result: $result,
                resultTransformers: $resultTransformerDTOs,
            );

            $trace->recordTransformedResult($result);

            $denormalizedResult = $this->typedDenormalizer->denormalize($result, $class);

            $trace->recordDenormalizedResult($denormalizedResult);

            return $denormalizedResult;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * Can be used to get a single object from the database, returns null when no result is found. Use @see getOneFromSingleValue instead if
     * you want to throw an exception when no result is found.
     *
     * The denormalization step expects a single value (will be most likely scalar and not an array). If instead you need an array with
     * every column being a key in the array, you can use the @see findOne method instead.
     *
     * @template T of object
     *
     * @param non-empty-string                                                        $sql
     * @param class-string<T>                                                         $class
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     *
     * @return T|null
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function findOneFromSingleValue(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        ?DTO\DecoderType $decoderType = null,
        ?DTO\ResultTransformer $resultTransformer = null,
    ): ?object {
        $trace = $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'class' => $class,
            'decoderType' => $decoderType,
            'resultTransformer' => $resultTransformer,
        ]);

        try {
            if ($resultTransformer !== null
                && $resultTransformer->renameTo !== null
            ) {
                throw new Exception\SingleValueTransformationMustNotContainRenaming();
            }

            $result = $this->decodingConnection->fetchOne(
                $sql,
                $parameters,
                $parameterTypes,
                $decoderType,
                trace: $trace,
            );

            if ($result === null) {
                return null;
            }

            if ($resultTransformer !== null) {
                $result = $this->resultTransformerRunner->transformItem(
                    transformer: $resultTransformer,
                    item: $result,
                    result: $result,
                    resultOfLevel: $result,
                );
            }

            $trace->recordTransformedResult($result);

            $denormalizedResult = $this->typedDenormalizer->denormalize($result, $class);

            $trace->recordDenormalizedResult($denormalizedResult);

            return $denormalizedResult;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * Can be used to get a single object from the database, but throws an exception if no result is found. Use @see findOneFromSingleValue
     * instead if you want to return null when no result is found.
     *
     * The denormalization step expects a single value (will be most likely scalar and not an array). If instead you need an array with
     * every column being a key in the array, you can use the @see getOne method instead.
     *
     * @template T of object
     *
     * @param non-empty-string                                                        $sql
     * @param class-string<T>                                                         $class
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     * @param ?callable(): \Throwable                                                 $otherwiseThrow
     *
     * @return T
     *
     * @throws \Throwable
     * @throws Exception\ElementNotFound
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function getOneFromSingleValue(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        ?DTO\DecoderType $decoderType = null,
        ?DTO\ResultTransformer $resultTransformer = null,
        ?callable $otherwiseThrow = null,
    ): object {
        $trace = $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'class' => $class,
            'decoderType' => $decoderType,
            'resultTransformer' => $resultTransformer,
        ]);

        try {
            if ($resultTransformer !== null
                && $resultTransformer->renameTo !== null
            ) {
                throw new Exception\SingleValueTransformationMustNotContainRenaming();
            }

            $result = $this->decodingConnection->fetchOne(
                $sql,
                $parameters,
                $parameterTypes,
                $decoderType,
                trace: $trace,
            );

            if ($result === null) {
                throw $otherwiseThrow !== null
                    ? $otherwiseThrow()
                    : new Exception\ElementNotFound();
            }

            if ($resultTransformer !== null) {
                $result = $this->resultTransformerRunner->transformItem(
                    transformer: $resultTransformer,
                    item: $result,
                    result: $result,
                    resultOfLevel: $result,
                );
            }

            $trace->recordTransformedResult($result);

            $denormalizedResult = $this->typedDenormalizer->denormalize($result, $class);

            $trace->recordDenormalizedResult($denormalizedResult);

            return $denormalizedResult;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * @template T of object
     * @template I of \Closure(mixed $item): string|null
     *
     * @param non-empty-string                                                        $sql
     * @param class-string<T>                                                         $class
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     * @param array<string, DTO\DecoderType>                                          $decoderTypes
     * @param array<int, DTO\ResultTransformer>                                       $resultTransformers
     * @param I                                                                       $indexedBy
     *
     * @return (I is null ? list<T> : array<string, T>)
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function findArray(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        array $resultTransformers = [],
        ?\Closure $indexedBy = null,
    ): array {
        $trace = $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'class' => $class,
            'decoderTypes' => $decoderTypes,
            'resultTransformers' => $resultTransformers,
        ]);

        try {
            $resultTransformerDTOs = new DTO\ResultTransformers($resultTransformers);

            $result = $this->decodingConnection->fetchAllAssociative(
                $sql,
                $parameters,
                $parameterTypes,
                $decoderTypes,
                trace: $trace,
            );

            foreach ($result as &$resultItem) {
                $this->resultTransformerRunner->runTransformations(
                    result: $resultItem,
                    resultTransformers: $resultTransformerDTOs,
                );
            }
            unset($resultItem);

            $trace->recordTransformedResult($result);

            $denormalizedResult = $this->typedDenormalizer->denormalizeArray($result, $class);

            if ($indexedBy === null) {
                $trace->recordDenormalizedResult($denormalizedResult);

                return $denormalizedResult;
            }

            $indexedResult = [];
            foreach ($denormalizedResult as $item) {
                $index = $indexedBy($item);
                /**
                 * @psalm-suppress TypeDoesNotContainType Validation against missing compliance with Psalm.
                 */
                if (!is_string($index)) {
                    throw new Exception\IndexMustBeString();
                }

                $indexedResult[$index] = $item;
            }

            $trace->recordDenormalizedResult($indexedResult);

            return $indexedResult;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * The trace is started when the generator is started. The denormalized items are added to the trace one after another while the
     * generator is consumed.
     *
     * @template T of object
     *
     * @param non-empty-string                                                        $sql
     * @param class-string<T>                                                         $class
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     * @param array<string, DTO\DecoderType>                                          $decoderTypes
     * @param array<int, DTO\ResultTransformer>                                       $resultTransformers
     *
     * @return \Generator<int, T>
     */
    #[\NoDiscard('as the query is only executed when the generator is consumed')]
    public function findGenerator(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        array $resultTransformers = [],
    ): \Generator {
        $trace = $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'class' => $class,
            'decoderTypes' => $decoderTypes,
            'resultTransformers' => $resultTransformers,
        ]);

        try {
            $resultTransformerDTOs = new DTO\ResultTransformers($resultTransformers);

            /** @var list<array> $result */
            $result = $this->decodingConnection->fetchAllAssociative(
                $sql,
                $parameters,
                $parameterTypes,
                $decoderTypes,
                trace: $trace,
            );

            foreach ($result as &$resultItem) {
                $this->resultTransformerRunner->runTransformations(
                    result: $resultItem,
                    resultTransformers: $resultTransformerDTOs,
                );
            }
            unset($resultItem);

            $trace->recordTransformedResult($result);

            foreach ($result as $item) {
                $trace->startItemDenormalization();

                /** @var T $denormalizedItem */
                $denormalizedItem = $this->typedDenormalizer->denormalize($item, $class);

                $trace->addDenormalizedItem($denormalizedItem);

                yield $denormalizedItem;
            }
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }
}
