<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\DependencyInjection\Compiler;

use DigitalCraftsman\DeserializingConnection\Profiler\DeserializingConnectionDataCollector;
use DigitalCraftsman\DeserializingConnection\Profiler\NullTracer;
use DigitalCraftsman\DeserializingConnection\Profiler\ProfilingTracer;
use DigitalCraftsman\DeserializingConnection\Profiler\Tracer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(ProfilerPass::class)]
final class ProfilerPassTest extends TestCase
{
    #[Test]
    public function profiling_tracer_is_used_with_profiler(): void
    {
        // -- Arrange
        $container = self::containerBuilder();
        $container->register('profiler');

        // -- Act
        new ProfilerPass()->process($container);

        // -- Assert
        self::assertSame(ProfilingTracer::class, (string) $container->getAlias(Tracer::class));
        self::assertTrue($container->hasDefinition(DeserializingConnectionDataCollector::class));
    }

    #[Test]
    public function null_tracer_is_used_without_profiler(): void
    {
        // -- Arrange
        $container = self::containerBuilder();

        // -- Act
        new ProfilerPass()->process($container);

        // -- Assert
        self::assertSame(NullTracer::class, (string) $container->getAlias(Tracer::class));
        self::assertFalse($container->hasDefinition(DeserializingConnectionDataCollector::class));
    }

    private static function containerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register(NullTracer::class);
        $container->register(ProfilingTracer::class);
        $container->register(DeserializingConnectionDataCollector::class);
        $container->setAlias(Tracer::class, NullTracer::class);

        return $container;
    }
}
