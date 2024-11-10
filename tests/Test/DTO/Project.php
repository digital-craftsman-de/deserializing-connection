<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

use DigitalCraftsman\DeserializingConnection\Serializer\ArrayNormalizable;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectId;

final readonly class Project implements ArrayNormalizable
{
    public function __construct(
        public ProjectId $projectId,
        public string $name,
    ) {
    }

    // -- Array normalizable

    /**
     * @param array{
     *     projectId: string,
     *      name: string,
     * } $array
     */
    public static function denormalize(array $array): self
    {
        return new self(
            projectId: ProjectId::fromString($array['projectId']),
            name: $array['name'],
        );
    }

    /**
     * @return array{
     *     projectId: string,
     *      name: string,
     * }
     */
    public function normalize(): array
    {
        return [
            'projectId' => (string) $this->projectId,
            'name' => $this->name,
        ];
    }
}
