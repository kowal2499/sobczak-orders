<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory;
use App\Tests\Utilities\Factory\Definition\AgreementFactory;
use App\Tests\Utilities\Factory\Definition\CustomerFactory;
use App\Tests\Utilities\Factory\Definition\ProductFactory;
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
        AgreementFactory::class
    ];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->faker = \Faker\Factory::create('pl_PL');
    }

    public function make(string $className, callable $callback = null) {

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
        $factoryDefinition = new $minionClass;
        $instance = $factoryDefinition->define($this->faker, $callback);
        $this->entityManager->persist($instance);
        return $instance;
    }
}