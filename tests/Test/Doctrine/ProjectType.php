<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\Doctrine;

use DigitalCraftsman\DeserializingConnection\Test\DTO\Project;
use DigitalCraftsman\SelfAwareNormalizers\Doctrine\ArrayNormalizableType;

final class ProjectType extends ArrayNormalizableType
{
    #[\Override]
    public static function getTypeName(): string
    {
        return 'project';
    }

    #[\Override]
    public static function getClass(): string
    {
        return Project::class;
    }
}
