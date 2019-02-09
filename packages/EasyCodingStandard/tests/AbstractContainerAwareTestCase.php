<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symplify\EasyCodingStandard\DependencyInjection\ContainerFactory;
use Symplify\EasyCodingStandard\HttpKernel\EasyCodingStandardKernel;

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
            $easyCodingStandardKernel = new EasyCodingStandardKernel(false);
            $easyCodingStandardKernel->boot();
            $easyCodingStandardKernel->reboot(sys_get_temp_dir() . '/ecs_tests');

            self::$cachedContainer = $easyCodingStandardKernel->getContainer();
        }

        $this->container = self::$cachedContainer;

        parent::__construct($name, $data, $dataName);
    }
}
