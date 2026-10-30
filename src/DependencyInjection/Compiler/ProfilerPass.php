<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\DependencyInjection\Compiler;

use DigitalCraftsman\DeserializingConnection\Profiler\DeserializingConnectionDataCollector;
use DigitalCraftsman\DeserializingConnection\Profiler\ProfilingTracer;
use DigitalCraftsman\DeserializingConnection\Profiler\Tracer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The traces are only collected when the Symfony profiler is available. Otherwise, the null tracer is used, and the profiler services are
 * removed.
 */
final readonly class ProfilerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('profiler')) {
            $container->removeDefinition(DeserializingConnectionDataCollector::class);

            return;
        }

        $container->setAlias(Tracer::class, ProfilingTracer::class);
    }
}
