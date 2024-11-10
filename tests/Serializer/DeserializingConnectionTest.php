<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Serializer\DTO\DecoderType;
use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use DigitalCraftsman\DeserializingConnection\Test\DTO\User;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectId;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectIdList;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserId;
use DigitalCraftsman\Ids\Serializer\IdListNormalizer;
use DigitalCraftsman\Ids\Serializer\IdNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(DeserializingConnection::class)]
#[CoversClass(Exception\ElementNotFound::class)]
final class DeserializingConnectionTest extends ConnectionTestCase
{
    private DeserializingConnection $deserializingConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $typedDenormalizer = new TypedDenormalizer(
            new Serializer(
                normalizers: [
                    new IdNormalizer(),
                    new IdListNormalizer(),
                    new ArrayDenormalizer(),
                    new PropertyNormalizer(
                        propertyTypeExtractor: new PropertyInfoExtractor(
                            typeExtractors: [
                                new PhpDocExtractor(),
                                new ReflectionExtractor(),
                            ],
                        ),
                    ),
                ],
                encoders: [
                    new JsonEncoder(),
                ],
            ),
        );

        $this->deserializingConnection = new DeserializingConnection(
            decodingConnection: new DecodingConnection($this->connection),
            typedDenormalizer: $typedDenormalizer,
        );
    }

    #[Test]
    public function get_one_works(): void
    {
        // -- Arrange
        $userIdString = '417df760-0d16-408f-8201-ec7760dee9fb';
        $expectedResult = new User(
            userId: UserId::fromString($userIdString),
            name: 'John Doe',
            accessibleProjects: new ProjectIdList([
                ProjectId::fromString('05f620c2-ea64-4012-816f-884310f69dd0'),
                ProjectId::fromString('91f47435-208d-4344-990b-ae17bd4b13fa'),
            ]),
        );

        // -- Act
        $user = $this->deserializingConnection->getOne(
            sql: <<<'SQL'
                SELECT
                    '417df760-0d16-408f-8201-ec7760dee9fb' AS "userId",
                    'John Doe' AS name,
                    '["05f620c2-ea64-4012-816f-884310f69dd0", "91f47435-208d-4344-990b-ae17bd4b13fa"]' AS "accessibleProjects"
                WHERE '417df760-0d16-408f-8201-ec7760dee9fb' = :userId
                SQL,
            class: User::class,
            parameters: [
                'userId' => $userIdString,
            ],
            decoderTypes: [
                'accessibleProjects' => DecoderType::JSON,
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $user);
    }

    #[Test]
    public function get_one_fails_without_result(): void
    {
        // -- Assert
        $this->expectException(Exception\ElementNotFound::class);

        // -- Act
        $this->deserializingConnection->getOne(
            sql: <<<'SQL'
                WITH empty_table AS (
                    SELECT 1
                    WHERE false
                )
                SELECT *
                FROM empty_table
                SQL,
            class: User::class,
            decoderTypes: [
                'accessibleProjects' => DecoderType::JSON,
            ],
        );
    }
}
