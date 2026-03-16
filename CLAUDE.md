# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHP Symfony Bundle (`digital-craftsman/deserializing-connection`) that provides a typed wrapper around Doctrine DBAL to deserialize database results directly into DTOs using Symfony Serializer.

## Development Environment

Docker-based setup with PostgreSQL 16.3 and PHP 8.4/8.5 containers.

```bash
make build          # Build Docker images
make up             # Start PostgreSQL + PHP containers
make install        # Install composer dependencies (PHP 8.5)
make install-8.4    # Install composer dependencies (PHP 8.4)
```

## Common Commands

```bash
# Tests (run inside Docker)
make php-tests                  # Run tests for all PHP versions
make php-8.5-tests              # Run tests with PHP 8.5 only

# Run a single test file
docker compose run --rm php-8.5 ./vendor/bin/phpunit tests/Serializer/DeserializingConnectionTest.php

# Run a single test method
docker compose run --rm php-8.5 ./vendor/bin/phpunit --filter "find_one_works" tests/Serializer/DeserializingConnectionTest.php

# Code style & static analysis
make php-code-validation        # PHP CS Fixer + Psalm

# Mutation testing
make php-mutation-testing       # Infection (requires 100% MSI)

# Full verification
make verify                     # Code validation + tests + mutation testing
```

## Architecture

The bundle has four core services (all autowired, readonly classes):

- **DeserializingConnection** — Main public API. Methods: `findOne`, `getOne`, `findArray`, `findGenerator`, `findOneFromSingleValue`, `getOneFromSingleValue`. Orchestrates query execution, result transformation, and deserialization.
- **DecodingConnection** — Wraps Doctrine DBAL `Connection`. Executes SQL and decodes database values (JSON, bool, int, float) using `DecoderType` enum.
- **TypedDenormalizer** — Wraps Symfony Serializer for type-safe denormalization (array → DTO).
- **ResultTransformerRunner** — Applies `ResultTransformer` pipelines to raw results before deserialization. Supports dotted key paths with array wildcards (e.g., `user.projects.*.name`).

Key DTOs in `src/Serializer/DTO/`: `ResultTransformer` (transformation spec with factory methods), `ResultTransformerKey` (dotted path value object), `ResultTransformers` (validated collection), `DecoderType` (enum for type conversions).

## Code Style

- `declare(strict_types=1)` in all files
- Readonly classes, constructor promotion, match expressions
- Test methods use snake_case (`#[Test]` attribute, not `test` prefix)
- PHP-CS-Fixer with Symfony rules (no yoda style, trailing commas in multiline)
- Psalm for static analysis
- Infection mutation testing enforces 100% mutation score index

## Testing

- PHPUnit 10.5+ with `#[Test]` and `#[CoversClass]` attributes
- Tests run against a real PostgreSQL database (no mocks for DB)
- Base class: `ConnectionTestCase` sets up Doctrine DBAL connection from `.env`
- Test DTOs in `tests/Test/DTO/` and value objects in `tests/Test/ValueObject/`
