<?php

namespace App\Module\ModuleRegistry\Service;
use App\Module\ModuleRegistry\Entity\Module;
use Symfony\Component\Yaml\Yaml;

class ModulesConfigurationLoader
{
    private const CONFIG_YAML = '/../config/modules.yaml';

    /**
     * @return Module[]
     */
    public function getModules(): array
    {
        $configuration = Yaml::parseFile(__DIR__ . self::CONFIG_YAML);
        return array_map(
            fn ($data) => $this->mapToModule($data),
            $configuration['app_modules']
        );
    }

    private function mapToModule(array $data): Module
    {
        $module = new Module();
        $module->setNamespace($data['namespace']);
        $module->setDescription($data['description']);
        $module->setActive($data['active']);
        return $module;
    }
}