<?php declare(strict_types=1);

namespace Symplify\Autodiscovery\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symplify\Autodiscovery\DependencyInjection\AutodiscoveryKernel;

abstract class AbstractContainerAwareTestCase extends TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ContainerInterface|null
     */
    private static $cachedContainer;

    /**
     * @param mixed[] $data
     * @param int|string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        if (self::$cachedContainer === null) {
            self::$cachedContainer = $this->createContainer();
        }
        $this->container = self::$cachedContainer;

        parent::__construct($name, $data, $dataName);
    }

    private function createContainer(): ContainerInterface
    {
        $kernel = new AutodiscoveryKernel($this->isDebug());
        $kernel->boot();

        return $kernel->getContainer();
    }

    private function isDebug(): bool
    {
        $argvInput = new ArgvInput();
        return (bool) $argvInput->hasParameterOption(['--debug', '-v', '-vv', '-vvv']);
    }
}
