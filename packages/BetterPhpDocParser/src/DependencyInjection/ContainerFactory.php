<?php declare(strict_types=1);

namespace Symplify\BetterPhpDocParser\DependencyInjection;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symplify\BetterPhpDocParser\HttpKernel\BetterPhpDocParserKernel;

final class ContainerFactory
{
    public function create(): ContainerInterface
    {
        $betterPhpDocParserKernelKernel = new BetterPhpDocParserKernel($this->isDebug());
        $betterPhpDocParserKernelKernel->boot();

        return $betterPhpDocParserKernelKernel->getContainer();
    }

    private function isDebug(): bool
    {
        $argvInput = new ArgvInput();
        return (bool) $argvInput->hasParameterOption(['--debug', '-v', '-vv', '-vvv']);
    }
}
