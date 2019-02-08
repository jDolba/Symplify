<?php declare(strict_types=1);

namespace Symplify\EasyCodingStandard\HttpKernel;

use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCsFixer\Fixer\FixerInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symplify\EasyCodingStandard\ChangedFilesDetector\CompilerPass\DetectParametersCompilerPass;
use Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\ConflictingCheckersCompilerPass;
use Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\CustomSourceProviderDefinitionCompilerPass;
use Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\FixerWhitespaceConfigCompilerPass;
use Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\RemoveExcludedCheckersCompilerPass;
use Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\RemoveMutualCheckersCompilerPass;
use Symplify\EasyCodingStandard\DependencyInjection\DelegatingLoaderFactory;
use Symplify\PackageBuilder\DependencyInjection\CompilerPass\AutoBindParametersCompilerPass;
use Symplify\PackageBuilder\DependencyInjection\CompilerPass\AutoReturnFactoryCompilerPass;
use Symplify\PackageBuilder\DependencyInjection\CompilerPass\AutowireArrayParameterCompilerPass;
use Symplify\PackageBuilder\DependencyInjection\CompilerPass\AutowireInterfacesCompilerPass;
use Symplify\PackageBuilder\DependencyInjection\CompilerPass\AutowireSinglyImplementedCompilerPass;
use Symplify\PackageBuilder\DependencyInjection\CompilerPass\ConfigurableCollectorCompilerPass;

final class EasyCodingStandardKernel extends Kernel
{
    /**
     * @var string[]
     */
    private $extraConfigFiles = [];

    /**
     * @param string[] $configFiles
     */
    public function __construct(array $configFiles = [], bool $debug = true)
    {
        $this->extraConfigFiles = $configFiles;

        $configFilesHash = md5(serialize($configFiles));

        // debug: require to invalidate container on service files change
        parent::__construct('ecs_' . $configFilesHash, $debug);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/../../config/config.yml');

        foreach ($this->extraConfigFiles as $configFile) {
            $loader->load($configFile);
        }
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/easy_coding_standard';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/easy_coding_standard_logs';
    }

    /**
     * @return BundleInterface[]
     */
    public function registerBundles(): iterable
    {
        return [];
    }

    /**
     * Order matters!
     */
    protected function build(ContainerBuilder $containerBuilder): void
    {
        // needs to be first, since it's adding new service definitions
        $containerBuilder->addCompilerPass(new AutoReturnFactoryCompilerPass());

        // cleanup
        $containerBuilder->addCompilerPass(new RemoveExcludedCheckersCompilerPass());
        $containerBuilder->addCompilerPass(new RemoveMutualCheckersCompilerPass());

        // autowire
        $containerBuilder->addCompilerPass(new AutowireInterfacesCompilerPass([
            FixerInterface::class,
            Sniff::class,
        ]));
        $containerBuilder->addCompilerPass(new AutowireArrayParameterCompilerPass());

        // exceptions
        $containerBuilder->addCompilerPass(new ConflictingCheckersCompilerPass());

        // parameters
        $containerBuilder->addCompilerPass(new DetectParametersCompilerPass());
        $containerBuilder->addCompilerPass(new AutoBindParametersCompilerPass());

        // method calls
        $containerBuilder->addCompilerPass(new FixerWhitespaceConfigCompilerPass());
        $containerBuilder->addCompilerPass(new ConfigurableCollectorCompilerPass());
        $containerBuilder->addCompilerPass(new CustomSourceProviderDefinitionCompilerPass());
        $containerBuilder->addCompilerPass(new AutowireSinglyImplementedCompilerPass());
    }

    /**
     * @param ContainerInterface|ContainerBuilder $container
     */
    protected function getContainerLoader(ContainerInterface $container): DelegatingLoader
    {
        return (new DelegatingLoaderFactory())->createFromContainerBuilderAndKernel($container, $this);
    }
}
