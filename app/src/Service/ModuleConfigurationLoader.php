<?php

namespace App\Service;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\ValueObject\GrantOption;
use App\Module\Authorization\ValueObject\GrantOptionsCollection;
use App\Module\Authorization\ValueObject\GrantType;
use App\Module\ModuleRegistry\Entity\Module;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ModuleConfigurationLoader
{
    private string $modulesPath;
    private const MODULE_CONFIG_FILE_NAME = 'module.yaml';

    public function __construct(string $projectDir)
    {
        $this->modulesPath = $projectDir . '/src/Module';
    }

    public function load(): array
    {
        $finder = new Finder();
        $finder->files()->in($this->modulesPath)->name(self::MODULE_CONFIG_FILE_NAME);

        $modules = [];
        foreach ($finder as $file) {
            $config = Yaml::parseFile($file->getRealPath());
            if (!isset($config['app_module'])) {
                continue;
            }

            $modules[] = $config['app_module'];

//            $module = $this->mapToModule($config['app_module']);
//            $modules[] = [
//                'module' => $module,
//                'grants' => array_map(
//                    fn ($data) => $this->mapToGrant($data, $module),
//                        $config['app_module']['grants'] ?? []
//                )
//            ];
        }
        return $modules;
    }

    private function mapToModule(array $data): Module
    {
        $module = new Module();
        $module->setNamespace($data['namespace']);
        $module->setDescription($data['description']);
        $module->setActive($data['active']);
        return $module;
    }

    private function mapToGrant(array $data, Module $module): AuthGrant
    {
        $grant = new AuthGrant($data['slug'], $module);
        $grant->setName($data['name']);
        $grant->setDescription($data['description']);
        $grant->setType(GrantType::from($data['type']));

        $grant->setOptions(new GrantOptionsCollection(...array_map(
            fn ($opt) => new GrantOption($opt['label'], $opt['optionSlug']),
            $data['options'] ?? []
        )));
        return $grant;
    }
}