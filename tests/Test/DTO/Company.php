<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

use DigitalCraftsman\DeserializingConnection\Test\ValueObject\CompanyId;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\ArrayNormalizable;

final readonly class Company implements ArrayNormalizable
{
    public function __construct(
        public CompanyId $companyId,
        public string $name,
    ) {
    }

    // -- Array normalizable

    /**
     * @param array{
     *   companyId: string,
     *   name: string,
     * } $data
     */
    public static function denormalize(array $data): self
    {
        return new self(
            companyId: CompanyId::fromString($data['companyId']),
            name: $data['name'],
        );
    }

    /**
     * @return array{
     *   companyId: string,
     *   name: string,
     * }
     */
    public function normalize(): array
    {
        return [
            'companyId' => (string) $this->companyId,
            'name' => $this->name,
        ];
    }
}
