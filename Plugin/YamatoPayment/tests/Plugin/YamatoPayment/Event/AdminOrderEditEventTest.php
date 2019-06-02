<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Common\Constant;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoOrderScheduledShippingDate;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminOrderEditEventTest extends AbstractEventTestCase
{
    function setUp()
    {
        parent::setUp();
    }

    /**
     * @param Customer $Customer
     * @param Product $Product
     * @param bool $isMulti true:複数配送が有効
     * @return array
     */
    public function createFormData($Customer, $Product, $isMulti = false)
    {
        $ProductClasses = $Product->getProductClasses();
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();
        $tel = explode('-', $faker->phoneNumber);

        $email = $faker->safeEmail;
        $delivery_date = $faker->dateTimeBetween('now', '+ 5 days');
        $OrderDetails = $this->createOrderDetails($Product, $ProductClasses[0]);

        $order = array(
            '_token' => 'dummy',
            'Customer' => $Customer->getId(),
            'OrderStatus' => 1,
            'name' => array(
                'name01' => $faker->lastName,
                'name02' => $faker->firstName,
            ),
            'kana' => array(
                'kana01' => $faker->lastKanaName ,
                'kana02' => $faker->firstKanaName,
            ),
            'company_name' => $faker->company,
            'zip' => array(
                'zip01' => $faker->postcode1(),
                'zip02' => $faker->postcode2(),
            ),
            'address' => array(
                'pref' => '5',
                'addr01' => $faker->city,
                'addr02' => $faker->streetAddress,
            ),
            'tel' => array(
                'tel01' => $tel[0],
                'tel02' => $tel[1],
                'tel03' => $tel[2],
            ),
            'fax' => array(
                'fax01' => $tel[0],
                'fax02' => $tel[1],
                'fax03' => $tel[2],
            ),
            'email' => $email,
            'message' => $faker->text,
            'Payment' => 1,
            'discount' => 0,
            'delivery_fee_total' => 0,
            'charge' => 0,
            'note' => $faker->text,
            'OrderDetails' => array($OrderDetails),
            'Shippings' => array(
                array(
                    'name' => array(
                        'name01' => $faker->lastName,
                        'name02' => $faker->firstName,
                    ),
                    'kana' => array(
                        'kana01' => $faker->lastKanaName ,
                        'kana02' => $faker->firstKanaName,
                    ),
                    'company_name' => $faker->company,
                    'zip' => array(
                        'zip01' => $faker->postcode1(),
                        'zip02' => $faker->postcode2(),
                    ),
                    'address' => array(
                        'pref' => '5',
                        'addr01' => $faker->city,
                        'addr02' => $faker->streetAddress,
                    ),
                    'tel' => array(
                        'tel01' => $tel[0],
                        'tel02' => $tel[1],
                        'tel03' => $tel[2],
                    ),
                    'fax' => array(
                        'fax01' => $tel[0],
                        'fax02' => $tel[1],
                        'fax03' => $tel[2],
                    ),
                    'Delivery' => 1,
                    'DeliveryTime' => 1,
                    'shipping_delivery_date' => array(
                        'year' => $delivery_date->format('Y'),
                        'month' => $delivery_date->format('n'),
                        'day' => $delivery_date->format('j')
                    ),
                )
            ),
            'YamatoShippings' => array(
                array(
                    'deliv_slip_number' => '123456789012',
                )
            ),
            'scheduled_shipping_date' => '20160601',
        );

        /** @var BaseInfo $BaseInfo */
        $BaseInfo = $this->app['eccube.repository.base_info']->get();

        if ($isMulti) {
            $BaseInfo->setOptionMultipleShipping(Constant::ENABLED);
            $order['Shippings'][0]['ShipmentItems'] = array($this->createParam($Product, $ProductClasses[0]));
        } else {
            $BaseInfo->setOptionMultipleShipping(Constant::DISABLED);
        }

        $this->app['orm.em']->flush();

        return $order;
    }

    /**
     * @param Product $Product
     * @param ProductClass $ProductClass
     * @return array
     */
    function createOrderDetails($Product, $ProductClass)
    {
        $OrderDetails = array(
            'Product' => $Product->getId(),
            'ProductClass' => $ProductClass->getId(),
            'price' => $ProductClass->getPrice02(),
            'quantity' => 1,
            'tax_rate' => 8,
        );
        $addParam = array();
        if (version_compare(Constant::VERSION, '3.0.13', '>=')) {
            $addParam = array(
                    'product_name' => $Product->getName(),
                    'product_code' => $ProductClass->getCode(),
                    'class_name1' => $Product->getClassName1(),
                    'class_name2' => $Product->getClassName2(),
                    'class_category_name1' => $ProductClass->getClassCategory1(),
                    'class_category_name2' => $ProductClass->getClassCategory2()
            );
        }

        $OrderDetails = array_merge($OrderDetails, $addParam);

        return $OrderDetails;
    }

    /**
     * @param Product $Product
     * @param ProductClass $ProductClass
     * @return array
     */
    function createParam($Product, $ProductClass)
    {
        $addParam = array(
                'price' => $ProductClass->getPrice02(),
                'quantity' => 1,
                'new' => 1,
                'Product' => $Product->getId(),
                'ProductClass' => $ProductClass->getId(),
            );

        $modParam = array();
        if (version_compare(Constant::VERSION, '3.0.13', '>=')) {
            $modParam = array(
                'price' => $ProductClass->getPrice02(),
                'quantity' => 1,
                'new' => 1,
                'Product' => $Product->getId(),
                'ProductClass' => $ProductClass->getId(),
                'product_name' => $Product->getName(),
                'product_code' => $ProductClass->getCode(),
                'class_name1' => $Product->getClassName1(),
                'class_name2' => $Product->getClassName2(),
                'class_category_name1' => $ProductClass->getClassCategory1(),
                'class_category_name2' => $ProductClass->getClassCategory2()
            );
        }

        $addParam = array_merge($addParam, $modParam);

        return $addParam;
    }

    /**
     * 受注データ(ダミー)を返す
     *
     * @return OrderExtension
     */
    function getDummyOrder()
    {
        $YamatoOrderPayment = new YamatoOrderPayment();
        $YamatoOrderPayment
            // 決済種別（クレジット）
            ->setMemo03($this->const['YAMATO_PAYID_CREDIT'])
            // 決済ステータス（与信完了）
            ->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_AUTH']);
        $YamatoOrderScheduledShippingDate = new YamatoOrderScheduledShippingDate();
        $YamatoOrderScheduledShippingDate
            // 予約出荷予定日セット
            ->setScheduledshippingDate(new \DateTime());

        // 受注データ(ダミー)作成
        $orderExtension = new OrderExtension();
        $orderExtension
            ->setOrderID(1)
            ->setOrder($this->createOrder($this->createCustomer()))
            ->setYamatoOrderPayment($YamatoOrderPayment)
            ->setYamatoOrderScheduledShippingDate($YamatoOrderScheduledShippingDate);

        return $orderExtension;
    }

    /**
     * 後払いの受注データ(ダミー)を返す
     *
     * @return OrderExtension
     */
    function getDummyDeferredOrder()
    {
        $YamatoOrderPayment = new YamatoOrderPayment();
        $YamatoOrderPayment
            // 決済種別（後払い）
            ->setMemo03($this->const['YAMATO_PAYID_DEFERRED'])
            // 決済ステータス（送り状番号登録済み）
            ->setMemo04($this->const['DEFERRED_STATUS_REGIST_DELIV_SLIP']);

        // 受注データ(ダミー)作成
        $orderExtension = new OrderExtension();
        $orderExtension
            ->setOrderID(1)
            ->setOrder($this->createOrder($this->createCustomer()))
            ->setYamatoOrderPayment($YamatoOrderPayment);

        return $orderExtension;
    }

    function testRenderNew_新規登録画面の表示確認()
    {
        $this->adminLogIn();

        // 新規登録画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_new'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('body')->html();

        // 決済情報表示欄が表示されていないこと
        $this->assertNotRegExp('/決済情報/u', $source);
        // 支払い方法選択肢からヤマト決済を削除するjavascriptが読み込まれていること
        $this->assertRegExp('/remove yamato payment from payment/u', $source);
        // 送り状番号入力欄が表示されること
        $this->assertRegExp('/送り状番号/u', $source);
    }

    function testRenderEdit_ヤマト決済以外の表示確認()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('body')->html();

        // 決済情報表示欄が表示されていないこと
        $this->assertNotRegExp('/決済情報/u', $source);
        // 支払い方法選択肢からヤマト決済を削除するjavascriptが読み込まれていること
        $this->assertRegExp('/remove yamato payment from payment/u', $source);
        // 送り状番号入力欄が表示されること
        $this->assertRegExp('/送り状番号/u', $source);
    }

    function testRenderEdit_クレジット決済の表示確認()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);
        $this->createYamatoShippingDelivSlip($Order);
        $this->createYamatoOrderScheduledShippingDateData($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('body')->html();

        // 決済情報表示欄が表示されていること
        $this->assertRegExp('/決済情報/u', $source);
        // 支払い方法選択肢からヤマト決済を削除するjavascriptが読み込まれていないこと
        $this->assertNotRegExp('/remove yamato payment from payment/u', $source);
        // 送り状番号入力欄が表示されること
        $this->assertRegExp('/送り状番号/u', $source);

        // 与信承認番号欄が表示されること
        $this->assertRegExp('/与信承認番号/u', $source);
        // 出荷予定日欄が表示されること
        $this->assertRegExp('/出荷予定日/u', $source);
    }

    function testRenderEdit_クレジット決済ボタンの表示確認_与信完了()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);
        $this->createYamatoShippingDelivSlip($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertNotEmpty($crawler->filter('#btn_yamato_get_info'), '取引情報照会ボタン表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_cancel'), '決済取消ボタン表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_change_price'), '金額変更ボタン表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_shipment_regist'), '出荷情報登録ボタン表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_shipment_cancel'), '出荷情報取消ボタン表示されない');
        $this->assertEmpty($crawler->filter('#btn_yamato_change_scheduled_shipping_date'), '出荷予定日変更ボタン表示されない');
    }

    function testRenderEdit_クレジット決済ボタンの表示確認_精算確定待ち()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        // 決済ステータス：精算確定待ち
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $this->createYamatoShippingDelivSlip($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertNotEmpty($crawler->filter('#btn_yamato_get_info'), '取引情報照会ボタン表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_cancel'), '決済取消ボタン表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_change_price'), '金額変更ボタン表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_shipment_regist'), '出荷情報登録ボタン表示されない');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_shipment_cancel'), '出荷情報取消ボタン表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_change_scheduled_shipping_date'), '出荷予定日変更ボタン表示されない');
    }

    function testRenderEdit_クレジット決済ボタンの表示確認_精算確定()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        // 決済ステータス：精算確定
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_COMMIT_SETTLEMENT']);
        $this->createYamatoShippingDelivSlip($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertNotEmpty($crawler->filter('#btn_yamato_get_info'), '取引情報照会ボタン表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_cancel'), '決済取消ボタン表示されない');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_change_price'), '金額変更ボタン表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_shipment_regist'), '出荷情報登録ボタン表示されない');
        $this->assertEmpty($crawler->filter('#btn_yamato_shipment_cancel'), '出荷情報取消ボタン表示されない');
        $this->assertEmpty($crawler->filter('#btn_yamato_change_scheduled_shipping_date'), '出荷予定日変更ボタン表示されない');
    }

    function testRenderEdit_クレジット決済ボタンの表示確認_取消()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        // 決済ステータス：取消
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_CANCEL']);
        $this->createYamatoShippingDelivSlip($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertNotEmpty($crawler->filter('#btn_yamato_get_info'), '取引情報照会ボタン表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_cancel'), '決済取消ボタン表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_change_price'), '金額変更ボタン表示されない');
        $this->assertEmpty($crawler->filter('#btn_yamato_shipment_regist'), '出荷情報登録ボタン表示されない');
        $this->assertEmpty($crawler->filter('#btn_yamato_shipment_cancel'), '出荷情報取消ボタン表示されない');
        $this->assertEmpty($crawler->filter('#btn_yamato_change_scheduled_shipping_date'), '出荷予定日変更ボタン表示されない');
    }

    function testRenderEdit_クレジット決済ボタンの表示確認_予約受付完了()
    {
        $this->adminLogIn();

        // 予約販売機能は利用する
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $userSettings['advance_sale'] = '0';
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);

        // 受注データ作成
        $Order = $this->createOrderData();
        // 決済ステータス：予約受付完了
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);
        $this->createYamatoShippingDelivSlip($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertNotEmpty($crawler->filter('#btn_yamato_get_info'), '取引情報照会ボタン表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_cancel'), '決済取消ボタン表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_change_price'), '金額変更ボタン表示されない');
        $this->assertEmpty($crawler->filter('#btn_yamato_shipment_regist'), '出荷情報登録ボタン表示されない');
        $this->assertEmpty($crawler->filter('#btn_yamato_shipment_cancel'), '出荷情報取消ボタン表示されない');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_change_scheduled_shipping_date'), '出荷予定日変更ボタン表示される');
    }

    function testRenderEdit_コンビニ決済の表示確認()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCvs($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('body')->html();

        // 決済情報表示欄が表示されていること
        $this->assertRegExp('/決済情報/u', $source);
        // 支払い方法選択肢からヤマト決済を削除するjavascriptが読み込まれていないこと
        $this->assertNotRegExp('/remove yamato payment from payment/u', $source);
        // 送り状番号入力欄が表示されること
        $this->assertRegExp('/送り状番号/u', $source);

        // 支払先コンビニ欄が表示されること
        $this->assertRegExp('/支払い先コンビニ/u', $source);


        $this->assertNotEmpty($crawler->filter('#btn_yamato_get_info'), '取引情報照会ボタン表示される');
    }

    function testRenderEdit_後払い決済の表示確認()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataDeferred($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 画面のソースを取得
        $source = $crawler->filter('body')->html();

        // 決済情報表示欄が表示されていること
        $this->assertRegExp('/決済情報/u', $source);
        // 支払い方法選択肢からヤマト決済を削除するjavascriptが読み込まれていないこと
        $this->assertNotRegExp('/remove yamato payment from payment/u', $source);
        // 送り状番号入力欄が表示されること
        $this->assertRegExp('/送り状番号/u', $source);

        // 送信日時欄が表示されること
        $this->assertRegExp('/送信日時/u', $source);
        // 買手情報一括登録CSVボタンが表示されること
        $this->assertRegExp('/買手情報一括登録CSV/u', $source);
    }

    function testRenderEdit_後払い決済ボタンの表示確認_後払い用審査結果がご利用可()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataDeferred($Order);
        $this->createYamatoShippingDelivSlip($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_shipment_regist'), '出荷情報登録ボタン(後払い)表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_deferred_shipment_cancel'), '出荷情報取消ボタン(後払い)表示されない');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_get_info'), '取引情報取得ボタン(後払い)表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_auth_cancel'), '与信取消ボタン(後払い)表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_get_auth'), '与信結果取得ボタン(後払い)表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_change_price'), '金額変更ボタン(後払い)表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_invoice_reissue'), '請求内容変更・請求書再発行ボタン 表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_invoice_reissue_withdrawn'), '請求書再発行取下げボタン 表示される');
    }

    function testRenderEdit_後払い決済ボタンの表示確認_決済ステータスが取消済み()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        // 決済状況：取消済み　審査結果：ご利用可
        $this->createOrderPaymentDataDeferred($Order, $this->const['DEFERRED_STATUS_AUTH_CANCEL'], $this->const['DEFERRED_AVAILABLE']);
        $this->createYamatoShippingDelivSlip($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_shipment_regist'), '出荷情報登録ボタン(後払い)表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_deferred_shipment_cancel'), '出荷情報取消ボタン(後払い)表示されない');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_get_info'), '取引情報取得ボタン(後払い)表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_deferred_auth_cancel'), '与信取消ボタン(後払い)表示されない');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_get_auth'), '与信結果取得ボタン(後払い)表示される');
        $this->assertEmpty($crawler->filter('#btn_yamato_deferred_change_price'), '金額変更ボタン(後払い)表示されない');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_invoice_reissue'), '請求内容変更・請求書再発行ボタン 表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_invoice_reissue_withdrawn'), '請求書再発行取下げボタン 表示される');
    }

    function testRenderEdit_後払い決済ボタンの表示確認_決済ステータスが送り状番号登録済み()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        // 決済状況：送り状番号登録済み　審査結果：ご利用可
        $this->createOrderPaymentDataDeferred($Order, $this->const['DEFERRED_STATUS_REGIST_DELIV_SLIP'], $this->const['DEFERRED_AVAILABLE']);
        $this->createYamatoShippingDelivSlip($Order);

        // 受注編集画面にアクセス
        $crawler = $this->client->request('GET', $this->app->url('admin_order_edit', array('id' => $Order->getId())));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_shipment_regist'), '出荷情報登録ボタン(後払い)表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_shipment_cancel'), '出荷情報取消ボタン(後払い)表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_get_info'), '取引情報取得ボタン(後払い)表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_auth_cancel'), '与信取消ボタン(後払い)表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_get_auth'), '与信結果取得ボタン(後払い)表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_invoice_reissue'), '請求内容変更・請求書再発行ボタン 表示される');
        $this->assertNotEmpty($crawler->filter('#btn_yamato_deferred_invoice_reissue_withdrawn'), '請求書再発行取下げボタン 表示される');
    }

    function testEditPost_登録が正常に完了する()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        $Product = $this->app['eccube.repository.product']->find(1);
        /** @var Product $Product */
        $formData = $this->createFormData($Customer, $Product);
        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'order' => $formData,
                'mode' => 'register'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
        $YamatoShippingDelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->findOneBy(array('order_id' => $Order->getId()));
        /** @var YamatoOrderScheduledShippingDate $YamatoOrderScheduledShippingDate */
        $YamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($Order->getId());

        // 配送伝票番号が更新されている
        $this->expected = $formData['YamatoShippings'][0]['deliv_slip_number'];
        $this->actual = $YamatoShippingDelivSlip->getDelivSlipNumber();
        $this->verify();

        // 出荷予定日が更新されている
        $this->expected = $formData['scheduled_shipping_date'];
        $this->actual = $YamatoOrderScheduledShippingDate->getScheduledshippingDate();
        $this->verify();
    }

    function testEditPost_登録が正常に完了する_複数配送()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Customer = $this->createCustomer();
        $Order = $this->createOrder($Customer);
        $Product = $this->app['eccube.repository.product']->find(1);
        /** @var Product $Product */
        $formData = $this->createFormData($Customer, $Product, true);
        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'order' => $formData,
                'mode' => 'register'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));
    }

    function test_クレジット決済_出荷情報登録が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('UtilClientService', array('doShipmentEntry'));
        $mock->expects($this->once())
             ->method('doShipmentEntry')
             ->will($this->returnValue(array(true, array())));
        $this->app['yamato_payment.service.client.util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_shipment_regist'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_クレジット決済_出荷情報登録エラー時はロールバックが呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('UtilClientService', array('doShipmentEntry', 'doShipmentRollback'));
        $mock->expects($this->once())
             ->method('doShipmentEntry')
             ->will($this->returnValue(array(false, array())));
        $mock->expects($this->once())
             ->method('doShipmentRollback');

        $this->app['yamato_payment.service.client.util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_shipment_regist'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作でエラーが発生しました', $this->app['session']->getFlashBag()->get('eccube.admin.danger')));
    }

    function test_クレジット決済_出荷情報取消が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('UtilClientService', array('doShipmentCancel'));
        $mock->expects($this->once())
             ->method('doShipmentCancel')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_shipment_cancel'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_クレジット決済_取引情報照会が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('UtilClientService', array('doGetTradeInfo'));
        $mock->expects($this->once())
             ->method('doGetTradeInfo')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_get_info'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_クレジット決済_出荷予定日変更が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('UtilClientService', array('doChangeDate'));
        $mock->expects($this->once())
             ->method('doChangeDate')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_change_scheduled_shipping_date'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_クレジット決済_決済取消が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('UtilClientService', array('doCreditCancel'));
        $mock->expects($this->once())
             ->method('doCreditCancel')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_cancel'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_クレジット決済_金額変更が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('UtilClientService', array('doCreditChangePrice'));
        $mock->expects($this->once())
             ->method('doCreditChangePrice')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_change_price'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_後払い決済_取引状況取得が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('DeferredUtilClientService', array('doGetOrderInfo'));
        $mock->expects($this->once())
             ->method('doGetOrderInfo')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.deferred_util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_deferred_get_info'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_後払い決済_与信取消が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('DeferredUtilClientService', array('doCancel'));
        $mock->expects($this->once())
             ->method('doCancel')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.deferred_util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_deferred_auth_cancel'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_後払い決済_与信結果取得が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('DeferredUtilClientService', array('doGetAuthResult'));
        $mock->expects($this->once())
             ->method('doGetAuthResult')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.deferred_util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_deferred_get_auth'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_後払い決済_出荷情報登録が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('DeferredUtilClientService', array('doShipmentEntry'));
        $mock->expects($this->once())
             ->method('doShipmentEntry')
             ->will($this->returnValue(array(true, 1, 0)));

        $this->app['yamato_payment.service.client.deferred_util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_deferred_shipment_regist'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $bool = (preg_grep('/決済操作が完了しました。/u', $this->app['session']->getFlashBag()->get('eccube.admin.success')))? true : false;
        $this->assertTrue($bool);
    }

    function test_後払い決済_出荷情報取消が呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('DeferredUtilClientService', array('doShipmentCancel'));
        $mock->expects($this->once())
             ->method('doShipmentCancel')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.deferred_util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_deferred_shipment_cancel'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

    function test_不正な決済モードならリダイレクトだけされる()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_yamato'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作でエラーが発生しました', $this->app['session']->getFlashBag()->get('eccube.admin.danger')));
    }

    function test_受注情報が存在しないならExceptionが発生する()
    {
        $this->adminLogIn();

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithError($this->app);

        try {
            // リクエスト送信
            $this->client->request(
                'POST',
                $this->app->url('admin_order_edit', array('id' => 0)),
                array(
                    'mode_type' => 'yamato_yamato'
                )
            );
            $this->fail();
        } catch (NotFoundHttpException $e) {
            $this->assertEquals(404, $e->getStatusCode());
        }
    }

    function test_エラーがあるならリダイレクトされる()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithError($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_yamato'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));
    }

    function test_未ログインならログインページが表示される()
    {
        // リクエスト送信
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => 1))
        );
        $this->assertNotEmpty($crawler->filter('#login-page'));
    }

    function testErrorCheck_エラーチェック対象外ならNULLが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // エラーチェック対象外
        $mode = 'yamato_yamato';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷情報登録_エラーがないならnullが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // モック作成
        $expected = null;
        $mock = $this->getMockBuilder('Plugin\YamatoPayment\Util\PaymentUtil')
            ->setConstructorArgs(array($this->app))
            ->setMethods(array('checkErrorShipmentEntryForCredit'))
            ->getMock();
        $mock->expects($this->once())
             ->method('checkErrorShipmentEntryForCredit')
             ->will($this->returnValue($expected));

        $this->app['yamato_payment.util.payment'] = $mock;

        // 出荷情報登録
        $mode = 'yamato_shipment_regist';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = $expected;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷情報登録_チェックエラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // モック作成
        $expected = 'error';
        $mock = $this->getMockBuilder('Plugin\YamatoPayment\Util\PaymentUtil')
            ->setConstructorArgs(array($this->app))
            ->setMethods(array('checkErrorShipmentEntryForCredit'))
            ->getMock();
        $mock->expects($this->once())
             ->method('checkErrorShipmentEntryForCredit')
             ->will($this->returnValue($expected));

        $this->app['yamato_payment.util.payment'] = $mock;

        // 出荷情報登録
        $mode = 'yamato_shipment_regist';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = $expected;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷情報取消_エラーがないならnullが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // isSlippingOn()がtrueを返すモック作成
        $mock = $this
            ->getMockBuilder('Plugin\YamatoPayment\Repository\YamatoShippingDelivSlipRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('isSlippingOn'))
            ->getMock();
        $mock->expects($this->any())
             ->method('isSlippingOn')
             ->will($this->returnValue(true));
        $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'] = $mock;

        // 出荷情報取消
        $mode = 'yamato_shipment_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷情報取消_対応支払方法チェックエラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 決済種別（クレジット以外）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo03(null);

        // isSlippingOn()がtrueを返すモック作成
        $mock = $this
            ->getMockBuilder('Plugin\YamatoPayment\Repository\YamatoShippingDelivSlipRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('isSlippingOn'))
            ->getMock();
        $mock->expects($this->any())
             ->method('isSlippingOn')
             ->will($this->returnValue(true));
        $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'] = $mock;

        // 出荷情報取消
        $mode = 'yamato_shipment_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない決済です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷情報取消_取引状況チェックエラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 取引状況（精算確定）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo04($this->const['YAMATO_ACTION_STATUS_COMMIT_SETTLEMENT']);

        // isSlippingOn()がtrueを返すモック作成
        $mock = $this
            ->getMockBuilder('Plugin\YamatoPayment\Repository\YamatoShippingDelivSlipRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('isSlippingOn'))
            ->getMock();
        $mock->expects($this->any())
             ->method('isSlippingOn')
             ->will($this->returnValue(true));
        $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'] = $mock;

        // 出荷情報取消
        $mode = 'yamato_shipment_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない取引状況です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷情報取消_送り状番号の登録状態チェックエラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // isSlippingOn()がfalseを返すモック作成
        $mock = $this
            ->getMockBuilder('Plugin\YamatoPayment\Repository\YamatoShippingDelivSlipRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('isSlippingOn'))
            ->getMock();
        $mock->expects($this->any())
             ->method('isSlippingOn')
             ->will($this->returnValue(false));
        $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'] = $mock;

        // 出荷情報取消
        $mode = 'yamato_shipment_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '送り状番号が登録されていない配送先が存在します。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_取引状況照会_エラーがないならnullが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 取引状況照会
        $mode = 'yamato_get_info';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷予定日変更_エラーがないならNULLが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 取引状況を予約受付完了に設定
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        // 出荷予定日変更
        $mode = 'yamato_change_scheduled_shipping_date';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷予定日変更_対応支払方法エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 決済種別（クレジット以外）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo03($this->const['YAMATO_PAYID_DEFERRED']);

        // 出荷予定日変更
        $mode = 'yamato_change_scheduled_shipping_date';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない決済です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷予定日変更_取引状況チェックエラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 出荷予定日変更
        $mode = 'yamato_change_scheduled_shipping_date';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない取引状況です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_出荷予定日変更_出荷予定日必須チェックエラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 取引状況を予約受付完了に設定
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        // 出荷予定日未登録
        $orderExtension
            ->getYamatoOrderScheduledShippingDate()
            ->setScheduledshippingDate(null);

        // 出荷予定日変更
        $mode = 'yamato_change_scheduled_shipping_date';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '出荷予定日が設定されていません。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_決済取消_エラーがないならNULLが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 決済取消
        $mode = 'yamato_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_決済取消_対応支払方法エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 決済種別（クレジット以外）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo03($this->const['YAMATO_PAYID_DEFERRED']);

        // 決済取消
        $mode = 'yamato_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない決済です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_決済取消_取引状況チェックエラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 取引状況（精算確定）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo04($this->const['YAMATO_ACTION_STATUS_COMMIT_SETTLEMENT']);

        // 決済取消
        $mode = 'yamato_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない取引状況です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_金額変更_エラーがないならNULLが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 金額変更
        $mode = 'yamato_change_price';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_金額変更_オプション未契約でもエラーはなくNULLが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // オプションサービス（未契約）
        $userSettings = $this->app['yamato_payment.util.plugin']->subData['user_settings'];
        $userSettings['use_option'] = '1';
        $this->app['yamato_payment.util.plugin']->subData['user_settings'] = $userSettings;

        // 金額変更
        $mode = 'yamato_change_price';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_金額変更_対応支払方法エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 決済種別（クレジット以外）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo03($this->const['YAMATO_PAYID_DEFERRED']);

        // 金額変更
        $mode = 'yamato_change_price';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない決済です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_金額変更_取引状況チェックエラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyOrder();

        // 取引状況（精算確定）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo04($this->const['YAMATO_ACTION_STATUS_CANCEL']);

        // 金額変更
        $mode = 'yamato_change_price';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない取引状況です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_取引状況照会_エラーがないならnullが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // 取引状況照会（後払い）
        $mode = 'yamato_deferred_get_info';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_取引状況照会_対応支払方法エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // 決済種別（後払い以外）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo03($this->const['YAMATO_PAYID_CREDIT']);

        // 取引状況照会（後払い）
        $mode = 'yamato_deferred_get_info';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない決済です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_与信取消_エラーがないならnullが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // 与信取消（後払い）
        $mode = 'yamato_deferred_auth_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_与信取消_対応支払方法エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // 決済種別（後払い以外）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo03($this->const['YAMATO_PAYID_CREDIT']);

        // 与信取消（後払い）
        $mode = 'yamato_deferred_auth_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない決済です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_与信取消_取引状況エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // 決済ステータス（取消済み）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo04($this->const['DEFERRED_STATUS_AUTH_CANCEL']);

        // 与信取消（後払い）
        $mode = 'yamato_deferred_auth_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない取引状況です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_与信結果取得_エラーがないならnullが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // 与信結果取得（後払い）
        $mode = 'yamato_deferred_get_auth';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_与信結果取得_対応支払方法エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // 決済種別（後払い以外）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo03($this->const['YAMATO_PAYID_CREDIT']);

        // 与信結果取得（後払い）
        $mode = 'yamato_deferred_get_auth';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない決済です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_出荷情報登録_エラーがないならnullが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // モック作成
        $expected = null;
        $mock = $this->getMockBuilder('Plugin\YamatoPayment\Util\PaymentUtil')
            ->setConstructorArgs(array($this->app))
            ->setMethods(array('checkErrorShipmentEntryForDeferred'))
            ->getMock();
        $mock->expects($this->once())
             ->method('checkErrorShipmentEntryForDeferred')
             ->will($this->returnValue($expected));

        $this->app['yamato_payment.util.payment'] = $mock;

        // 出荷情報登録（後払い）
        $mode = 'yamato_deferred_shipment_regist';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = $expected;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_出荷情報登録_チェックエラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // モック作成
        $expected = 'error';
        $mock = $this->getMockBuilder('Plugin\YamatoPayment\Util\PaymentUtil')
            ->setConstructorArgs(array($this->app))
            ->setMethods(array('checkErrorShipmentEntryForDeferred'))
            ->getMock();
        $mock->expects($this->once())
             ->method('checkErrorShipmentEntryForDeferred')
             ->will($this->returnValue($expected));

        $this->app['yamato_payment.util.payment'] = $mock;

        // 出荷情報登録（後払い）
        $mode = 'yamato_deferred_shipment_regist';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = $expected;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_出荷情報取消_エラーがないならnullが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // isAllExistLastDelivSlip()がtrueを返すモック作成
        $mock = $this
            ->getMockBuilder('Plugin\YamatoPayment\Repository\YamatoShippingDelivSlipRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('isAllExistLastDelivSlip'))
            ->getMock();
        $mock
            ->expects($this->once())
            ->method('isAllExistLastDelivSlip')
            ->with($orderExtension->getOrder()->getId())
            ->will($this->returnValue(true));
        $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'] = $mock;

        // 出荷情報取消（後払い）
        $mode = 'yamato_deferred_shipment_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = null;
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_出荷情報取消_対応支払方法エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // 決済種別（後払い以外）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo03($this->const['YAMATO_PAYID_CREDIT']);

        // isAllExistLastDelivSlip()がtrueを返すモック作成
        $mock = $this
            ->getMockBuilder('Plugin\YamatoPayment\Repository\YamatoShippingDelivSlipRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('isAllExistLastDelivSlip'))
            ->getMock();
        $mock->expects($this->any())
             ->method('isAllExistLastDelivSlip')
             ->will($this->returnValue(true));
        $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'] = $mock;

        // 出荷情報取消（後払い）
        $mode = 'yamato_deferred_shipment_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない決済です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_出荷情報取消_取引状況エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // 決済ステータス（送り状番号登録済み以外）
        $orderExtension
            ->getYamatoOrderPayment()
            ->setMemo04($this->const['DEFERRED_STATUS_AUTH_OK']);

        // isAllExistLastDelivSlip()がtrueを返すモック作成
        $mock = $this
            ->getMockBuilder('Plugin\YamatoPayment\Repository\YamatoShippingDelivSlipRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('isAllExistLastDelivSlip'))
            ->getMock();
        $mock->expects($this->any())
             ->method('isAllExistLastDelivSlip')
             ->will($this->returnValue(true));
        $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'] = $mock;

        // 出荷情報取消（後払い）
        $mode = 'yamato_deferred_shipment_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '操作に対応していない取引状況です。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function testErrorCheck_後払い_出荷情報取消_出荷情報未登録エラーならエラーメッセージが返る()
    {
        // 受注データ(ダミー)作成
        $orderExtension = $this->getDummyDeferredOrder();

        // isAllExistLastDelivSlip()がfalseを返すモック作成
        $mock = $this
            ->getMockBuilder('Plugin\YamatoPayment\Repository\YamatoShippingDelivSlipRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('isAllExistLastDelivSlip'))
            ->getMock();
        $mock->expects($this->once())
             ->method('isAllExistLastDelivSlip')
             ->with($orderExtension->getOrder()->getId())
             ->will($this->returnValue(false));
        $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'] = $mock;

        // 出荷情報取消（後払い）
        $mode = 'yamato_deferred_shipment_cancel';

        // テスト対象メソッドをアクセス可能にする
        $object = new AdminOrderEditEvent($this->app);
        $method = new \ReflectionMethod(get_class($object), 'checkError');
        $method->setAccessible(true);

        // テスト対象メソッド実行
        $this->expected = '出荷情報登録されていない配送先が存在します。';
        $this->actual = $method->invoke($object, $orderExtension, $mode);
        $this->verify();
    }

    function test_後払い決済_請求内容変更_請求書再発行が呼び出される_再発行通知メールが送信される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataDeferred($Order);

        // サービスのモック作成
        $mock = $this->getMock('Plugin\YamatoPayment\Service\Client\DeferredUtilClientService', array('sendOrderRequest', 'getResults'), array($this->app));
        $mock->expects($this->any())
            ->method('sendOrderRequest')
            ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.deferred_util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_deferred_invoice_reissue'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));

        // 送信したメールを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        // 注文完了メールが送信されること
        $this->assertContains('請求書再発行のお知らせ', $Message->subject);
    }

    function test_後払い決済_請求書再発行取下げが呼び出される()
    {
        $this->adminLogIn();

        // 受注データ作成
        $Order = $this->createOrderData();

        // サービスのモック作成
        $mock = $this->getMock('DeferredUtilClientService', array('doInvoiceReissue'));
        $mock->expects($this->once())
             ->method('doInvoiceReissue')
             ->will($this->returnValue(true));

        $this->app['yamato_payment.service.client.deferred_util'] = $mock;

        // イベントクラス差し替え（エラーチェックをスキップ）
        $this->app['yamato_payment.event.admin.order.edit'] = new AdminOrderEditEventWithoutErrorCheck($this->app);

        $this->client->request(
            'POST',
            $this->app->url('admin_order_edit', array('id' => $Order->getId())),
            array(
                'mode_type' => 'yamato_deferred_invoice_reissue_withdrawn'
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_order_edit',
            array('id' => $Order->getId()))));

        $this->assertTrue(in_array('決済操作が完了しました。', $this->app['session']->getFlashBag()->get('eccube.admin.success')));
    }

}

class AdminOrderEditEventWithoutErrorCheck extends AdminOrderEditEvent
{
    protected function checkError($orderExtension, $mode)
    {
        return null;
    }
}

class AdminOrderEditEventWithError extends AdminOrderEditEvent
{
    protected function checkError($orderExtension, $mode)
    {
        return 'error';
    }
}
