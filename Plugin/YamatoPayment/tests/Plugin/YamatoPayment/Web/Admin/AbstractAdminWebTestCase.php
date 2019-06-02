<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Web\Admin;

use Plugin\YamatoPayment\Web\AbstractWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

abstract class AbstractAdminWebTestCase extends AbstractWebTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->logIn();
    }

    // Mockを使うべき
    public function logIn($user = null)
    {
        $firewall = 'admin';

        if (!is_object($user)) {
            $user = $this->app['eccube.repository.member']
                ->findOneBy(array(
                    'login_id' => 'admin',
                ));
        }

        $token = new UsernamePasswordToken($user, null, $firewall, array('ROLE_ADMIN'));

        $this->app['session']->set('_security_' . $firewall, serialize($token));
        $this->app['session']->save();

        $cookie = new Cookie($this->app['session']->getName(), $this->app['session']->getId());
        $this->client->getCookieJar()->set($cookie);
        return $user;
    }

}
