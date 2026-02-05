<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Test\DTO\AccessToken;
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

#[CoversClass(ResultTransformerRunner::class)]
#[CoversClass(DTO\ResultTransformers::class)]
#[CoversClass(DTO\ResultTransformer::class)]
#[CoversClass(Exception\ResultTransformerKeyNotFound::class)]
final class ResultTransformerRunnerTest extends TestCase
{
    private ResultTransformerRunner $resultTransformerRunner;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $typedDenormalizer = new TypedDenormalizer(
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

        $this->resultTransformerRunner = new ResultTransformerRunner(
            typedDenormalizer: $typedDenormalizer,
        );
    }

    #[Test]
    public function run_transformations_works_on_one_level(): void
    {
        // -- Arrange
        $result = [
            'userId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
            'name' => 'John Doe',
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransform(
                    key: 'name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name) => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ],
            ));

        // -- Assert
        self::assertSame('JOHN DOE', $result['name']);
    }

    #[Test]
    public function run_transformations_fails_when_key_is_not_found(): void
    {
        // -- Assert
        $this->expectException(Exception\ResultTransformerKeyNotFound::class);

        // -- Arrange
        $result = [
            'userId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
            'name' => 'John Doe',
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransform(
                    key: 'project',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name) => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ],
            ));
    }

    #[Test]
    public function run_transformations_works_on_second_level(): void
    {
        // -- Arrange
        $result = [
            'userId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
            'name' => 'John Doe',
            'project' => [
                'projectId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
                'name' => 'Project X',
            ],
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransform(
                    key: 'project.name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name) => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ],
            ));

        // -- Assert
        self::assertSame('PROJECT X', $result['project']['name']);
    }

    #[Test]
    public function run_transformations_works_on_third_level(): void
    {
        // -- Arrange
        $result = [
            'userId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
            'name' => 'John Doe',
            'project' => [
                'projectId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
                'name' => 'Project X',
                'responsible' => [
                    'userId' => '10ee2b8b-f4fc-4791-8e70-f5a141cd1ad9',
                    'name' => 'Tony Stark',
                ],
            ],
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransform(
                    key: 'project.responsible.name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name) => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ],
            ));

        // -- Assert
        self::assertSame('TONY STARK', $result['project']['responsible']['name']);
    }

    #[Test]
    public function run_transformations_works_with_array(): void
    {
        // -- Arrange
        $result = [
            'userId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
            'name' => 'John Doe',
            'projects' => [
                [
                    'projectId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
                    'name' => 'Project X',
                ],
                [
                    'projectId' => '5c0c0fc7-e0e7-4cfc-9715-21c96dfac6bb',
                    'name' => 'Project Y',
                ],
            ],
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransform(
                    key: 'projects.*.name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name) => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ]),
        );

        // -- Assert
        self::assertSame('PROJECT X', $result['projects'][0]['name']);
        self::assertSame('PROJECT Y', $result['projects'][1]['name']);
    }

    #[Test]
    public function run_transformations_ignores_elements_in_array_that_are_null(): void
    {
        // -- Arrange
        $result = [
            'userId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
            'name' => 'John Doe',
            'project' => [
                'projectDefinition' => null,
            ],
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransform(
                    key: 'project.projectDefinition.name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name) => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ]),
        );

        // -- Assert
        self::assertNull($result['project']['projectDefinition']);
    }

    #[Test]
    public function run_transformations_works_with_array_and_data_of_current_level(): void
    {
        // -- Arrange
        $result = [
            'userId' => '06e030f1-4db8-41bb-9e31-b7cb003bf5a7',
            'name' => 'John Doe',
            'projects' => [
                [
                    'projectId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
                    'name' => 'Project X',
                ],
                [
                    'projectId' => '5c0c0fc7-e0e7-4cfc-9715-21c96dfac6bb',
                    'name' => 'Project Y',
                ],
            ],
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransform(
                    key: 'projects.*.name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name, array $resultOfLevel) => sprintf(
                        '%s - %s',
                        strtoupper($name),
                        $resultOfLevel['projectId'],
                    ),
                    isTransformedResultNormalized: false,
                ),
            ]),
        );

        // -- Assert
        self::assertSame('PROJECT X - 399ad4ea-5a85-470e-8283-308a26f9d519', $result['projects'][0]['name']);
        self::assertSame('PROJECT Y - 5c0c0fc7-e0e7-4cfc-9715-21c96dfac6bb', $result['projects'][1]['name']);
    }

    #[Test]
    public function run_transformations_works_with_array_and_data_from_result(): void
    {
        // -- Arrange
        $result = [
            'userId' => '06e030f1-4db8-41bb-9e31-b7cb003bf5a7',
            'name' => 'John Doe',
            'projects' => [
                [
                    'projectId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
                    'name' => 'Project X',
                ],
                [
                    'projectId' => '5c0c0fc7-e0e7-4cfc-9715-21c96dfac6bb',
                    'name' => 'Project Y',
                ],
            ],
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransform(
                    key: 'projects.*.name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name, array $resultOfLevel, array $result) => sprintf(
                        '%s - %s',
                        strtoupper($name),
                        $result['userId'],
                    ),
                    isTransformedResultNormalized: false,
                ),
            ]),
        );

        // -- Assert
        self::assertSame('PROJECT X - 06e030f1-4db8-41bb-9e31-b7cb003bf5a7', $result['projects'][0]['name']);
        self::assertSame('PROJECT Y - 06e030f1-4db8-41bb-9e31-b7cb003bf5a7', $result['projects'][1]['name']);
    }

    #[Test]
    public function run_transformations_works_with_array_and_null_values(): void
    {
        // -- Arrange
        $result = [
            'userId' => '06e030f1-4db8-41bb-9e31-b7cb003bf5a7',
            'name' => 'John Doe',
            'projects' => [
                [
                    'projectId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
                    'name' => 'Project X',
                    'accessToken' => [
                        'token' => '35c9c331-f185-42c6-9952-90150fc56735',
                        'accessLevel' => 5,
                    ],
                ],
                [
                    'projectId' => '5c0c0fc7-e0e7-4cfc-9715-21c96dfac6bb',
                    'name' => 'Project Y',
                    'accessToken' => null,
                ],
            ],
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransform(
                    key: 'projects.*.accessToken',
                    denormalizeResultToClass: AccessToken::class,
                    transformer: static fn (?AccessToken $accessToken) => $accessToken?->increaseLevel(),
                    isTransformedResultNormalized: true,
                ),
            ],
            ));

        // -- Assert
        self::assertSame(6, $result['projects'][0]['accessToken']['accessLevel']);
        self::assertNull($result['projects'][1]['accessToken']);
    }

    #[Test]
    public function run_transformations_works_with_renaming(): void
    {
        // -- Arrange
        $result = [
            'userId' => '06e030f1-4db8-41bb-9e31-b7cb003bf5a7',
            'name' => 'John Doe',
            'projects' => [
                [
                    'projectId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
                    'name' => 'Project X',
                    'accessToken' => [
                        'token' => '35c9c331-f185-42c6-9952-90150fc56735',
                        'accessLevel' => 5,
                    ],
                ],
                [
                    'projectId' => '5c0c0fc7-e0e7-4cfc-9715-21c96dfac6bb',
                    'name' => 'Project Y',
                    'accessToken' => null,
                ],
            ],
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toRename(
                    key: 'projects.*.accessToken',
                    renameTo: 'token',
                ),
            ]),
        );

        // -- Asser)t
        self::assertSame(5, $result['projects'][0]['token']['accessLevel']);
        self::assertNull($result['projects'][1]['token']);
    }

    #[Test]
    public function run_transformations_works_with_array_and_null_values_and_renaming(): void
    {
        // -- Arrange
        $result = [
            'userId' => '06e030f1-4db8-41bb-9e31-b7cb003bf5a7',
            'name' => 'John Doe',
            'projects' => [
                [
                    'projectId' => '399ad4ea-5a85-470e-8283-308a26f9d519',
                    'name' => 'Project X',
                    'accessToken' => [
                        'token' => '35c9c331-f185-42c6-9952-90150fc56735',
                        'accessLevel' => 5,
                    ],
                ],
                [
                    'projectId' => '5c0c0fc7-e0e7-4cfc-9715-21c96dfac6bb',
                    'name' => 'Project Y',
                    'accessToken' => null,
                ],
            ],
        ];

        // -- Act
        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: new DTO\ResultTransformers([
                DTO\ResultTransformer::toTransformAndRename(
                    key: 'projects.*.accessToken',
                    denormalizeResultToClass: AccessToken::class,
                    transformer: static fn (?AccessToken $accessToken) => $accessToken?->increaseLevel(),
                    isTransformedResultNormalized: true,
                    renameTo: 'token',
                ),
            ]),
        );

        // -- Assert
        self::assertSame(6, $result['projects'][0]['token']['accessLevel']);
        self::assertNull($result['projects'][1]['token']);
    }
}
