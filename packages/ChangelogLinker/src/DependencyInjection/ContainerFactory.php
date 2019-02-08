<?php declare(strict_types=1);

namespace Symplify\ChangelogLinker\DependencyInjection;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symplify\ChangelogLinker\HttpKernel\ChangelogLinkerKernel;

final class ContainerFactory
{
    public function create(): ContainerInterface
    {
        $changelogLinkerKernel = new ChangelogLinkerKernel($this->isDebug());
        $changelogLinkerKernel->boot();

        return $changelogLinkerKernel->getContainer();
    }

    public function createWithConfig(string $config): ContainerInterface
    {
        $changelogLinkerKernel = new ChangelogLinkerKernel($this->isDebug(), $config);
        $changelogLinkerKernel->boot();

        return $changelogLinkerKernel->getContainer();
    }

    private function isDebug(): bool
    {
        $argvInput = new ArgvInput();
        return (bool) $argvInput->hasParameterOption(['--debug', '-v', '-vv', '-vvv']);
    }
}
