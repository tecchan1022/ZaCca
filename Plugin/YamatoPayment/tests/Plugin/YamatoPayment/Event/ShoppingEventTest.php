<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Entity\Master\ProductType;
use Eccube\Entity\Payment;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoProduct;
use Symfony\Component\DomCrawler\Crawler;

class ShoppingEventTest extends AbstractEventTestCase
{
    function setUp()
    {
        parent::setUp();

        $this->logIn();
    }

    /**
     * カート→購入確認画面
     */
    function testShoppingIndex_後払い不可商品でないなら支払方法一覧に後払い決済が表示される()
    {
        // カートイン
        $this->scenarioCartIn(false);

        // 確認画面
        $crawler = $this->scenarioConfirm();

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $expected = '/クロネコ代金後払い決済/u';
        $actual = $crawler->filter('ul.payment_list')->html();
        $this->assertRegExp($expected, $actual);
    }

    /**
     * カート→購入確認画面
     */
    function testShoppingIndex_後払い不可商品なら支払方法一覧に後払い決済が表示されない()
    {
        // カートイン
        $this->scenarioCartIn(true);

        // 確認画面
        $crawler = $this->scenarioConfirm();

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $expected = '/クロネコ代金後払い決済/u';
        $actual = $crawler->filter('ul.payment_list')->html();
        $this->assertNotRegExp($expected, $actual);
    }

    /**
     * カート→購入確認画面→支払い方法選択→購入確認画面
     */
    public function testRender_ヤマト決済なら注文するボタンのキャプションが書きかわる()
    {
        // カートイン
        $this->scenarioCartIn(false);

        // 確認画面
        $this->scenarioConfirm();

        // クレジット決済情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']));

        // 支払い方法選択(クレジット決済を選択)
        $this->scenarioPayment($YamatoPaymentMethod->getId());

        // 確認画面
        $crawler = $this->scenarioConfirm();

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 注文ボタンのキャプションが書き変わっていること
        $expected = '注文する';
        $actual = $crawler->filter('#order-button')->text();
        $this->assertNotEquals($expected, $actual);
    }

    /**
     * カート→購入確認画面→支払い方法選択→購入確認画面
     */
    public function testRender_ヤマト決済以外なら注文するボタンのキャプションは書きかわらない()
    {
        // カートイン
        $this->scenarioCartIn(false);

        // 確認画面
        $this->scenarioConfirm();

        // 支払い方法選択(ヤマト決済以外を選択)
        $this->scenarioPayment(1);

        // 確認画面
        $crawler = $this->scenarioConfirm();

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 注文ボタンのキャプションが書き変わっていないこと
        $expected = '注文する';
        $actual = $crawler->filter('#order-button')->text();
        $this->assertEquals($expected, $actual);
    }

