<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Doctrine;

use DigitalCraftsman\DeserializingConnection\Test\Doctrine\ProjectType;
use DigitalCraftsman\DeserializingConnection\Test\DTO\Project;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectId;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayNormalizableType::class)]
final class ArrayNormalizableTypeTest extends TestCase
{
    #[Test]
    public function convert_works(): void
    {
        // -- Arrange
        $doctrineType = new ProjectType();
        $platform = new PostgreSQLPlatform();

        $value = new Project(
            projectId: ProjectId::generateRandom(),
            name: 'Great project',
        );

        // -- Act
        $databaseValue = $doctrineType->convertToDatabaseValue($value, $platform);
        $convertedValue = $doctrineType->convertToPHPValue($databaseValue, $platform);

        // -- Assert
        self::assertEquals($value, $convertedValue);
    }

    #[Test]
    public function convert_works_with_null(): void
    {
        // -- Arrange
        $doctrineType = new ProjectType();
        $platform = new PostgreSQLPlatform();

        $value = null;

        // -- Act
        $databaseValue = $doctrineType->convertToDatabaseValue($value, $platform);
        $convertedValue = $doctrineType->convertToPHPValue($databaseValue, $platform);

        // -- Assert
        self::assertEquals($value, $convertedValue);
    }
}
