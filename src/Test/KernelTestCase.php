<?php


namespace App\Test;


use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyKernelTestCase;

class KernelTestCase extends SymfonyKernelTestCase
{
    protected static $entityManagerWasCleared = false;

    protected function assertException(string $expectClass, callable $callback)
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            $this->assertInstanceOf($expectClass, $exception, 'An invalid exception was thrown');
            return;
        }

        $this->fail('No exception was thrown');
    }

    /**
     * @param $serviceId
     * @return object|null
     */
    public function get($serviceId)
    {
        if (null === static::$kernel || null === static::$container) {
            self::bootKernel();
        }

        return static::$container->get($serviceId);
    }

    /**
     * @return EntityManager
     */
    public function getManager(): EntityManager
    {
        if (false === static::$entityManagerWasCleared) {
            $this->get('doctrine')->getManager()->clear();

            static::$entityManagerWasCleared = true;
        }

        return $this->get('doctrine')->getManager();
    }

    /**
     * @return Connection
     */
    public function getDBConnection(): Connection
    {
        return $this->getManager()->getConnection();
    }
}