    /**
     * カート→購入確認画面→決済画面
     */
    public function testComplete_ヤマト決済なら購入完了せず決済画面へ遷移する()
    {
        // カート画面
        $this->scenarioCartIn(false);

        // 確認画面
        $crawler = $this->scenarioConfirm();
        $this->expected = 'ご注文内容のご確認';
        $this->actual = $crawler->filter('h1.page-heading')->text();
        $this->verify();

        // クレジット決済情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']));

        $this->scenarioComplete($this->app->path('shopping_confirm'), $YamatoPaymentMethod->getId());
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('yamato_shopping_payment')));
    }

    /**
     * カート→購入確認画面→完了画面
     */
    public function testComplete_ヤマト決済でないなら注文完了する()
    {
        // ヤマト決済のランクを下げる
        $Payments = $this->app['eccube.repository.payment']->findAll();
        foreach ($Payments as $payment) {
            /** @var Payment $payment */
            if (!is_null($this->app['yamato_payment.repository.yamato_payment_method']->find($payment->getId()))) {
                $payment->setRank(0);
                $this->app['orm.em']->persist($payment);
            }
        }
        $this->app['orm.em']->flush();

        // カート画面
        $this->scenarioCartIn(false);

        // 確認画面
        $crawler = $this->scenarioConfirm();
        $this->expected = 'ご注文内容のご確認';
        $this->actual = $crawler->filter('h1.page-heading')->text();
        $this->verify();

        // 注文を確定する
        $this->scenarioComplete($this->app->path('shopping_confirm'), 1);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('shopping_complete')));
    }

    public function testCompleteRender_ヤマト決済でなら決済情報テンプレートが差し込まれる()
    {
        // 受注データ作成
        $Order = $this->createOrderData();
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);

        $this->app['session']->set('eccube.front.shopping.order.id', $Order->getId());

        // 注文完了画面
        $client = $this->createClient();
        $crawler = $client->request('GET', $this->app->path('shopping_complete'));

        $this->assertTrue($client->getResponse()->isSuccessful());

        // 決済情報テンプレートが差し込まれること
        $this->assertNotEmpty($crawler->filter('#deliveradd_input_box__payment_message'));

        // 決済内容が表示されていること
        $memo02 = $YamatoOrderPayment->getMemo02();
        $expected = '/' . $memo02['title']['name'] . '/u';
        $actual = $crawler->filter('#deliveradd_input_box__payment_message')->html();
        $this->assertRegExp($expected, $actual);
    }

    public function testCompleteRender_受注番号が空でもエラーにならない()
    {
        // 注文完了画面
        $client = $this->createClient();
        $client->request('GET', $this->app->path('shopping_complete'));

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function testCompleteRender_ヤマト決済でないなら決済情報テンプレートは差し込まれない()
    {
        // 受注データ作成
        $Order = $this->createOrderData();

        $this->app['session']->set('eccube.front.shopping.order.id', $Order->getId());

        // 注文完了画面
        $client = $this->createClient();
        $crawler = $client->request('GET', $this->app->path('shopping_complete'));

        $this->assertTrue($client->getResponse()->isSuccessful());

        // テンプレートは差し込まれないこと
        $this->assertEmpty($crawler->filter('#deliveradd_input_box__payment_message'));
    }

    /**
     * @param bool $not_deferred_flg
     */
    protected function scenarioCartIn($not_deferred_flg)
    {
        /** @var Product $Product */
        $Product = $this->app['eccube.repository.product']->find(2);
        $product_class_id = $Product->getProductClasses()->get(0)->getId();

        // 後払い不可フラグ更新
        /** @var YamatoProduct $YamatoProduct */
        $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($Product->getId());
        if (is_null($YamatoProduct)) {
            $YamatoProduct = new YamatoProduct();
            $YamatoProduct->setId($Product->getId());
        }
        $YamatoProduct->setNotDeferredFlg($not_deferred_flg);
        $this->app['orm.em']->persist($YamatoProduct);
        $this->app['orm.em']->flush($YamatoProduct);

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
        $crawler = $this->client->request('GET', $this->app->path('shopping'));
        return $crawler;
    }

    /**
     * @param integer $payment_id
     * @return Crawler
     */
    protected function scenarioPayment($payment_id)
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();

        // 支払い方法選択
        $crawler = $this->client->request(
            'POST',
            $this->app->path('shopping_payment'),
            array(
                'shopping' => array(
                    'shippings' => array(
                        0 => array(
                            'delivery' => 1,
                            'deliveryTime' => 1
                        ),
                    ),
                    'payment' => $payment_id,
                    'message' => $faker->text(),
                    '_token' => 'dummy'
                )
            )
        );

        return $crawler;
    }

    /**
     * @param string $confirm_url
     * @param integer $payment_id
     * @return Crawler
     */
    protected function scenarioComplete($confirm_url, $payment_id)
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();
        $crawler = $this->client->request(
            'POST',
            $confirm_url,
            array('shopping' =>
                  array(
                      'shippings' =>
                      array(0 =>
                            array(
                                'delivery' => 1,
                                'deliveryTime' => 1
                            ),
                      ),
                      'payment' => $payment_id,
                      'message' => $faker->text(),
                      '_token' => 'dummy'
                  )
            )
        );
        return $crawler;
    }

    /**
     * カート→購入確認画面→カート
     */
    function testShoppingIndex_カートに商品種別予約商品と予約商品以外が混在する場合カートに戻る()
    {
        // 複数配送を有効にする
        $this->setMultipleShipping(1);

        // カートイン 通常商品＋予約商品
        $this->scenarioReserveItemCartIn(1, 1);
        $this->scenarioReserveItemCartIn($this->app['config']['YamatoPayment']['const']['PRODUCT_TYPE_ID_RESERVE'], 2);

        // 確認画面
        $this->scenarioConfirm();

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('cart')));

    }

    /**
     * 購入確認画面→決済実行→購入確認画面
     */
    public function testComplete_商品種別予約商品と予約商品以外が混在する場合カート画面へ返る()
    {
        // 複数配送を有効にする
        $this->setMultipleShipping(1);

        // カートイン 通常商品＋予約商品
        $this->scenarioReserveItemCartIn(1, 1);
        $this->scenarioReserveItemCartIn($this->app['config']['YamatoPayment']['const']['PRODUCT_TYPE_ID_RESERVE'], 2);

        // 注文を確定する
        $this->scenarioComplete($this->app->path('shopping_confirm'), 1);
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('cart')));
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

}
