<?php declare(strict_types=1);

namespace Symplify\NeonToYamlConverter;

use Nette\Neon\Entity;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Symfony\Component\Yaml\Yaml;
use Symplify\PackageBuilder\Strings\StringFormatConverter;

final class NeonToYamlConverter
{
    /**
     * @todo maybe use to dump env vars
     * @var string[]
     */
    private $environmentVaribales = [];

    /**
     * @var string[]
     */
    private $parametersToReplace = [];

    /**
     * @var StringFormatConverter
     */
    private $stringFormatConverter;

    public function __construct(StringFormatConverter $stringFormatConverter)
    {
        $this->stringFormatConverter = $stringFormatConverter;
    }

    public function convertFile(string $file): string
    {
        $content = FileSystem::read($file);

        $content = $this->convertEnv($content);

        $data = (array) Neon::decode($content);

        foreach ($data as $key => $value) {
            if ($value instanceof Entity) {
                $data[$key] = $this->convertNeonEntityToArray($value);
            }

            if ($key === 'services') {
                $data[$key] = $this->convertServices((array) $value);
            }

            if ($key === 'parameters') {
                $data[$key] = $this->convertParameters((array) $value);
            }

            if ($key === 'includes') {
                unset($data[$key]);
                $data['imports'] = $this->convertIncludes((array) $value);
            }
        }

        $content = Yaml::dump($data, 100, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_OBJECT);

        $content = $this->replaceAppDirAndWwwDir($content);
        $content = $this->replaceTilda($content);

        return $this->replaceOldToNewParameters($content);
    }

    /**
     * @return mixed[]
     */
    private function convertNeonEntityToArray(Entity $entity): array
    {
        return array_merge([
            'value' => $entity->value,

        ], $entity->attributes);
    }

    /**
     * @param mixed[] $data
     * @return mixed[]
     */
    private function convertIncludes(array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = [
                'resource' => $value,
            ];
        }

        return $data;
    }

    /**
     * @param mixed[] $data
     * @return mixed[]
     */
    private function convertServices(array $data): array
    {
        foreach ($data as $name => $service) {
            if (is_int($name)) { // not named
                if (is_string($service)) { // just single-class
                    unset($data[$name]);
                    $name = $service;
                    $data[$name] = null;
                }

                if ($service instanceof Entity) {
                    [$name, $data] = $this->convertServiceEntity($data, $service, $name);
                }
            } elseif ($service instanceof Entity) {
                [$name, $data] = $this->convertServiceEntity($data, $service, $name);
            } elseif (is_string($service)) {
                if (is_string($name) && $service === '~') {
                    $data[$name] = null;
                    continue;
                }

                // probably factory, @see https://symfony.com/doc/current/service_container/factories.html
                if (Strings::contains($service, '::')) {
                    [$factoryClass, $factoryMethod] = explode('::', $service);

                    $data[$name] = [
                        'factory' => [$factoryClass, $factoryMethod],
                    ];
                // probably alias, @see https://symfony.com/doc/current/service_container/alias_private.html#aliasing
                } elseif (Strings::startsWith($service, '@')) {
                    $data[$name] = [
                        'alias' => $service,
                    ];
                // probably service
                } else {
                    $data[$name] = [
                        'class' => $service,
                    ];
                }
            } else { // named service
                $service = $data[$name];
                if (isset($service['class'])) {
                    if ($service['class'] instanceof Entity) {
                        if ($service['class']->attributes) {
                            $service['arguments'] = $service['class']->attributes;
                        }
                        $service['class'] = $service['class']->value;
                    }
                }

                $data[$name] = $service;
            }

            $service = $data[$name];
            if (isset($service['setup'])) {
                foreach ((array) $service['setup'] as $key => $value) {
                    if ($value instanceof Entity) {
                        $service['setup'][$key] = [$value->value, $value->attributes];
                    }
                }

                $service['calls'] = $service['setup'];
                unset($service['setup']);

                $data[$name] = $service;
            }

            $service = $data[$name];
            if (isset($service['arguments'])) {
                foreach ((array) $service['arguments'] as $key => $value) {
                    if ($value instanceof Entity) {
                        if ($value->value === '@env::get') { // enviro value! @see https://symfony.com/blog/new-in-symfony-3-4-advanced-environment-variables
                            $environmentVariable = $value->attributes[0];
                            $this->environmentVaribales[] = $environmentVariable;
                            $service['arguments'][$key] = sprintf('%%ENV(%s)%%', $environmentVariable);
                        }
                    }
                }
            }

            $data[$name] = $service;
        }

        return $data;
    }

    /**
     * @param mixed[] $data
     * @param string|int $name
     * @return mixed[]
     */
    private function convertServiceEntity(array $data, Entity $entity, $name): array
    {
        $class = $entity->value;
        $serviceData = [
            'class' => $class,
            'arguments' => $entity->attributes,
        ];

        if (is_int($name)) { // class-named service
            // is namespaced class?
            if (Strings::contains($serviceData['class'], '\\')) {
                unset($serviceData['class']);
            }

            unset($data[$name]);
            $name = $class;
        }

        $data[$name] = $serviceData;

        return [$name, $data];
    }

    /**
     * @param mixed[] $data
     * @return mixed[]
     */
    private function convertParameters(array $data): array
    {
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            foreach ($value as $key2 => $value2) {
                $newKey = $key . '_' . $key2;
                // camelCase to under_score Yaml convention
                $newKey = $this->stringFormatConverter->camelCaseToUnderscore($newKey);
                $data[$newKey] = $value2;

                $oldKey = $key . '.' . $key2;
                $this->parametersToReplace[$oldKey] = $newKey;
            }

            unset($data[$key]);
        }

        return $data;
    }

    private function replaceAppDirAndWwwDir(string $content): string
    {
        // @see https://symfony.com/blog/new-in-symfony-3-3-a-simpler-way-to-get-the-project-root-directory
        // %appDir% → %kernel.project_dir%/app
        $content = Strings::replace($content, '#%appDir%#', '%kernel.project_dir%/app');

        // %wwwDir% → %kernel.project_dir%/public
        $content = Strings::replace($content, '#%wwwDir%#', '%kernel.project_dir%/public');

        // %kernel.project_dir%/app/..% → %kernel.project_dir%
        return Strings::replace($content, '#%kernel.project_dir%\/app\/\.\.#', '%kernel.project_dir%');
    }

    private function replaceOldToNewParameters(string $content): string
    {
        foreach ($this->parametersToReplace as $oldParameter => $newParamter) {
            $content = Strings::replace($content, '#' . preg_quote($oldParameter) . '#', $newParamter);
        }

        return $content;
    }

    private function replaceTilda(string $content): string
    {
        $content = Strings::replace($content, "#: '~'\n#", ': ~' . PHP_EOL);

        return Strings::replace($content, "#: null\n#", ': ~' . PHP_EOL);
    }

    private function convertEnv(string $content): string
    {
        // https://regex101.com/r/IxBjFD/1
        return Strings::replace($content, "#\@env::get\(\'?(.*?)\'?(,.*?)?\)#ms", "'%ENV($1)%'");
    }
}
