<?php declare(strict_types=1);

namespace Symplify\Autodiscovery\DependencyInjection;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArgvInput;

final class ContainerFactory
{
    public function create(): ContainerInterface
    {
        $autodiscoveryKernel = new AutodiscoveryKernel($this->isDebug());
        $autodiscoveryKernel->boot();

        return $autodiscoveryKernel->getContainer();
    }

    private function isDebug(): bool
    {
        $argvInput = new ArgvInput();
        return (bool) $argvInput->hasParameterOption(['--debug', '-v', '-vv', '-vvv']);
    }
}
