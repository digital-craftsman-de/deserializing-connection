<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

#[CoversClass(Configuration::class)]
final class ConfigurationTest extends TestCase
{
    #[Test]
    public function profiling_is_enabled_by_default(): void
    {
        // -- Act
        $config = new Processor()->processConfiguration(new Configuration(), []);

        // -- Assert
        self::assertSame(['profiling' => true], $config);
    }

    #[Test]
    public function profiling_can_be_disabled(): void
    {
        // -- Act
        $config = new Processor()->processConfiguration(new Configuration(), [
            ['profiling' => false],
        ]);

        // -- Assert
        self::assertSame(['profiling' => false], $config);
    }
}
