<?php declare(strict_types=1);

namespace Symplify\Statie\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symplify\Statie\HttpKernel\StatieKernel;

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
            $statieKernel = new StatieKernel(true);
            // always start fresh copy for tests
            $statieKernel->reboot(sys_get_temp_dir() . '/statie_tests');

            self::$cachedContainer = $statieKernel->getContainer();
        }

        $this->container = self::$cachedContainer;

        parent::__construct($name, $data, $dataName);
    }
}
