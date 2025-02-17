<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Test\DTO\User;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectIdList;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\ArrayNormalizableNormalizer;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\StringNormalizableNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(TypedDenormalizer::class)]
final class TypedDenormalizerTest extends TestCase
{
    private TypedDenormalizer $typedDenormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typedDenormalizer = new TypedDenormalizer(
            new Serializer(
                normalizers: [
                    new StringNormalizableNormalizer(),
                    new ArrayNormalizableNormalizer(),
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
    }

    #[Test]
    public function denormalize_works(): void
    {
        // -- Arrange
        $data = [
            'userId' => '1a493034-c5e0-4414-a9b7-ca414b884719',
            'name' => 'John Doe',
            'accessibleProjects' => [],
            'companies' => [],
        ];

        // -- Act
        $user = $this->typedDenormalizer->denormalize($data, User::class);

        // -- Assert
        self::assertEquals('1a493034-c5e0-4414-a9b7-ca414b884719', $user->userId);
        self::assertSame('John Doe', $user->name);
        self::assertEquals(ProjectIdList::emptyList(), $user->accessibleProjects);
    }

    #[Test]
    public function denormalize_array_works(): void
    {
        // -- Arrange
        $data = [
            [
                'userId' => '1a493034-c5e0-4414-a9b7-ca414b884719',
                'name' => 'John Doe',
                'accessibleProjects' => [],
                'companies' => [],
            ],
            [
                'userId' => '42a4d8b0-73ff-42da-88cc-8dd96fa2f170',
                'name' => 'Jane Doe',
                'accessibleProjects' => [],
                'companies' => [],
            ],
        ];

        // -- Act
        $users = $this->typedDenormalizer->denormalizeArray($data, User::class);

        // -- Assert
        self::assertCount(2, $users);
        self::assertEquals('1a493034-c5e0-4414-a9b7-ca414b884719', $users[0]->userId);
        self::assertSame('John Doe', $users[0]->name);
        self::assertEquals(ProjectIdList::emptyList(), $users[0]->accessibleProjects);
    }
}
