<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\DependencyInjection;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symplify\EasyCodingStandard\HttpKernel\EasyCodingStandardKernel;

final class ContainerFactory
{
    public function create(): ContainerInterface
    {
        $kernel = new EasyCodingStandardKernel([], $this->isDebug());
        $kernel->boot();

        return $kernel->getContainer();
    }

    /**
     * @param string[] $configs
     */
    public function createWithConfigs(array $configs): ContainerInterface
    {
        $kernel = new EasyCodingStandardKernel($configs, $this->isDebug());
        $kernel->boot();

        return $kernel->getContainer();
    }

    private function isDebug(): bool
    {
        $argvInput = new ArgvInput();
        return (bool) $argvInput->hasParameterOption(['--debug', '-v', '-vv', '-vvv']);
    }
}
