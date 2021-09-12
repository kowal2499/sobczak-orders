<?php
/** @author: Roman Kowalski */

namespace App\System\Test;

use App\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class ApiTestCase extends WebTestCase
{
    private static $entityManagerWasCleared = false;
    private $client = null;

    /**
     * ApiTestCase constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();

    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    /**
     * @param User $user
     * @return KernelBrowser
     */
    public function login(User $user): KernelBrowser
    {
        $session = $this->get('session');
        $firewallName = $firewallContext = 'main';
        $token = new PostAuthenticationGuardToken($user, $firewallName, $user->getRoles());
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());

        $client = $this->client ?? $this->initializeClient();
        $client->getCookieJar()->set($cookie);
        return $client;
    }

    public function getManager(): EntityManager
    {
        $manager = $this->get('doctrine')->getManager();
        if (false === self::$entityManagerWasCleared) {
            $manager->clear();
            self::$entityManagerWasCleared = true;
        }
        return $manager;
    }

    private function initializeClient(): KernelBrowser
    {
        if (!$this->client) {
            $this->client = self::createClient();
        }
        return $this->client;
    }

    /**
     * @param string $service
     * @return object|null
     */
    protected function get(string $service)
    {
        if (null === static::$kernel || null === static::$container) {
            // triggers bootKernel
            $this->initializeClient();
        }
        return static::$container->get($service);
    }
}