<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectId;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\ArrayNormalizable;

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
     *   projectId: string,
     *    name: string,
     * } $data
     */
    public static function denormalize(array $data): self
    {
        return new self(
            projectId: ProjectId::denormalize($data['projectId']),
            name: $data['name'],
        );
    }

    /**
     * @return array{
     *   projectId: string,
     *   name: string,
     * }
     */
    public function normalize(): array
    {
        return [
            'projectId' => $this->projectId->normalize(),
            'name' => $this->name,
        ];
    }
}
