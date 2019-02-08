<?php declare(strict_types=1);

use Symfony\Component\Console\Input\ArgvInput;
use Symplify\PackageBuilder\Configuration\ConfigFileFinder;
use Symplify\Statie\DependencyInjection\ContainerFactory;

// Detect configuration from input
ConfigFileFinder::detectFromInput('statie', new ArgvInput());

// Fallback to file in root
$configFile = ConfigFileFinder::provide('statie', ['statie.yml', 'statie.yaml']);

$containerFactory = new ContainerFactory();
if ($configFile) {
    return $containerFactory->createWithConfig($configFile);
}

return $containerFactory->create();
