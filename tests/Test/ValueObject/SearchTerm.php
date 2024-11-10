<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\ValueObject;

use DigitalCraftsman\DeserializingConnection\Serializer\StringNormalizable;

final readonly class SearchTerm implements StringNormalizable
{
    public function __construct(
        public string $search,
    ) {
        if ($this->search === '') {
            throw new \InvalidArgumentException('The search term must not be empty');
        }
        if (str_contains($this->search, '*')) {
            throw new \InvalidArgumentException('The search term must not contain *');
        }
    }

    // -- String normalizable

    #[\Override]
    public static function denormalize(string $string): self
    {
        return new self($string);
    }

    #[\Override]
    public function normalize(): string
    {
        return $this->search;
    }
}
