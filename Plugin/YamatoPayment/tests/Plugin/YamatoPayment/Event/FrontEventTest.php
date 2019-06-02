<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Entity\Product;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoProduct;
use Symfony\Component\DomCrawler\Crawler;

class FrontEventTest extends AbstractEventTestCase
{
    function setUp()
    {
        parent::setUp();
    }

    function testFrontRequest_アクセスしたページがセッションに記録される()
    {
        $this->client->request('GET', $this->app->url('contact'));

        $this->assertEquals('/contact', $this->app['session']->get('yamato_payment.now_page'));
        $this->assertEquals('', $this->app['session']->get('yamato_payment.pre_page'));

        $this->app['session']->save();
        $this->client->request('GET', $this->app->url('entry'));

        $this->assertEquals('/entry', $this->app['session']->get('yamato_payment.now_page'));
        $this->assertEquals('/contact', $this->app['session']->get('yamato_payment.pre_page'));
    }

}
