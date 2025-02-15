<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResultTransformerKey::class)]
#[CoversClass(Exception\ResultTransformationKeyCanNotStartWithAnArrayIdentifier::class)]
#[CoversClass(Exception\ResultTransformationKeyCanNotEndWithAnArrayIdentifier::class)]
final class ResultTransformerKeyTest extends TestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function construct_works(): void
    {
        // -- Act
        new ResultTransformerKey('user');
    }

    #[Test]
    public function construct_fails_when_array_identifier_is_used_at_the_start(): void
    {
        // -- Assert
        $this->expectException(Exception\ResultTransformationKeyCanNotStartWithAnArrayIdentifier::class);

        // -- Act
        new ResultTransformerKey('*.user');
    }

    #[Test]
    public function construct_fails_when_array_identifier_is_used_at_the_end(): void
    {
        // -- Assert
        $this->expectException(Exception\ResultTransformationKeyCanNotEndWithAnArrayIdentifier::class);

        // -- Act
        new ResultTransformerKey('user.*');
    }
}
