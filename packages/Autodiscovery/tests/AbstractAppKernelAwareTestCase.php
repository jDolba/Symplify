<?php declare(strict_types=1);

namespace Symplify\Autodiscovery\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symplify\Autodiscovery\Tests\DependencyInjection\AudiscoveryTestingKernel;

abstract class AbstractAppKernelAwareTestCase extends TestCase
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
        $kernel = new AudiscoveryTestingKernel();
        $kernel->boot();

        return $kernel->getContainer();
    }
}
