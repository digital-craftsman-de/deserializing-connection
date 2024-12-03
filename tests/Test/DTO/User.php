<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectIdList;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserId;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\ArrayNormalizable;

final readonly class User implements ArrayNormalizable
{
    public function __construct(
        public UserId $userId,
        public string $name,
        public ProjectIdList $accessibleProjects,
    ) {
    }

    public static function denormalize(array $data): self
    {
        return new self(
            UserId::fromString($data['userId']),
            $data['name'],
            ProjectIdList::fromIdStrings($data['accessibleProjects']),
        );
    }

    public function normalize(): array
    {
        return [
            'userId' => (string) $this->userId,
            'name' => $this->name,
            'accessibleProjects' => $this->accessibleProjects->idsAsStringList(),
        ];
    }
}
