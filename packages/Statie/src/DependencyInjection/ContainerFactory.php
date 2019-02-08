<?php declare(strict_types=1);

namespace Symplify\Statie\DependencyInjection;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symplify\Statie\HttpKernel\StatieKernel;

final class ContainerFactory
{
    public function create(): ContainerInterface
    {
        $statieKernel = new StatieKernel($this->isDebug());
        $statieKernel->boot();

        return $statieKernel->getContainer();
    }

    public function createWithConfig(string $config): ContainerInterface
    {
        $statieKernel = new StatieKernel($this->isDebug(), $config);

        // in tests we need to invalidate cache
        if (defined('PHPUNIT_COMPOSER_INSTALL')) {
            $statieKernel->reboot(sys_get_temp_dir() . '/statie_tests' . rand(1, 100000000));
        }

        $statieKernel->boot();

        return $statieKernel->getContainer();
    }

    private function isDebug(): bool
    {
        $argvInput = new ArgvInput();
        return (bool) $argvInput->hasParameterOption(['--debug', '-v', '-vv', '-vvv']);
    }
}
