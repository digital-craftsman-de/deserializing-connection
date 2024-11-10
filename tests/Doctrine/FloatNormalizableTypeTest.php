<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Doctrine;

use DigitalCraftsman\DeserializingConnection\Test\Doctrine\RangeType;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\Range;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FloatNormalizableType::class)]
final class FloatNormalizableTypeTest extends TestCase
{
    #[Test]
    public function convert_works(): void
    {
        // -- Arrange
        $doctrineType = new RangeType();
        $platform = new PostgreSQLPlatform();

        $value = new Range(0.5);

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
        $doctrineType = new RangeType();
        $platform = new PostgreSQLPlatform();

        $value = null;

        // -- Act
        $databaseValue = $doctrineType->convertToDatabaseValue($value, $platform);
        $convertedValue = $doctrineType->convertToPHPValue($databaseValue, $platform);

        // -- Assert
        self::assertEquals($value, $convertedValue);
    }
}
