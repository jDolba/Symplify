<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symplify\EasyCodingStandard\DependencyInjection\ContainerFactory;
use Symplify\EasyCodingStandard\HttpKernel\EasyCodingStandardKernel;

abstract class AbstractConfigContainerAwareTestCase extends TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param mixed[] $data
     * @param int|string $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $easyCodingStandardKernel = new EasyCodingStandardKernel(false, [$this->provideConfig()]);
        $easyCodingStandardKernel->boot();
        $easyCodingStandardKernel->reboot(sys_get_temp_dir() . '/ecs_tests');

        $this->container = $easyCodingStandardKernel->getContainer();

        parent::__construct($name, $data, $dataName);
    }

    abstract protected function provideConfig(): string;
}
