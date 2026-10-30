<?php

namespace DigitalCraftsman\DeserializingConnection;

use DigitalCraftsman\DeserializingConnection\DependencyInjection\Compiler\ProfilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore
 */
final class DeserializingConnectionBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Must run before the profiler pass of the framework bundle, which registers the data collectors
        $container->addCompilerPass(new ProfilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
    }
}
