<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectIdList;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserId;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\ArrayNormalizable;

final readonly class User implements ArrayNormalizable
{
    /**
     * @param array<int, Company> $companies
     */
    public function __construct(
        public UserId $userId,
        public string $name,
        public ProjectIdList $accessibleProjects,
        /**
         * @var array<int, Company>
         */
        public array $companies,
    ) {
    }

    // -- Array normalizable

    public static function denormalize(array $data): self
    {
        return new self(
            userId: UserId::denormalize($data['userId']),
            name: $data['name'],
            accessibleProjects: ProjectIdList::denormalize($data['accessibleProjects']),
            companies: array_map(
                static fn (array $companyData): Company => Company::denormalize($companyData),
                $data['companies'],
            ),
        );
    }

    public function normalize(): array
    {
        return [
            'userId' => $this->userId->normalize(),
            'name' => $this->name,
            'accessibleProjects' => $this->accessibleProjects->normalize(),
            'companies' => array_map(
                static fn (Company $company): array => $company->normalize(),
                $this->companies,
            ),
        ];
    }
}
