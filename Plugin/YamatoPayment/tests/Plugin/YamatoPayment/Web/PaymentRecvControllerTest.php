<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Web;

use Eccube\Application;
use Eccube\Entity\Order;
use Plugin\YamatoPayment\Controller\PaymentRecvController;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PaymentRecvControllerTest extends AbstractWebTestCase
{
    /** @var  PaymentRecvController */
    var $object;
    /** @var  Order */
    var $Order;

    public function setUp()
    {
        parent::setUp();
        $this->object = new PaymentRecvController();

        // 受注情報の作成
        $this->Order = $this->createOrderData();
    }

    protected function createFormDataCredit(Order $Order)
    {
        $form = array(
            'trader_code' => '12345678',
            'order_no' => $Order->getId(),
            'function_div' => 'A01',
            'settle_price' => $Order->getPaymentTotal(),
            // 決済日時
            'settle_date' => '20160401120000',
            // 決済結果
            'settle_result' => 1, // 正常
            // 決済結果詳細
            'settle_detail' => 4, // 正常（与信完了）
            // 決済手段ID
            'settle_method' => 5, // 三井住友クレジット
        );
        return $form;
    }

    protected function createFormDataCvs(Order $Order)
    {
        $data = array(
            'trader_code' => '12345678',
            'order_no' => $Order->getId(),
            'settle_price' => $Order->getPaymentTotal(),
            // 決済日時
            'settle_date' => '20160401120000',
            // 決済結果
            'settle_result' => 1, // 正常
            // 決済結果詳細
            'settle_detail' => 2, // 入金完了（速報）
            // 決済手段ID
            'settle_method' => 21, // セブンイレブン
        );
        return $data;
    }

    function testIndexWithCreditNormal()
    {
        // クレジットカード決済情報を作成 決済ステータス：予約受付完了
        $YamatoOrderPaymentOld = $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        $memo04 = $YamatoOrderPaymentOld->getMemo04();
        $memo05 = $YamatoOrderPaymentOld->getMemo05();
        $memo06 = $YamatoOrderPaymentOld->getMemo06();
        $memo09 = $YamatoOrderPaymentOld->getMemo09();

        $form = $this->createFormDataCredit($this->Order);

        /**
         * クレジット決済結果：エラーが無い場合、0(正常終了)が返る
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment_recv'),
            $form
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('0', $this->client->getResponse()->getContent());

        /**
         * 決済データの更新確認
         */
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this
            ->app['yamato_payment.repository.yamato_order_payment']
            ->find($this->Order->getId());

        // 決済ステータスが与信完了に更新されている
        $this->assertEquals(
            $this->const['YAMATO_ACTION_STATUS_COMP_AUTH'],
            $YamatoOrderPayment->getMemo04()
        );
        // 決済ステータスが更新されている
        $this->assertNotEquals(
            $memo04,
            $YamatoOrderPayment->getMemo04()
        );
        // 決済情報が更新されている
        $this->assertNotEquals(
            $memo05,
            $YamatoOrderPayment->getMemo05()
        );
        // 審査結果は更新されない
        $this->assertEquals(
            $memo06,
            $YamatoOrderPayment->getMemo06()
        );
        // 決済ログが更新されている
        $this->assertNotEquals(
            $memo09,
            $YamatoOrderPayment->getMemo09()
        );
    }

    function testIndexWithCredit_Error_受注データ不在の場合1が返る_エラーメールが送信されること()
    {
        // フォームデータを作成
        $form = $this->createFormDataCredit($this->Order);
        $form['order_no'] = 0;

        /**
         * クレジット決済結果：受注データ不在の場合、1(エラー)が返る
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment_recv'),
            $form
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('1', $this->client->getResponse()->getContent());

        // 送信したメールを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        // エラーメールが送信されること
        $pluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->assertContains($pluginUtil->getPluginName() . ' 不一致データ検出', $Message->subject);
    }

    function testIndexWithCredit_Error_決済未使用の場合1が返る_エラーメールが送信されること()
    {
        // フォームデータを作成
        $form = $this->createFormDataCredit($this->Order);
        $form['function_div'] = null;

        /**
         * クレジット決済結果：決済未使用の場合、1(エラー)が返る
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment_recv'),
            $form
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('1', $this->client->getResponse()->getContent());

        // 送信したメールを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        // エラーメールが送信されること
        $pluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->assertContains($pluginUtil->getPluginName() . ' 決済未使用データ検出', $Message->subject);
    }

    function testIndexWithCredit_Error_支払方法不一致の場合1が返る_エラーメールが送信されること()
    {
        // クレジットカード決済情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        // フォームデータを作成
        $form = $this->createFormDataCredit($this->Order);
        $form['settle_method'] = 'abcdefg';

        /**
         * クレジット決済結果：支払方法不一致の場合、1(エラー)が返る
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment_recv'),
            $form
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('1', $this->client->getResponse()->getContent());

        // 送信したメールを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        // エラーメールが送信されること
        $pluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->assertContains($pluginUtil->getPluginName() . ' 支払い方法不一致データ検出', $Message->subject);
    }

    function testIndexWithCredit_Error_決済金額不一致の場合1が返る_エラーメールが送信されること()
    {
        // クレジットカード決済情報を作成
        $this->createOrderPaymentDataCredit($this->Order);

        // フォームデータを作成
        $form = $this->createFormDataCredit($this->Order);
        $form['settle_price'] = 'abcdefgh';

        /**
         * クレジット決済結果：決済金額不一致の場合、1(エラー)が返る
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment_recv'),
            $form
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('1', $this->client->getResponse()->getContent());

        // 送信したメールを取得
        $Messages = $this->getMailCatcherMessages();
        $Message = $this->getMailCatcherMessage($Messages[0]->id);
        // エラーメールが送信されること
        $pluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->assertContains($pluginUtil->getPluginName() . ' 決済金額不一致データ検出', $Message->subject);
    }

    function testIndexWithCvsNormal()
    {
        $YamatoOrderPaymentOld = $this->createOrderPaymentDataCvs($this->Order);
        $memo04 = $YamatoOrderPaymentOld->getMemo04();
        $memo05 = $YamatoOrderPaymentOld->getMemo05();
        $memo06 = $YamatoOrderPaymentOld->getMemo06();
        $memo09 = $YamatoOrderPaymentOld->getMemo09();

        $formData = $this->createFormDataCvs($this->Order);

        /**
         * コンビニ決済結果：エラーが無い場合、0(正常終了)が返る
         */
        $this->client->request(
            'POST',
            $this->app->url('yamato_shopping_payment_recv'),
            $formData
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('0', $this->client->getResponse()->getContent());

        /**
         * 受注データの更新確認
         */
        /** @var Order $Order */
        $Order = $this->app['eccube.repository.order']->find($this->Order->getId());

        // 受注ステータスが更新されている
        $this->assertEquals(
            $this->app['config']['order_pre_end'],
            $Order->getOrderStatus()->getId()
        );

        /**
         * 決済データの更新確認
         */
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this
            ->app['yamato_payment.repository.yamato_order_payment']
            ->find($Order->getId());

        // 決済ステータスが入金完了（速報）に更新されている
        $this->assertEquals(
            $this->const['YAMATO_ACTION_STATUS_PROMPT_REPORT'],
            $YamatoOrderPayment->getMemo04()
        );
        // 決済ステータスが更新されている
        $this->assertNotEquals(
            $memo04,
            $YamatoOrderPayment->getMemo04()
        );
        // 決済情報が更新されている
        $this->assertNotEquals(
            $memo05,
            $YamatoOrderPayment->getMemo05()
        );
        // 審査結果は更新されない
        $this->assertEquals(
            $memo06,
            $YamatoOrderPayment->getMemo06()
        );
        // 決済ログが更新されている
        $this->assertNotEquals(
            $memo09,
            $YamatoOrderPayment->getMemo09()
        );
    }

    function testIndexWithGET()
    {
        /**
         * GETアクセスは、1(エラー)が返る
         */
        $this->client->request(
            'GET',
            $this->app->url('yamato_shopping_payment_recv')
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals('1', $this->client->getResponse()->getContent());
    }

    function testIndexWithAccessDeniedHttpException()
    {
        // 許可されないIPアドレスを設定
        $this->client->setServerParameter('REMOTE_ADDR', '0.0.0.0');

        /**
         * 許可されていないIPアドレスは例外(403)が発生する
         */
        try {
            $this->client->request(
                'POST',
                $this->app->url('yamato_shopping_payment_recv')
            );
            $this->fail('例外発生なし');
        } catch (AccessDeniedHttpException $e) {
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('未定義の例外');
        }
    }

    function testDoReceive_支払方法がWEBコレクト以外の場合_falseが返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'doReceive');
        $method->setAccessible(true);

        //注文情報取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        /*
         * 支払方法がWEBコレクト以外の場合、falseが返る
         */
        $this->expected = false;
        $this->actual = $method->invoke($this->object, array(), $OrderExtension, $this->app);
        $this->verify();
    }

    function testGetPayNameFromSettleMethod()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'getPayNameFromSettleMethod');
        $method->setAccessible(true);

        /*
         * 支払方法がコンビニ決済の場合、コンビニ決済コンビニ名が返る
         */
        $settle_method = 21;
        $this->expected = 'コンビニ決済 セブンイレブン';
        $this->actual = $method->invoke($this->object, $settle_method, $this->app);
        $this->verify();

        /*
         * 支払方法が電子マネー決済の場合、電子マネー名が返る
         */
        $settle_method = 66;
        $this->expected = 'モバイルWAON決済';
        $this->actual = $method->invoke($this->object, $settle_method, $this->app);
        $this->verify();

        /*
         * 支払方法がネットバンク決済の場合、ネットバンク名が返る
         */
        $settle_method = 41;
        $this->expected = 'ネットバンク決済';
        $this->actual = $method->invoke($this->object, $settle_method, $this->app);
        $this->verify();

        /*
         * 支払方法が不明な場合、「不明な支払方法」が返る
         */
        $settle_method = 0;
        $this->expected = '不明な支払方法';
        $this->actual = $method->invoke($this->object, $settle_method, $this->app);
        $this->verify();
    }

    function testDoRecvCvs_POST値が入金完了速報の場合_trueが返る_受注情報の対応状況が更新される()
    {
        $order_id = $this->Order->getId();

        // フォームデータを作成
        $formData = $this->createFormDataCvs($this->Order);

        // 受注情報の対応状況を取得
        $orderStatus = $this->Order->getOrderStatus();

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // 入金日がNullなことを確認
        $this->assertNull($this->Order->getPaymentDate());

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCvs($formData, $this->Order, $this->app));

        // 受注情報の対応状況が更新されること
        $newOrder = $this->app['eccube.repository.order']->find($order_id);
        $newOrderStatus = $newOrder->getOrderStatus();
        $this->assertNotEquals($orderStatus, $newOrderStatus);

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);

        // 入金日に値が入っていることを確認
        /** @var Order $newOrder */
        $newOrder = $this->app['eccube.repository.order']->find($order_id);
        $this->assertNotNull($newOrder->getPaymentDate());
    }

    function testDoRecvCvs_受注情報が入金待ちでない_POST値が入金完了確報の場合_trueが返る_受注情報の対応状況は更新されない()
    {
        $order_id = $this->Order->getId();

        // フォームデータを作成
        $formData = $this->createFormDataCvs($this->Order);
        $formData['settle_detail'] = 3;

        // 受注情報の対応状況を取得
        $orderStatus = $this->Order->getOrderStatus();

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCvs($formData, $this->Order, $this->app));

        // 受注情報の対応状況が更新されないこと
        $newOrder = $this->app['eccube.repository.order']->find($order_id);
        $newOrderStatus = $newOrder->getOrderStatus();
        $this->assertEquals($orderStatus, $newOrderStatus);

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCvs_受注情報が入金待ち_POST値が入金完了確報の場合_trueが返る_受注情報の対応状況は更新される()
    {
        $OrderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_pay_wait']);
        $this->Order->setOrderStatus($OrderStatus);
        $this->app['orm.em']->flush();

        $order_id = $this->Order->getId();

        // フォームデータを作成
        $formData = $this->createFormDataCvs($this->Order);
        $formData['settle_detail'] = 3;

        // 受注情報の対応状況を取得
        $orderStatus = $this->Order->getOrderStatus();

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // 入金日がNullなことを確認
        $this->assertNull($this->Order->getPaymentDate());

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCvs($formData, $this->Order, $this->app));

        // 受注情報の対応状況が更新されること
        $newOrder = $this->app['eccube.repository.order']->find($order_id);
        $newOrderStatus = $newOrder->getOrderStatus();
        $this->assertNotEquals($orderStatus, $newOrderStatus);

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);

        // 入金日に値が入っていることを確認
        /** @var Order $newOrder */
        $newOrder = $this->app['eccube.repository.order']->find($order_id);
        $this->assertNotNull($newOrder->getPaymentDate());
    }

    function testDoRecvCvs_購入者都合エラー_trueが返る_受注情報の対応状況が更新される()
    {
        $order_id = $this->Order->getId();

        // フォームデータを作成
        $formData = $this->createFormDataCvs($this->Order);
        $formData['settle_detail'] = 11;

        // 受注情報の対応状況を取得
        $orderStatus = $this->Order->getOrderStatus();

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCvs($formData, $this->Order, $this->app));

        // 受注情報の対応状況が更新されること
        $newOrder = $this->app['eccube.repository.order']->find($order_id);
        $newOrderStatus = $newOrder->getOrderStatus();
        $this->assertNotEquals($orderStatus, $newOrderStatus);

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCvs_決済機関都合エラー_trueが返る_受注情報の対応状況が更新される()
    {
        $order_id = $this->Order->getId();

        // フォームデータを作成
        $formData = $this->createFormDataCvs($this->Order);
        $formData['settle_detail'] = 13;

        // 受注情報の対応状況を取得
        $orderStatus = $this->Order->getOrderStatus();

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCvs($formData, $this->Order, $this->app));

        // 受注情報の対応状況は変更がないこと
        $newOrder = $this->app['eccube.repository.order']->find($order_id);
        $newOrderStatus = $newOrder->getOrderStatus();
        $this->assertEquals($orderStatus, $newOrderStatus);

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCvs_その他のシステムエラー_trueが返る_受注情報の対応状況が更新される()
    {
        $order_id = $this->Order->getId();

        // フォームデータを作成
        $formData = $this->createFormDataCvs($this->Order);
        $formData['settle_detail'] = 14;

        // 受注情報の対応状況を取得
        $orderStatus = $this->Order->getOrderStatus();

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCvs($formData, $this->Order, $this->app));

        // 受注情報の対応状況は変更がないこと
        $newOrder = $this->app['eccube.repository.order']->find($order_id);
        $newOrderStatus = $newOrder->getOrderStatus();
        $this->assertEquals($orderStatus, $newOrderStatus);

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCvs_default_falseが返る()
    {
        // フォームデータを作成
        $formData = $this->createFormDataCvs($this->Order);
        $formData['settle_detail'] = 0;

        // Falseが返ること
        $this->assertFalse($this->object->doRecvCvs($formData, $this->Order, $this->app));
    }

    function testDoRecvCredit_与信完了_trueが返る()
    {
        // フォームデータを作成
        $formData = $this->createFormDataCredit($this->Order);

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // 受注支払い情報の作成
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCredit($formData, $OrderExtension, $this->app));

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCredit_購入者都合エラー_trueが返る()
    {
        // フォームデータを作成
        $formData = $this->createFormDataCredit($this->Order);
        $formData['settle_detail'] = 11;

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // 受注支払い情報の作成
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCredit($formData, $OrderExtension, $this->app));

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCredit_加盟店都合エラー_trueが返る()
    {
        // フォームデータを作成
        $formData = $this->createFormDataCredit($this->Order);
        $formData['settle_detail'] = 12;

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // 受注支払い情報の作成
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCredit($formData, $OrderExtension, $this->app));

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCredit_決済機関都合エラー_trueが返る()
    {
        // フォームデータを作成
        $formData = $this->createFormDataCredit($this->Order);
        $formData['settle_detail'] = 13;

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // 受注支払い情報の作成
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCredit($formData, $OrderExtension, $this->app));

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCredit_その他システムエラー_trueが返る()
    {
        // フォームデータを作成
        $formData = $this->createFormDataCredit($this->Order);
        $formData['settle_detail'] = 14;

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // 受注支払い情報の作成
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCredit($formData, $OrderExtension, $this->app));

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCredit_予約販売与信エラー_trueが返る()
    {
        // フォームデータを作成
        $formData = $this->createFormDataCredit($this->Order);
        $formData['settle_detail'] = 15;

        // action_statusが存在しないことを確認
        $this->assertTrue(empty($formData['action_status']));

        // 受注支払い情報の作成
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // Trueが返ること
        $this->assertTrue($this->object->doRecvCredit($formData, $OrderExtension, $this->app));

        // formDataの決済結果詳細が変更される
        $this->assertNotEmpty($formData['action_status']);
    }

    function testDoRecvCredit_default_falseが返る()
    {
        // フォームデータを作成
        $formData = $this->createFormDataCredit($this->Order);
        $formData['settle_detail'] = 0;

        // 受注支払い情報の作成
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE']);

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // Falseが返ること
        $this->assertFalse($this->object->doRecvCredit($formData, $OrderExtension, $this->app));
    }

    function testDoRecvCredit_取引状況が予約受付完了でない_falseが返る()
    {
        // フォームデータを作成
        $formData = $this->createFormDataCredit($this->Order);
        $formData['settle_detail'] = 0;

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // Falseが返ること
        $this->assertFalse($this->object->doRecvCredit($formData, $OrderExtension, $this->app));
    }
}
