<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Entity\Master\ProductType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Eccube\Entity\ProductClass;

class AdminOrderIndexEventTest extends AbstractEventTestCase
{
    function setUp()
    {
        parent::setUp();
        $this->adminLogIn();
    }

    function testIndex()
    {
        // 受注を一件追加
        $this->createOrderData();

        // 検索条件無しで検索
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order'),
            array(
                'admin_search_order' => array(
                    '_token' => 'dummy'
                )
            )
        );
        // 検索が完了したこと
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('body')->html();

        // 追加したtwigのテキストが含まれていること
        $this->assertRegExp('/クレジットカード出荷登録CSVダウンロード/u', $source);
        $this->assertRegExp('/商品種別/u', $source);
    }

    function testIndex_search()
    {
        // 既存の受注情報を全件削除
        $orders = $this->app['eccube.repository.order']->findAll();
        foreach ($orders as $Order) {
            /** @var Order $Order */
            $Order->setDelFlg(1);
        }
        $this->app['orm.em']->flush();

        // 全件削除されたことを確認
        $this->assertEmpty($this->app['eccube.repository.order']->findAll());

        // 受注を一件追加
        $Order = $this->createOrderData();

        // 受注詳細情報を取得
        $orderDetails = $Order->getOrderDetails();

        // 予約商品の商品種別を取得
        /** @var ProductType $product_type */
        $product_type = $this->app['eccube.repository.master.product_type']->find(9625);

        // 受注商品を予約商品に更新
        foreach ($orderDetails as $orderDetail) {
            /** @var OrderDetail $orderDetail */
            /** @var ProductClass $ProductClass */
            $ProductClass = $orderDetail->getProductClass();
            $ProductClass->setProductType($product_type);
            $orderDetail->setProductClass($ProductClass);
            $this->app['orm.em']->persist($orderDetail);
            $newOrderDetail = clone $orderDetail;
            $Order->addOrderDetail($newOrderDetail);
        }
        $this->app['orm.em']->flush();

        // 一件追加されたことを確認
        $this->assertNotEmpty($this->app['eccube.repository.order']->findAll());

        /*
         * 検索条件に予約商品を指定して検索
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order'),
            array(
                'admin_search_order' => array(
                    '_token' => 'dummy',
                    'product_type' => array(9625),
                )
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 予約商品の受注情報が一件検索されることを確認
        $this->assertEquals(1, $crawler->filter('#result_list_main__list table tr')->count() - 1);

        /*
         * 検索条件に予約商品を指定して検索 → ページング
         */
        $crawler = $this->client->request(
            'GET',
            $this->app->url('admin_order_page', array('page_no' => 1))
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 予約商品の受注情報が一件検索されることを確認
        $this->assertEquals(1, $crawler->filter('#result_list_main__list table tr')->count() - 1);

        /*
         * 検索条件に予約商品以外を指定し再検索
         */
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order'),
            array(
                'admin_search_order' => array(
                    '_token' => 'dummy',
                    'product_type' => array(1)
                )
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 受注情報が存在しないことを確認
        $this->assertEmpty($crawler->filter('#result_list_main__list table tr'));
    }
}
