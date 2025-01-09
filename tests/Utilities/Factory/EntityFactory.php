<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory;
use App\Entity\StatusLog;
use App\Tests\Utilities\Factory\Definition\AgreementFactory;
use App\Tests\Utilities\Factory\Definition\AgreementLineFactory;
use App\Tests\Utilities\Factory\Definition\CustomerFactory;
use App\Tests\Utilities\Factory\Definition\ProductFactory;
use App\Tests\Utilities\Factory\Definition\ProductionFactory;
use App\Tests\Utilities\Factory\Definition\StatusLogFactory;
use App\Tests\Utilities\Factory\Definition\UserFactory;
use Doctrine\ORM\EntityManagerInterface;

class EntityFactory
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var \Faker\Generator */
    private $faker;

    const MINIONS = [
        UserFactory::class,
        ProductFactory::class,
        CustomerFactory::class,
        AgreementFactory::class,
        AgreementLineFactory::class,
        ProductionFactory::class,
        StatusLogFactory::class
    ];

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->faker = \Faker\Factory::create('pl_PL');
    }

    public function make(string $className, array $overrideProps = [])
    {
        $supportedClassesMap = [];
        foreach (self::MINIONS as $minion) {
            /** @var FactoryDefinitionInterface $minion */
            $supportedClassesMap[$minion::supports()] = $minion;
        }

        if (!isset($supportedClassesMap[$className])) {
            throw new \Exception('Factory is unable to create the instance of: ' . $className);
        }

        $minionClass = $supportedClassesMap[$className];

        /** @var FactoryDefinitionInterface $factoryDefinition */
        $factoryInstance = new $minionClass;

        $instance = new $className;

        $mergedProps = array_merge($factoryInstance->defaultProperties($this->faker), $overrideProps);
        foreach ($mergedProps as $propId => $propValue) {
            $setterName = sprintf('set%s', ucfirst($propId));

            if (method_exists($className, $setterName)) {
                $instance->{$setterName}($propValue);
            } else {
                throw new \RuntimeException("No defined setter for `{$propId}` in `{$className}` class.");
            }
        }

        $this->entityManager->persist($instance);
        return $instance;
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function clear(): void
    {
        $this->entityManager->clear();
    }

    public function getFaker(): \Faker\Generator
    {
        return $this->faker;
    }
}