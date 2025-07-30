<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

use DigitalCraftsman\SelfAwareNormalizers\Serializer\ArrayNormalizable;

final readonly class AccessToken implements ArrayNormalizable
{
    public function __construct(
        public string $token,
        public int $accessLevel,
    ) {
    }

    public function increaseLevel(): self
    {
        return new self(
            $this->token,
            $this->accessLevel + 1,
        );
    }

    // -- Array normalizable

    /**
     * @param array{
     *   token: string,
     *   accessLevel: int,
     * } $data
     */
    #[\Override]
    public static function denormalize(array $data): self
    {
        return new self(
            token: $data['token'],
            accessLevel: $data['accessLevel'],
        );
    }

    /**
     * @return array{
     *   token: string,
     *   accessLevel: int,
     * }
     */
    #[\Override]
    public function normalize(): array
    {
        return [
            'token' => $this->token,
            'accessLevel' => $this->accessLevel,
        ];
    }
}
