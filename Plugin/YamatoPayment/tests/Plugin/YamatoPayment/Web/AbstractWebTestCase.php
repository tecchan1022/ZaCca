<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Web;

use Plugin\YamatoPayment\AbstractYamatoPaymentTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

abstract class AbstractWebTestCase extends AbstractYamatoPaymentTestCase
{
    /** @var Client */
    protected $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
        $this->initializeMailCatcher();
    }

    public function tearDown()
    {
        $this->cleanUpMailCatcherMessages();
        parent::tearDown();
        $this->client = null;
    }

    /**
     * {@inheritdoc}
     */
    public function logIn($user = null)
    {
        $firewall = 'customer';

        if (!is_object($user)) {
            $user = $this->createCustomer();
        }
        $token = new UsernamePasswordToken($user, null, $firewall, array('ROLE_USER'));

        $this->app['security.token_storage']->setToken($token);
        $this->app['session']->set('_security_' . $firewall, serialize($token));
        $this->app['session']->save();

        $cookie = new Cookie($this->app['session']->getName(), $this->app['session']->getId());
        $this->client->getCookieJar()->set($cookie);
        return $user;
    }

}
