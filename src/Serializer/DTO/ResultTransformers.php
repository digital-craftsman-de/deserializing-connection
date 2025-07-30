<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO;

/**
 * @template-implements \IteratorAggregate<int, ResultTransformer>
 */
final readonly class ResultTransformers implements \IteratorAggregate
{
    /**
     * @param array<int, ResultTransformer> $resultTransformers
     */
    public function __construct(
        /**
         * @var array<int, ResultTransformer> $resultTransformers
         */
        public array $resultTransformers = [],
    ) {
        $this->keysAndRenameToMustNotBeInConflictWithEachOther($resultTransformers);
    }

    /**
     * @param array<int, ResultTransformer> $resultTransformers
     */
    private function keysAndRenameToMustNotBeInConflictWithEachOther(array $resultTransformers): void
    {
        $propertyNames = [];
        $renameTos = [];
        foreach ($resultTransformers as $resultTransformer) {
            $levels = explode('.', $resultTransformer->key->value);
            $propertyNames[] = $levels[count($levels) - 1];
            if ($resultTransformer->renameTo !== null) {
                $renameTos[] = $resultTransformer->renameTo;
            }
        }

        $countOfOverlappingEntries = count(array_intersect($propertyNames, $renameTos));
        if ($countOfOverlappingEntries > 0) {
            throw new Exception\ConflictBetweenKeysAndRenameToConfiguration();
        }
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->resultTransformers);
    }
}
