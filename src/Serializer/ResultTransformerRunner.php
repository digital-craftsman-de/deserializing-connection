<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

final readonly class ResultTransformerRunner
{
    public function __construct(
        private TypedDenormalizer $typedDenormalizer,
    ) {
    }

    public function runTransformations(
        array &$result,
        DTO\ResultTransformers $resultTransformers,
    ): void {
        foreach ($resultTransformers as $resultTransformation) {
            $levels = explode('.', $resultTransformation->key->value);

            $this->runRecursive(
                transformer: $resultTransformation,
                result: $result,
                resultOfLevel: $result,
                levels: $levels,
                levelIndex: 0,
            );
        }
    }

    private function runRecursive(
        DTO\ResultTransformer $transformer,
        array &$result,
        array &$resultOfLevel,
        array $levels,
        int $levelIndex,
    ): void {
        $levelKey = $levels[$levelIndex];
        if ($levelIndex === count($levels) - 1) {
            $this->transformResult(
                transformer: $transformer,
                levelKey: $levelKey,
                result: $result,
                resultOfLevel: $resultOfLevel,
            );

            return;
        }

        if ($levelKey === DTO\ResultTransformerKey::ARRAY_KEY_IDENTIFIER) {
            foreach ($resultOfLevel as &$resultOfLevelItem) {
                $this->runRecursive(
                    transformer: $transformer,
                    result: $result,
                    resultOfLevel: $resultOfLevelItem,
                    levels: $levels,
                    levelIndex: $levelIndex + 1,
                );
            }

            return;
        }

        $this->runRecursive(
            transformer: $transformer,
            result: $result,
            resultOfLevel: $resultOfLevel[$levelKey],
            levels: $levels,
            levelIndex: $levelIndex + 1,
        );
    }

    private function transformResult(
        DTO\ResultTransformer $transformer,
        string $levelKey,
        array $result,
        array &$resultOfLevel,
    ): void {
        if (!array_key_exists($levelKey, $resultOfLevel)) {
            throw new Exception\ResultTransformerKeyNotFound($levelKey);
        }

        if ($transformer->transformer !== null) {
            $resultOfLevel[$levelKey] = $this->transformItem(
                transformer: $transformer,
                item: $resultOfLevel[$levelKey],
                result: $result,
                resultOfLevel: $resultOfLevel,
            );
        }

        if ($transformer->renameTo !== null) {
            $resultOfLevel[$transformer->renameTo] = $resultOfLevel[$levelKey];
            unset($resultOfLevel[$levelKey]);
        }
    }

    public function transformItem(
        DTO\ResultTransformer $transformer,
        mixed &$item,
        mixed $result,
        mixed $resultOfLevel,
    ): mixed {
        if ($item !== null) {
            $payload = $transformer->denormalizeResultToClass !== null
                ? $this->typedDenormalizer->denormalize($item, $transformer->denormalizeResultToClass)
                : $item;
        } else {
            $payload = null;
        }

        /**
         * It's only called when a transformer is defined.
         *
         * @var \Closure(mixed $payload, array $resultOfLevel, array $result): mixed $transformerFunction
         */
        $transformerFunction = $transformer->transformer;
        $transformedPayload = $transformerFunction->__invoke($payload, $resultOfLevel, $result);

        return $transformer->isTransformedResultNormalized
            && $transformedPayload !== null
            ? $this->typedDenormalizer->normalize($transformedPayload)
            : $transformedPayload;
    }
}
