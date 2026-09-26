<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use DigitalCraftsman\DeserializingConnection\Test\DTO\Company;
use DigitalCraftsman\DeserializingConnection\Test\DTO\CompanyWithProjects;
use DigitalCraftsman\DeserializingConnection\Test\DTO\Duration;
use DigitalCraftsman\DeserializingConnection\Test\DTO\ProjectWithTimeEntries;
use DigitalCraftsman\DeserializingConnection\Test\DTO\TimeEntry;
use DigitalCraftsman\DeserializingConnection\Test\DTO\User;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\CompanyId;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectId;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\ProjectIdList;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserId;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\ArrayNormalizableNormalizer;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\IntNormalizableNormalizer;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\StringNormalizableNormalizer;
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
#[CoversClass(TypedDenormalizer::class)]
#[CoversClass(DTO\ResultTransformers::class)]
#[CoversClass(DTO\ResultTransformer::class)]
#[CoversClass(Exception\ElementNotFound::class)]
#[CoversClass(Exception\SingleValueTransformationMustNotContainRenaming::class)]
#[CoversClass(Exception\IndexMustBeString::class)]
#[CoversClass(DTO\Exception\ConflictBetweenKeysAndRenameToConfiguration::class)]
#[CoversClass(Exception\DecoderTypeKeyLevelIsNotAnArray::class)]
final class DeserializingConnectionTest extends ConnectionTestCase
{
    private DeserializingConnection $deserializingConnection;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $typedDenormalizer = new TypedDenormalizer(
            new Serializer(
                normalizers: [
                    new StringNormalizableNormalizer(),
                    new ArrayNormalizableNormalizer(),
                    new IntNormalizableNormalizer(),
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
            resultTransformerRunner: new ResultTransformerRunner($typedDenormalizer),
        );
    }

    #[Test]
    public function find_one_works(): void
    {
        // -- Arrange
        $userIdString = '417df760-0d16-408f-8201-ec7760dee9fb';
        $staticAdditionalId = ProjectId::fromString('260ca83c-de97-423a-96eb-6a697372ec9e');
        $expectedResult = new User(
            userId: UserId::fromString($userIdString),
            name: 'JOHN DOE',
            accessibleProjects: new ProjectIdList([
                ProjectId::fromString('05f620c2-ea64-4012-816f-884310f69dd0'),
                ProjectId::fromString('91f47435-208d-4344-990b-ae17bd4b13fa'),
                $staticAdditionalId,
            ]),
            companies: [
                new Company(
                    companyId: CompanyId::fromString('3a3fe751-b621-4baa-a0f1-41b660ca877b'),
                    name: 'STARK INDUSTRIES',
                ),
            ],
        );

        // -- Act
        $user = $this->deserializingConnection->findOne(
            sql: <<<'SQL'
                SELECT
                    '417df760-0d16-408f-8201-ec7760dee9fb' AS "userId",
                    'John Doe' AS name,
                    '["05f620c2-ea64-4012-816f-884310f69dd0", "91f47435-208d-4344-990b-ae17bd4b13fa"]' AS "accessibleProjects",
                    '[
                        {"companyId": "3a3fe751-b621-4baa-a0f1-41b660ca877b", "name": "Stark Industries"}
                    ]' AS "companies"
                WHERE '417df760-0d16-408f-8201-ec7760dee9fb' = :userId
                SQL,
            class: User::class,
            parameters: [
                'userId' => $userIdString,
            ],
            decoderTypes: [
                'accessibleProjects' => DTO\DecoderType::JSON,
                'companies' => DTO\DecoderType::JSON,
            ],
            resultTransformers: [
                DTO\ResultTransformer::toTransform(
                    key: 'name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name): string => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
                DTO\ResultTransformer::toTransform(
                    key: 'accessibleProjects',
                    denormalizeResultToClass: ProjectIdList::class,
                    transformer: static fn (ProjectIdList $ids): ProjectIdList => $ids->addId($staticAdditionalId),
                    isTransformedResultNormalized: true,
                ),
                DTO\ResultTransformer::toTransform(
                    key: 'companies.*.name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name): string => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $user);
    }

    #[Test]
    public function find_one_works_without_results(): void
    {
        // -- Arrange
        $expectedResult = null;

        // -- Act
        $user = $this->deserializingConnection->findOne(
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
                'accessibleProjects' => DTO\DecoderType::JSON,
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $user);
    }

    #[Test]
    public function find_one_fails_with_invalid_result_transformer_configuration(): void
    {
        // -- Assert
        $this->expectException(DTO\Exception\ConflictBetweenKeysAndRenameToConfiguration::class);

        // -- Act
        $this->deserializingConnection->findOne(
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
                'accessibleProjects' => DTO\DecoderType::JSON,
            ],
            resultTransformers: [
                DTO\ResultTransformer::toRename(
                    key: 'name',
                    renameTo: 'firstName',
                ),
                DTO\ResultTransformer::toRename(
                    key: 'firstName',
                    renameTo: 'lastName',
                ),
            ],
        );
    }

    #[Test]
    public function get_one_works(): void
    {
        // -- Arrange
        $userIdString = '417df760-0d16-408f-8201-ec7760dee9fb';
        $expectedResult = new User(
            userId: UserId::fromString($userIdString),
            name: 'JOHN DOE',
            accessibleProjects: new ProjectIdList([
                ProjectId::fromString('05f620c2-ea64-4012-816f-884310f69dd0'),
                ProjectId::fromString('91f47435-208d-4344-990b-ae17bd4b13fa'),
            ]),
            companies: [],
        );

        // -- Act
        $user = $this->deserializingConnection->getOne(
            sql: <<<'SQL'
                SELECT
                    '417df760-0d16-408f-8201-ec7760dee9fb' AS "userId",
                    'John Doe' AS name,
                    '["05f620c2-ea64-4012-816f-884310f69dd0", "91f47435-208d-4344-990b-ae17bd4b13fa"]' AS "accessibleProjects",
                    '[]' AS "companies"
                WHERE '417df760-0d16-408f-8201-ec7760dee9fb' = :userId
                SQL,
            class: User::class,
            parameters: [
                'userId' => $userIdString,
            ],
            decoderTypes: [
                'accessibleProjects' => DTO\DecoderType::JSON,
                'companies' => DTO\DecoderType::JSON,
            ],
            resultTransformers: [
                DTO\ResultTransformer::toTransform(
                    key: 'name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name): string => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $user);
    }

    #[Test]
    public function get_one_fails_with_invalid_configuration(): void
    {
        // -- Arrange
        $userIdString = '417df760-0d16-408f-8201-ec7760dee9fb';
        $expectedResult = new User(
            userId: UserId::fromString($userIdString),
            name: 'JOHN DOE',
            accessibleProjects: new ProjectIdList([
                ProjectId::fromString('05f620c2-ea64-4012-816f-884310f69dd0'),
                ProjectId::fromString('91f47435-208d-4344-990b-ae17bd4b13fa'),
            ]),
            companies: [],
        );

        // -- Act
        $user = $this->deserializingConnection->getOne(
            sql: <<<'SQL'
                SELECT
                    '417df760-0d16-408f-8201-ec7760dee9fb' AS "userId",
                    'John Doe' AS name,
                    '["05f620c2-ea64-4012-816f-884310f69dd0", "91f47435-208d-4344-990b-ae17bd4b13fa"]' AS "accessibleProjects",
                    '[]' AS "companies"
                WHERE '417df760-0d16-408f-8201-ec7760dee9fb' = :userId
                SQL,
            class: User::class,
            parameters: [
                'userId' => $userIdString,
            ],
            decoderTypes: [
                'accessibleProjects' => DTO\DecoderType::JSON,
                'companies' => DTO\DecoderType::JSON,
            ],
            resultTransformers: [
                DTO\ResultTransformer::toTransform(
                    key: 'name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name): string => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
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
                'accessibleProjects' => DTO\DecoderType::JSON,
            ],
        );
    }

    #[Test]
    public function find_one_from_single_value_works(): void
    {
        // -- Arrange
        $userIdString = '417df760-0d16-408f-8201-ec7760dee9fb';
        $expectedResult = UserId::fromString($userIdString);

        // -- Act
        $userId = $this->deserializingConnection->findOneFromSingleValue(
            sql: <<<'SQL'
                SELECT
                    '417df760-0d16-408f-8201-ec7760dee9fb'
                WHERE '417df760-0d16-408f-8201-ec7760dee9fb' = :userId
                SQL,
            class: UserId::class,
            parameters: [
                'userId' => $userIdString,
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $userId);
    }

    #[Test]
    public function find_one_from_single_value_works_with_decoding_and_result_transformation(): void
    {
        // -- Arrange
        $expectedResult = new Duration(20);

        // -- Act
        $duration = $this->deserializingConnection->findOneFromSingleValue(
            sql: <<<'SQL'
                SELECT
                    '15'
                SQL,
            class: Duration::class,
            decoderType: DTO\DecoderType::INT,
            resultTransformer: DTO\ResultTransformer::toTransform(
                key: 'key',
                denormalizeResultToClass: null,
                transformer: static fn (int $value): int => $value + 5,
                isTransformedResultNormalized: false,
            ),
        );

        // -- Assert
        self::assertEquals($expectedResult, $duration);
    }

    #[Test]
    public function find_one_from_single_value_works_without_results(): void
    {
        // -- Act
        $userId = $this->deserializingConnection->findOneFromSingleValue(
            sql: <<<'SQL'
                WITH empty_table AS (
                    SELECT 1
                    WHERE false
                )
                SELECT *
                FROM empty_table
                SQL,
            class: UserId::class,
        );

        // -- Assert
        self::assertNull($userId);
    }

    #[Test]
    public function find_one_from_single_value_fails_with_renaming_in_transformation(): void
    {
        // -- Assert
        $this->expectException(Exception\SingleValueTransformationMustNotContainRenaming::class);

        // -- Act
        $this->deserializingConnection->findOneFromSingleValue(
            sql: <<<'SQL'
                WITH empty_table AS (
                    SELECT 1
                    WHERE false
                )
                SELECT *
                FROM empty_table
                SQL,
            class: UserId::class,
            resultTransformer: DTO\ResultTransformer::toTransformAndRename(
                key: 'key',
                denormalizeResultToClass: null,
                transformer: static fn (string $name): string => strtoupper($name),
                isTransformedResultNormalized: false,
                renameTo: 'renamedKey',
            ),
        );
    }

    #[Test]
    public function get_one_from_single_value_works(): void
    {
        // -- Arrange
        $userIdString = '417df760-0d16-408f-8201-ec7760dee9fb';
        $expectedResult = UserId::fromString($userIdString);

        // -- Act
        $userId = $this->deserializingConnection->getOneFromSingleValue(
            sql: <<<'SQL'
                SELECT
                    '417df760-0d16-408f-8201-ec7760dee9fb'
                WHERE '417df760-0d16-408f-8201-ec7760dee9fb' = :userId
                SQL,
            class: UserId::class,
            parameters: [
                'userId' => $userIdString,
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $userId);
    }

    #[Test]
    public function get_one_from_single_value_works_with_decoding_and_result_transformation(): void
    {
        // -- Arrange
        $expectedResult = new Duration(20);

        // -- Act
        $duration = $this->deserializingConnection->getOneFromSingleValue(
            sql: <<<'SQL'
                SELECT
                    '15'
                SQL,
            class: Duration::class,
            decoderType: DTO\DecoderType::INT,
            resultTransformer: DTO\ResultTransformer::toTransform(
                key: 'key',
                denormalizeResultToClass: null,
                transformer: static fn (int $value): int => $value + 5,
                isTransformedResultNormalized: false,
            ),
        );

        // -- Assert
        self::assertEquals($expectedResult, $duration);
    }

    #[Test]
    public function get_one_from_single_value_fails_without_results(): void
    {
        // -- Assert
        $this->expectException(Exception\ElementNotFound::class);

        // -- Act
        $this->deserializingConnection->getOneFromSingleValue(
            sql: <<<'SQL'
                WITH empty_table AS (
                    SELECT 1
                    WHERE false
                )
                SELECT *
                FROM empty_table
                SQL,
            class: UserId::class,
        );
    }

    #[Test]
    public function get_one_from_single_value_fails_with_renaming_in_transformation(): void
    {
        // -- Assert
        $this->expectException(Exception\SingleValueTransformationMustNotContainRenaming::class);

        // -- Act
        $this->deserializingConnection->getOneFromSingleValue(
            sql: <<<'SQL'
                WITH empty_table AS (
                    SELECT 1
                    WHERE false
                )
                SELECT *
                FROM empty_table
                SQL,
            class: UserId::class,
            resultTransformer: DTO\ResultTransformer::toTransformAndRename(
                key: 'key',
                denormalizeResultToClass: null,
                transformer: static fn (string $name): string => strtoupper($name),
                isTransformedResultNormalized: false,
                renameTo: 'renamedKey',
            ),
        );
    }

    #[Test]
    public function find_array_works(): void
    {
        // -- Arrange
        $expectedResult = [
            new User(
                userId: UserId::fromString('417df760-0d16-408f-8201-ec7760dee9fb'),
                name: 'JOHN DOE',
                accessibleProjects: new ProjectIdList([
                    ProjectId::fromString('05f620c2-ea64-4012-816f-884310f69dd0'),
                    ProjectId::fromString('91f47435-208d-4344-990b-ae17bd4b13fa'),
                ]),
                companies: [],
            ),
            new User(
                userId: UserId::fromString('ef64a500-db7b-49a8-b670-8eca24936688'),
                name: 'JANE DOE',
                accessibleProjects: ProjectIdList::emptyList(),
                companies: [],
            ),
        ];

        // -- Act
        $users = $this->deserializingConnection->findArray(
            sql: <<<'SQL'
                SELECT
                        user_id AS "userId",
                        name,
                        accessible_projects AS "accessibleProjects",
                        '[]' AS "companies"
                    FROM (
                        VALUES
                            ('417df760-0d16-408f-8201-ec7760dee9fb', 'John Doe', '["05f620c2-ea64-4012-816f-884310f69dd0", "91f47435-208d-4344-990b-ae17bd4b13fa"]'),
                            ('ef64a500-db7b-49a8-b670-8eca24936688', 'Jane Doe', '[]')
                    ) AS users(user_id, name, accessible_projects)
                SQL,
            class: User::class,
            decoderTypes: [
                'accessibleProjects' => DTO\DecoderType::JSON,
                'companies' => DTO\DecoderType::JSON,
            ],
            resultTransformers: [
                DTO\ResultTransformer::toTransform(
                    key: 'name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name): string => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $users);
    }

    #[Test]
    public function find_array_works_with_indexed_by(): void
    {
        // -- Arrange
        $expectedResult = [
            '417df760-0d16-408f-8201-ec7760dee9fb' => new User(
                userId: UserId::fromString('417df760-0d16-408f-8201-ec7760dee9fb'),
                name: 'JOHN DOE',
                accessibleProjects: new ProjectIdList([
                    ProjectId::fromString('05f620c2-ea64-4012-816f-884310f69dd0'),
                    ProjectId::fromString('91f47435-208d-4344-990b-ae17bd4b13fa'),
                ]),
                companies: [],
            ),
            'ef64a500-db7b-49a8-b670-8eca24936688' => new User(
                userId: UserId::fromString('ef64a500-db7b-49a8-b670-8eca24936688'),
                name: 'JANE DOE',
                accessibleProjects: ProjectIdList::emptyList(),
                companies: [],
            ),
        ];

        // -- Act
        $users = $this->deserializingConnection->findArray(
            sql: <<<'SQL'
                SELECT
                        user_id AS "userId",
                        name,
                        accessible_projects AS "accessibleProjects",
                        '[]' AS "companies"
                    FROM (
                        VALUES
                            ('417df760-0d16-408f-8201-ec7760dee9fb', 'John Doe', '["05f620c2-ea64-4012-816f-884310f69dd0", "91f47435-208d-4344-990b-ae17bd4b13fa"]'),
                            ('ef64a500-db7b-49a8-b670-8eca24936688', 'Jane Doe', '[]')
                    ) AS users(user_id, name, accessible_projects)
                SQL,
            class: User::class,
            decoderTypes: [
                'accessibleProjects' => DTO\DecoderType::JSON,
                'companies' => DTO\DecoderType::JSON,
            ],
            resultTransformers: [
                DTO\ResultTransformer::toTransform(
                    key: 'name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name): string => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ],
            indexedBy: static fn (User $user): string => (string) $user->userId,
        );

        // -- Assert
        self::assertEquals($expectedResult, $users);
    }

    #[Test]
    public function find_array_fails_with_invalid_index_generation(): void
    {
        // -- Assert
        $this->expectException(Exception\IndexMustBeString::class);

        // -- Act
        /**
         * @psalm-suppress InvalidArgument Provided on purpose to test the exception
         */
        $this->deserializingConnection->findArray(
            sql: <<<'SQL'
                SELECT
                        user_id AS "userId",
                        name,
                        accessible_projects AS "accessibleProjects",
                        '[]' AS "companies"
                    FROM (
                        VALUES
                            ('417df760-0d16-408f-8201-ec7760dee9fb', 'John Doe', '["05f620c2-ea64-4012-816f-884310f69dd0", "91f47435-208d-4344-990b-ae17bd4b13fa"]'),
                            ('ef64a500-db7b-49a8-b670-8eca24936688', 'Jane Doe', '[]')
                    ) AS users(user_id, name, accessible_projects)
                SQL,
            class: User::class,
            decoderTypes: [
                'accessibleProjects' => DTO\DecoderType::JSON,
                'companies' => DTO\DecoderType::JSON,
            ],
            indexedBy: static fn (User $user): int => 15,
        );
    }

    #[Test]
    public function find_generator_works(): void
    {
        // -- Arrange
        $expectedUsers = [
            new User(
                userId: UserId::fromString('417df760-0d16-408f-8201-ec7760dee9fb'),
                name: 'JOHN DOE',
                accessibleProjects: new ProjectIdList([
                    ProjectId::fromString('05f620c2-ea64-4012-816f-884310f69dd0'),
                    ProjectId::fromString('91f47435-208d-4344-990b-ae17bd4b13fa'),
                ]),
                companies: [],
            ),
            new User(
                userId: UserId::fromString('ef64a500-db7b-49a8-b670-8eca24936688'),
                name: 'JANE DOE',
                accessibleProjects: ProjectIdList::emptyList(),
                companies: [],
            ),
        ];

        // -- Act
        $users = $this->deserializingConnection->findGenerator(
            sql: <<<'SQL'
                SELECT
                        user_id AS "userId",
                        name,
                        accessible_projects AS "accessibleProjects",
                        '[]' AS "companies"
                    FROM (
                        VALUES
                            ('417df760-0d16-408f-8201-ec7760dee9fb', 'John Doe', '["05f620c2-ea64-4012-816f-884310f69dd0", "91f47435-208d-4344-990b-ae17bd4b13fa"]'),
                            ('ef64a500-db7b-49a8-b670-8eca24936688', 'Jane Doe', '[]')
                    ) AS users(user_id, name, accessible_projects)
                SQL,
            class: User::class,
            decoderTypes: [
                'accessibleProjects' => DTO\DecoderType::JSON,
                'companies' => DTO\DecoderType::JSON,
            ],
            resultTransformers: [
                DTO\ResultTransformer::toTransform(
                    key: 'name',
                    denormalizeResultToClass: null,
                    transformer: static fn (string $name): string => strtoupper($name),
                    isTransformedResultNormalized: false,
                ),
            ],
        );

        // -- Assert
        self::assertSame(\Generator::class, $users::class);
        $usersResult = iterator_to_array($users);
        self::assertEquals($expectedUsers, $usersResult);
    }

    #[Test]
    public function find_one_works_with_nested_decoder_types(): void
    {
        // -- Arrange
        $expectedResult = new CompanyWithProjects(
            name: 'Stark Industries',
            projects: [
                new ProjectWithTimeEntries(
                    name: 'Project 1',
                    timeEntries: [
                        new TimeEntry(
                            description: 'Planning',
                            hours: 2.0,
                        ),
                        new TimeEntry(
                            description: 'Development',
                            hours: null,
                        ),
                    ],
                ),
                new ProjectWithTimeEntries(
                    name: 'Project 2',
                    timeEntries: [],
                ),
                new ProjectWithTimeEntries(
                    name: 'Project 3',
                    timeEntries: [
                        new TimeEntry(
                            description: 'Planning',
                            hours: 1.5,
                        ),
                    ],
                ),
            ],
        );

        // -- Act
        $company = $this->deserializingConnection->findOne(
            sql: <<<'SQL'
                SELECT
                    'Stark Industries' AS name,
                    jsonb_build_array(
                        jsonb_build_object(
                            'name', 'Project 1',
                            'timeEntries', jsonb_build_array(
                                jsonb_build_object('description', 'Planning', 'hours', 2),
                                jsonb_build_object('description', 'Development', 'hours', null)
                            )
                        ),
                        jsonb_build_object(
                            'name', 'Project 2',
                            'timeEntries', jsonb_build_array()
                        ),
                        jsonb_build_object(
                            'name', 'Project 3',
                            'timeEntries', jsonb_build_array(
                                jsonb_build_object('description', 'Planning', 'hours', 1.5)
                            )
                        )
                    ) AS projects
                WHERE 'Stark Industries' = :name
                SQL,
            class: CompanyWithProjects::class,
            parameters: [
                'name' => 'Stark Industries',
            ],
            decoderTypes: [
                'projects' => DTO\DecoderType::JSON,
                'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $company);
    }

    #[Test]
    public function find_one_works_with_nested_decoder_types_and_result_transformers(): void
    {
        // -- Arrange
        $expectedResult = new CompanyWithProjects(
            name: 'Stark Industries',
            projects: [
                new ProjectWithTimeEntries(
                    name: 'Project 1',
                    timeEntries: [
                        new TimeEntry(
                            description: 'Planning',
                            hours: 4.0,
                        ),
                        new TimeEntry(
                            description: 'Development',
                            hours: null,
                        ),
                    ],
                ),
            ],
        );

        // -- Act
        $company = $this->deserializingConnection->findOne(
            sql: <<<'SQL'
                SELECT
                    'Stark Industries' AS name,
                    '[{"name": "Project 1", "timeEntries": [{"description": "Planning", "hours": "2"}, {"description": "Development", "hours": null}]}]' AS projects
                SQL,
            class: CompanyWithProjects::class,
            decoderTypes: [
                'projects' => DTO\DecoderType::JSON,
                'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
            ],
            resultTransformers: [
                // Is run after the decoding, so the hours is already a float.
                DTO\ResultTransformer::toTransform(
                    key: 'projects.*.timeEntries.*.hours',
                    denormalizeResultToClass: null,
                    transformer: static fn (?float $hours): ?float => $hours !== null
                        ? $hours * 2.0
                        : null,
                    isTransformedResultNormalized: false,
                ),
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $company);
    }

    #[Test]
    public function find_one_fails_with_nested_decoder_type_on_undecoded_value(): void
    {
        // -- Assert
        $this->expectException(Exception\DecoderTypeKeyLevelIsNotAnArray::class);

        // -- Act
        $this->deserializingConnection->findOne(
            sql: <<<'SQL'
                SELECT
                    'Stark Industries' AS name,
                    '[{"name": "Project 1", "timeEntries": [{"description": "Planning", "hours": 2}]}]' AS projects
                SQL,
            class: CompanyWithProjects::class,
            decoderTypes: [
                'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
            ],
        );
    }

    #[Test]
    public function find_one_fails_with_nested_decoder_type_key_ending_with_array_identifier(): void
    {
        // -- Assert
        $this->expectException(DTO\Exception\ResultTransformationKeyCanNotEndWithAnArrayIdentifier::class);

        // -- Act
        $this->deserializingConnection->findOne(
            sql: <<<'SQL'
                SELECT
                    'Stark Industries' AS name,
                    '[]' AS projects
                SQL,
            class: CompanyWithProjects::class,
            decoderTypes: [
                'projects' => DTO\DecoderType::JSON,
                'projects.*' => DTO\DecoderType::JSON,
            ],
        );
    }

    #[Test]
    public function get_one_works_with_nested_decoder_types(): void
    {
        // -- Arrange
        $expectedResult = new CompanyWithProjects(
            name: 'Stark Industries',
            projects: [
                new ProjectWithTimeEntries(
                    name: 'Project 1',
                    timeEntries: [
                        new TimeEntry(
                            description: 'Planning',
                            hours: 2.0,
                        ),
                        new TimeEntry(
                            description: 'Development',
                            hours: null,
                        ),
                    ],
                ),
                new ProjectWithTimeEntries(
                    name: 'Project 2',
                    timeEntries: [],
                ),
                new ProjectWithTimeEntries(
                    name: 'Project 3',
                    timeEntries: [
                        new TimeEntry(
                            description: 'Planning',
                            hours: 1.5,
                        ),
                    ],
                ),
            ],
        );

        // -- Act
        $company = $this->deserializingConnection->getOne(
            sql: <<<'SQL'
                SELECT
                    'Stark Industries' AS name,
                    jsonb_build_array(
                        jsonb_build_object(
                            'name', 'Project 1',
                            'timeEntries', jsonb_build_array(
                                jsonb_build_object('description', 'Planning', 'hours', 2),
                                jsonb_build_object('description', 'Development', 'hours', null)
                            )
                        ),
                        jsonb_build_object(
                            'name', 'Project 2',
                            'timeEntries', jsonb_build_array()
                        ),
                        jsonb_build_object(
                            'name', 'Project 3',
                            'timeEntries', jsonb_build_array(
                                jsonb_build_object('description', 'Planning', 'hours', 1.5)
                            )
                        )
                    ) AS projects
                WHERE 'Stark Industries' = :name
                SQL,
            class: CompanyWithProjects::class,
            parameters: [
                'name' => 'Stark Industries',
            ],
            decoderTypes: [
                'projects' => DTO\DecoderType::JSON,
                'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $company);
    }

    #[Test]
    public function find_array_works_with_nested_decoder_types(): void
    {
        // -- Arrange
        $expectedResult = [
            new CompanyWithProjects(
                name: 'Stark Industries',
                projects: [
                    new ProjectWithTimeEntries(
                        name: 'Project 1',
                        timeEntries: [
                            new TimeEntry(
                                description: 'Planning',
                                hours: 2.0,
                            ),
                            new TimeEntry(
                                description: 'Development',
                                hours: null,
                            ),
                        ],
                    ),
                ],
            ),
            new CompanyWithProjects(
                name: 'Wayne Enterprises',
                projects: [
                    new ProjectWithTimeEntries(
                        name: 'Project 2',
                        timeEntries: [],
                    ),
                    new ProjectWithTimeEntries(
                        name: 'Project 3',
                        timeEntries: [
                            new TimeEntry(
                                description: 'Planning',
                                hours: 1.5,
                            ),
                        ],
                    ),
                ],
            ),
            new CompanyWithProjects(
                name: 'Acme Corporation',
                projects: [],
            ),
        ];

        // -- Act
        $companies = $this->deserializingConnection->findArray(
            sql: <<<'SQL'
                SELECT
                    name,
                    projects
                FROM (
                    VALUES
                        ('Stark Industries', '[{"name": "Project 1", "timeEntries": [{"description": "Planning", "hours": 2}, {"description": "Development", "hours": null}]}]'::jsonb),
                        ('Wayne Enterprises', '[{"name": "Project 2", "timeEntries": null}, {"name": "Project 3", "timeEntries": [{"description": "Planning", "hours": 1.5}]}]'::jsonb),
                        ('Acme Corporation', null)
                ) AS companies(name, projects)
                SQL,
            class: CompanyWithProjects::class,
            decoderTypes: [
                'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
                'projects' => DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL,
            ],
            resultTransformers: [
                DTO\ResultTransformer::toTransform(
                    key: 'projects.*.timeEntries',
                    denormalizeResultToClass: null,
                    transformer: static fn (?array $timeEntries): array => $timeEntries ?? [],
                    isTransformedResultNormalized: false,
                ),
            ],
        );

        // -- Assert
        self::assertEquals($expectedResult, $companies);
    }

    #[Test]
    public function find_generator_works_with_nested_decoder_types(): void
    {
        // -- Arrange
        $expectedResult = [
            new CompanyWithProjects(
                name: 'Stark Industries',
                projects: [
                    new ProjectWithTimeEntries(
                        name: 'Project 1',
                        timeEntries: [
                            new TimeEntry(
                                description: 'Planning',
                                hours: 2.0,
                            ),
                            new TimeEntry(
                                description: 'Development',
                                hours: null,
                            ),
                        ],
                    ),
                ],
            ),
            new CompanyWithProjects(
                name: 'Wayne Enterprises',
                projects: [
                    new ProjectWithTimeEntries(
                        name: 'Project 2',
                        timeEntries: [],
                    ),
                    new ProjectWithTimeEntries(
                        name: 'Project 3',
                        timeEntries: [
                            new TimeEntry(
                                description: 'Planning',
                                hours: 1.5,
                            ),
                        ],
                    ),
                ],
            ),
            new CompanyWithProjects(
                name: 'Acme Corporation',
                projects: [],
            ),
        ];

        // -- Act
        $companies = $this->deserializingConnection->findGenerator(
            sql: <<<'SQL'
                SELECT
                    name,
                    projects
                FROM (
                    VALUES
                        ('Stark Industries', '[{"name": "Project 1", "timeEntries": [{"description": "Planning", "hours": 2}, {"description": "Development", "hours": null}]}]'::jsonb),
                        ('Wayne Enterprises', '[{"name": "Project 2", "timeEntries": null}, {"name": "Project 3", "timeEntries": [{"description": "Planning", "hours": 1.5}]}]'::jsonb),
                        ('Acme Corporation', null)
                ) AS companies(name, projects)
                SQL,
            class: CompanyWithProjects::class,
            decoderTypes: [
                'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
                'projects' => DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL,
            ],
            resultTransformers: [
                DTO\ResultTransformer::toTransform(
                    key: 'projects.*.timeEntries',
                    denormalizeResultToClass: null,
                    transformer: static fn (?array $timeEntries): array => $timeEntries ?? [],
                    isTransformedResultNormalized: false,
                ),
            ],
        );

        // -- Assert
        $companiesResult = iterator_to_array($companies);
        self::assertEquals($expectedResult, $companiesResult);
    }
}
