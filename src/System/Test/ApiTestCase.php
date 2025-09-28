<?php
/** @author: Roman Kowalski */

namespace App\System\Test;

use App\Entity\User;
use App\Utilities\Test\AuthHelper;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiTestCase extends WebTestCase
{
    private static $entityManagerWasCleared = false;
    private $client = null;
    private ?AuthHelper $authHelper = null;

    protected static $container = null;


    protected function setUp(): void
    {
        parent::setUp();

    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @param User $user
     * @return KernelBrowser
     */
    public function login(User $user): KernelBrowser
    {
        $client = $this->client ?? $this->initializeClient();
        $client->loginUser($user);
//        $session = $this->get('session');
//        $firewallName = $firewallContext = 'main';
//        $token = new PostAuthenticationGuardToken($user, $firewallName, $user->getRoles());
//        $this->client->getContainer()->get('security.token_storage')->setToken($token);
//        $session->set('_security_'.$firewallContext, serialize($token));
//        $session->save();
//
//        $cookie = new Cookie($session->getName(), $session->getId());
//        $client->getCookieJar()->set($cookie);
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
    protected function get(string $service): mixed
    {
        if (null === static::$kernel) {
            // triggers bootKernel
            $this->initializeClient();
        }
        return static::getContainer()->get($service);
    }

    protected function getAuthHelper(): AuthHelper
    {
        if (null === $this->authHelper) {
            $authHelper = $this->get(AuthHelper::class);
            assert($authHelper instanceof AuthHelper);
            $this->authHelper = $authHelper;
        }
        return $this->authHelper;
    }

    protected function createUser(
        array $data = [],
        array $roleNames = [],
        array $grantNames = []
    ): User {
        return $this->getAuthHelper()->createUser($data, $roleNames, $grantNames);
    }
}