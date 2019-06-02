<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Entity\Master\ProductType;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Symfony\Component\DomCrawler\Crawler;

class CartEventTest extends AbstractEventTestCase
{
    function setUp()
    {
        parent::setUp();

        $this->logIn();
    }

    /**
     * 商品画面→カート
     */
    function testCartIndex_商品種別予約商品と予約商品以外が混在する場合追加する商品を削除する()
    {
        // 複数配送を有効にする
        $this->setMultipleShipping(1);

        // カートイン 通常商品＋予約商品
        $this->scenarioReserveItemCartIn(1, 1);
        $this->scenarioReserveItemCartIn($this->app['config']['YamatoPayment']['const']['PRODUCT_TYPE_ID_RESERVE'], 2);

        // カート情報を取得
        $Cart = $this->app['eccube.service.cart']->getCart();
        $cart_item_count = count($Cart->getCartItems());

        // 確認画面
        $this->scenarioConfirm();

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // カート情報を取得
        $NewCart = $this->app['eccube.service.cart']->getCart();
        $new_cart_item_count = count($NewCart->getCartItems());

        // カートから削除されていること
        $this->assertNotEquals($cart_item_count, $new_cart_item_count);
    }

    /**
     * @param int $product_type_id
     * @param int $product_id
     */
    protected function scenarioReserveItemCartIn($product_type_id, $product_id)
    {
        /** @var Product $Product */
        $Product = $this->app['eccube.repository.product']->find($product_id);
        $product_class_id = $Product->getProductClasses()->get(0)->getId();

        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find($product_type_id);

        // 商品種別を更新
        foreach ($Product->getProductClasses() as $productClass) {
            /** @var ProductClass $productClass */
            $productClass->setProductType($ProductType);

            $this->app['orm.em']->persist($productClass);
        }
        $this->app['orm.em']->flush();

        // カートイン
        $this->client->request(
            'POST',
            $this->app->path('cart_add'),
            array('product_class_id' => $product_class_id)
        );

        // カートロック
        $this->app['eccube.service.cart']->lock();
    }

    /**
     * @return Crawler
     */
    protected function scenarioConfirm()
    {
        $crawler = $this->client->request('GET', $this->app->path('cart'));
        return $crawler;
    }

}
