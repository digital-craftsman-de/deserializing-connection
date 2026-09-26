<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

final readonly class CompanyWithProjects
{
    /**
     * @param list<ProjectWithTimeEntries> $projects
     */
    public function __construct(
        public string $name,
        /**
         * @var list<ProjectWithTimeEntries>
         */
        public array $projects,
    ) {
    }
}
