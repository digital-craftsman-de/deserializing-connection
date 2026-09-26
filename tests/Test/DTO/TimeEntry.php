<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

final readonly class TimeEntry
{
    public function __construct(
        public string $description,
        public ?float $hours,
    ) {
    }
}
