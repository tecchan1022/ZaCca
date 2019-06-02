<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Helper;

use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Util\Str;
use Plugin\YamatoPayment\Entity\PaymentExtension;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class CreditPageHelperTest extends AbstractHelperTestCase
{
    var $tpl_is_reserve;

    var $error;

    /** @var  CreditPageHelper */
    var $object;

    /** @var Order */
    var $Order;

    var $threeDsecure;

    protected $const;

    public function setUp()
    {
        parent::setUp();
        $this->const = $this->app['config']['YamatoPayment']['const'];
        $this->object = new CreditPageHelper($this->app);

        // クレジットカード支払方法設定
        $payment_method = array(
            'pay_way' => array(0, 1, 2),
            'TdFlag' => 1,
            'order_mail_title' => "お支払いについて",
            'order_mail_body' => "お支払いクレジットカード",
        );
        /** @var YamatoPaymentMethod $YamatoPaymentMethodCredit */
        $YamatoPaymentMethodCredit = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']));
        $YamatoPaymentMethodCredit->setMemo05($payment_method);
        $this->app['orm.em']->flush();

        // 受注情報を取得
        $pre_order_id = sha1(Str::random(32));
        $this->app['eccube.service.cart']->setPreOrderId($pre_order_id);
        $this->Order = $this->createOrder($this->createCustomer());
        $this->Order->setPreOrderId($pre_order_id);

        // 支払方法IDを取得（クレジットカード決済）
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->Order->setPayment($Payment);
        $this->app['orm.em']->flush();
    }

    function testModeAction_modeはnext_予約商品購入の場合_isCompleteはtrueとなること_出荷予定日がセットされること()
    {
        // 予約販売機能は利用する
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $userSettings['advance_sale'] = '0';
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);

        // 出荷予定日がセットされていないことを確認
        $yamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($this->Order->getId());
        $this->assertEmpty($yamatoOrderScheduledShippingDate);

        /*
         * PaymentUtilモック作成
         */
        // 出荷予定日
        $getMaxScheduledShippingDate = 20280101;

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil(null, true, null, null, null, $getMaxScheduledShippingDate);

        // PageHelper_Credit doNextをモック化
        $this->object = $this->createPageHelper_Credit(true);

        // isCompleteがfalseなことを確認
        $this->assertFalse($this->object->isComplete);

        $mode = 'next';

        $listParam = array(
            'card_no' => '123456789012',
            'register_card' => '0',
            'use_registed_card' => '1'
        );

        $this->object->modeAction(null, $mode, $listParam, $this->Order, $this);

        // isCompleteはtrueとなること
        $this->assertTrue($this->object->isComplete);

        // 出荷予定日がセットされていること
        $yamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($this->Order->getId());
        $this->assertNotEmpty($yamatoOrderScheduledShippingDate);
    }

    function testModeAction_modeはnext_通常商品購入の場合_isCompleteはtrueとなること()
    {
        /*
         * PaymentUtilモック作成
         */
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil(null, false, null, null, null);

        /*
         * PageHelper_Credit doNextをモック化
         */
        $this->object = $this->createPageHelper_Credit(true);

        // isCompleteがfalseなことを確認
        $this->assertFalse($this->object->isComplete);

        $mode = 'next';
        $this->object->modeAction(null, $mode, array(), $this->Order, $this);

        // isCompleteはtrueとなること
        $this->assertTrue($this->object->isComplete);
    }

    function testModeAction_modeは3dTran_予約商品購入の場合_isCompleteはtrueとなること_出荷予定日がセットされること()
    {
        // 出荷予定日がセットされていないことを確認
        $yamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($this->Order->getId());
        $this->assertEmpty($yamatoOrderScheduledShippingDate);

        /*
         * PaymentUtilモック作成
         */
        // 出荷予定日
        $getMaxScheduledShippingDate = 20280101;

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil(null, true, null, null, null, $getMaxScheduledShippingDate);

        /*
         * PageHelper_Credit 3dTranをモック化
         */
        $this->object = $this->createPageHelper_Credit(true);

        // isCompleteがfalseなことを確認
        $this->assertFalse($this->object->isComplete);

        $mode = '3dTran';
        $this->object->modeAction(null, $mode, array(), $this->Order, $this);

        // isCompleteはtrueとなること
        $this->assertTrue($this->object->isComplete);

        // 出荷予定日がセットされていること
        $yamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($this->Order->getId());
        $this->assertNotEmpty($yamatoOrderScheduledShippingDate);
    }

    function testModeAction_modeは3dTran_通常商品購入の場合_isCompleteはtrueとなること()
    {
        /*
         * PaymentUtilモック作成
         */
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil(null, false, null, null, null);

        /*
         * PageHelper_Credit 3dTranをモック化
         */
        $this->object = $this->createPageHelper_Credit(true);

        // isCompleteがfalseなことを確認
        $this->assertFalse($this->object->isComplete);

        $mode = '3dTran';
        $this->object->modeAction(null, $mode, array(), $this->Order, $this);

        // isCompleteはtrueとなること
        $this->assertTrue($this->object->isComplete);
    }

    function testModeAction_modeはdeleteCard_ログイン済の場合_PaymentUtilのdoDeleteCardが呼び出されること()
    {
        // ログイン状態にする
        $Customer = $this->createCustomer();
        $token = new UsernamePasswordToken($Customer, null, 'customer', array('ROLE_USER'));
        $this->app['security.token_storage']->setToken($token);

        /*
         * PaymentUtil doDeleteCardをモック化
         */
        $mock = $this->getMock('Plugin\YamatoPayment\Util\PaymentUtil', array('doDeleteCard'), array($this->app));
        $mock->expects($this->once())
            ->method('doDeleteCard');
        $this->app['yamato_payment.util.payment'] = $mock;

        // 「doDeleteCard」メソッドが一度だけ呼び出されること
        $mode = 'deleteCard';
        $this->object->modeAction(null, $mode, array(), $this->Order, $this);
    }


    public function testModeAction_予約販売機能は利用しない_modeはnext_カード情報が無い_予約商品購入の場合_エラーメッセージが返ること()
    {
        // 予約販売機能は利用しない
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $userSettings['advance_sale'] = '1';
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);

        /*
         * PaymentUtilモック作成
         */
        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil(null, true, null, null, null, null);

        $mode = 'next';
        $listParam = array(
            'card_no' => '',
            'register_card' => '0',
            'use_registed_card' => '0'
        );

        $this->object->modeAction(null, $mode, $listParam, $this->Order, $this);

        $this->assertContains(
            '現在のご契約内容では予約商品販売は行えません。大変お手数をおかけいたしますが店舗運営者までお問い合わせくださいませ。',
            $this->error['advance_sale']
        );
        $this->assertContains(
            '予約商品購入はカード情報お預かり、もしくは登録済カード情報でのご購入が必要です。',
            $this->error['register_card']
        );
    }


    function testDoNext_予約商品有り_3Dセキュア無し_trueが返ること_注文状況が新規受付なこと_決済状況が予約受付完了なこと()
    {
        // クレジットカード決済の受注情報を作成 決済状況：決済手続き中
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);

        // フォームデータ作成
        $listParam = $this->createFormDataCredit($this->Order);

        // 予約商品有無
        $this->tpl_is_reserve = true;

        // 出荷予定日がセットされていないことを確認
        $yamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($this->Order->getId());
        $this->assertEmpty($yamatoOrderScheduledShippingDate);

        // クレジットカード決済処理（CreditClientService）モック作成
        // 決済結果取得
        $getResults = array(
            'errorCode' => 'A012050002',
            'info_use_threeD' => null,
            'threeDAuthHtml' => '',
            'threeDToken' => '',
        );
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(false, true, $getResults);

        /*
         * PaymentUtilモック作成
         */
        // 支払方法の設定情報を作成
        $paymentTypeConfig = new PaymentExtension();

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, $OrderExtension, true, null);

        // 注文状況が新規受付でないこと
        $this->assertNotEquals($this->app['config']['order_new'], $this->Order->getOrderStatus()->getId());

        // 決済状況が予約受付完了でないなこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_COMP_RESERVE'], $YamatoOrderPayment->getMemo04());

        // trueが返ること
        $this->assertTrue($this->object->doNext($this->Order, $listParam, $this));

        // 注文状況が新規受付なこと
        $Order = $this->app['eccube.repository.order']->find($this->Order->getId());
        $this->assertEquals($this->app['config']['order_new'], $Order->getOrderStatus()->getId());

        // 決済状況が予約受付完了なこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_COMP_RESERVE'], $YamatoOrderPayment->getMemo04());
    }

    function testDoNext_予約商品無し_3Dセキュア無し_trueが返ること_注文状況が新規受付なこと_決済状況が与信完了なこと()
    {
        // クレジットカード決済の受注情報を作成 決済状況：決済手続き中
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);

        // フォームデータ作成
        $listParam = $this->createFormDataCredit($this->Order);

        // 予約商品有無
        $this->tpl_is_reserve = false;

        // クレジットカード決済処理（CreditClientService）モック作成
        // 決済結果取得
        $getResults = array(
            'errorCode' => 'A012050002',
            'info_use_threeD' => null,
            'threeDAuthHtml' => '',
            'threeDToken' => '',
        );
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(false, true, $getResults);

        /*
         * PaymentUtilモック作成
         */
        // 支払方法の設定情報を作成
        $paymentTypeConfig = new PaymentExtension();

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, $OrderExtension, false);

        // 注文状況が新規受付でないこと
        $this->assertNotEquals($this->app['config']['order_new'], $this->Order->getOrderStatus()->getId());

        // 決済状況が与信完了でないなこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_COMP_AUTH'], $YamatoOrderPayment->getMemo04());

        // trueが返ること
        $this->assertTrue($this->object->doNext($this->Order, $listParam, $this));

        // 注文状況が新規受付なこと
        $Order = $this->app['eccube.repository.order']->find($this->Order->getId());
        $this->assertEquals($this->app['config']['order_new'], $Order->getOrderStatus()->getId());

        // 決済状況が与信完了なこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_COMP_AUTH'], $YamatoOrderPayment->getMemo04());
    }

    function testDoNext_3Dセキュア有り_3DセキュアのページのURLが表示されたhtmlが返ること()
    {
        // フォームデータ作成
        $listParam = $this->createFormDataCredit($this->Order);

        // 予約商品有無
        $this->tpl_is_reserve = true;

        // クレジットカード決済処理（CreditClientService）モック作成
        // 決済結果取得
        $getResults = array(
            'errorCode' => '',
            'info_use_threeD' => null,
            'threeDAuthHtml' => 'dummyHTML',
            'threeDToken' => 'dummyToken',
        );
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(true, true, $getResults);

        // PaymentUtilモック作成
        // 支払方法の設定情報を作成
        $paymentTypeConfig = new PaymentExtension();

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, $OrderExtension, null, null);

        $this->object->doNext($this->Order, $listParam, $this);

        // 3Dセキュアのページurlが表示されているのhtmlが返ること
        $this->assertContains('dummyHTML', $this->threeDsecure);
    }

    function testDoNext_決済でエラーが発生した場合_falseが返ること_決済状況が決済中断なこと_エラーメッセージが返ること()
    {
        // クレジットカード決済の受注情報を作成 決済状況：決済手続き中
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);

        // フォームデータ作成
        $listParam = $this->createFormDataCredit($this->Order);

        // 予約商品有無
        $this->tpl_is_reserve = true;

        // クレジットカード決済処理（CreditClientService）モック作成
        // 決済結果取得
        $getResults = array(
            'errorCode' => '',
            'info_use_threeD' => null,
            'threeDAuthHtml' => 'dummyHTML',
            'threeDToken' => 'dummyToken',
        );
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(false, false, $getResults);

        // PaymentUtilモック作成
        // 支払方法の設定情報を作成
        $paymentTypeConfig = new PaymentExtension();

        //受注情報の拡張データを取得
        $OrderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($this->Order->getId());

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, $OrderExtension, null, null);

        // 決済状況が決済中断でないなこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_NG_TRANSACTION'], $YamatoOrderPayment->getMemo04());

        // Falseが返ること
        $this->assertFalse($this->object->doNext($this->Order, $listParam, $this));

        // 決済状況が決済中断なこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_NG_TRANSACTION'], $YamatoOrderPayment->getMemo04());

        // エラーメッセージが返ること
        $this->assertContains('決済でエラーが発生しました', $this->error['payment']);
    }

    function testDo3dTran_予約商品有り_trueが返ること_注文状況が新規受付なこと_決済状況が予約受付完了なこと()
    {
        // リクエスト値
        $request = null;

        // クレジットカード決済の受注情報を作成 決済状況：決済手続き中
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);

        // 出荷予定日がセットされていないことを確認
        $yamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($this->Order->getId());
        $this->assertEmpty($yamatoOrderScheduledShippingDate);

        // クレジットカード決済処理（CreditClientService）モック作成
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(null, null, null, true);

        /*
         * PaymentUtilモック作成
         */
        // 支払方法の設定情報を作成
        $paymentTypeConfig = new PaymentExtension();

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, null, true);

        // 注文状況が新規受付でないこと
        $this->assertNotEquals($this->app['config']['order_new'], $this->Order->getOrderStatus()->getId());

        // 決済状況が予約受付完了でないなこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_COMP_RESERVE'], $YamatoOrderPayment->getMemo04());

        // trueが返ること
        $this->assertTrue($this->object->do3dTran($request, $this->Order, $this));

        // 注文状況が新規受付なこと
        $Order = $this->app['eccube.repository.order']->find($this->Order->getId());
        $this->assertEquals($this->app['config']['order_new'], $Order->getOrderStatus()->getId());

        // 決済状況が予約受付完了なこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_COMP_RESERVE'], $YamatoOrderPayment->getMemo04());
    }

    function testDo3dTran_予約商品無し_trueが返ること_注文状況が新規受付なこと_決済状況が予約受付完了なこと()
    {
        // リクエスト値
        $request = null;

        // クレジットカード決済の受注情報を作成 決済状況：決済手続き中
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);

        // クレジットカード決済処理（CreditClientService）モック作成
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(null, null, null, true);

        /*
         * PaymentUtilモック作成
         */
        // 支払方法の設定情報を作成
        $paymentTypeConfig = new PaymentExtension();

        // 予約販売可否
        $isReserve = false;

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig, null, null, null, $isReserve);

        // 注文状況が新規受付でないこと
        $this->assertNotEquals($this->app['config']['order_new'], $this->Order->getOrderStatus()->getId());

        // 決済状況が与信完了でないなこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_COMP_AUTH'], $YamatoOrderPayment->getMemo04());

        // trueが返ること
        $this->assertTrue($this->object->do3dTran($request, $this->Order, $this));

        // 注文状況が新規受付なこと
        $Order = $this->app['eccube.repository.order']->find($this->Order->getId());
        $this->assertEquals($this->app['config']['order_new'], $Order->getOrderStatus()->getId());

        // 決済状況が与信完了なこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_COMP_AUTH'], $YamatoOrderPayment->getMemo04());
    }

    function testDo3dTran_決済でエラーが発生した場合_falseが返ること_決済状況が決済中断なこと_エラーメッセージが返ること()
    {
        // リクエスト値
        $request = null;

        // クレジットカード決済の受注情報を作成 決済状況：決済手続き中
        $this->createOrderPaymentDataCredit($this->Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);

        /*
         * クレジットカード決済処理（CreditClientService）モック作成
         */
        $this->app['yamato_payment.service.client.credit'] = $this->createCreditClientService(false);

        /*
         * PaymentUtilモック作成
         */
        // 支払方法の設定情報を作成
        $paymentTypeConfig = new PaymentExtension();

        // PaymentUtilをモック化
        $this->app['yamato_payment.util.payment'] = $this->createPaymentUtil($paymentTypeConfig);

        // 決済状況が決済中断でないなこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_NG_TRANSACTION'], $YamatoOrderPayment->getMemo04());

        // trueが返ること
        $this->assertFalse($this->object->do3dTran($request, $this->Order, $this));

        // 決済状況が決済中断なこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_NG_TRANSACTION'], $YamatoOrderPayment->getMemo04());

        // エラーメッセージが返ること
        $this->assertRegExp('/決済でエラーが発生しました/u', $this->error['payment']);
    }

    public function testGetArrCardInfo_預かり情報1件の場合_返り値は更新されること()
    {
        // 預かりカード一件
        $listCardInfos['cardData'] = array(
            'card_key' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '1',
        );
        $listCardInfos['cardUnit'] = 1;

        $actual = $this->object->getArrCardInfo($listCardInfos);

        // 預かり情報が1件の場合、返ってくる値は更新されること
        $this->assertNotEquals($listCardInfos, $actual);
    }

    public function testGetArrCardInfo_預かり情報2件の場合_返り値は更新されないこと()
    {
        // 預かりカード2件
        $listCardInfos['cardData'] = array(
            0 => array(
                'card_key' => '1',
                'maskingCardNo' => '************1111',
                'cardExp' => '0528',
                'cardOwner' => 'KURONEKO YAMATO',
                'subscriptionFlg' => '1',
            ),
            1 => array(
                'card_key' => '2',
                'maskingCardNo' => '************2222',
                'cardExp' => '1128',
                'cardOwner' => 'IPPO MAEE',
                'subscriptionFlg' => '0',
            ),
        );
        $listCardInfos['cardUnit'] = 2;

        $actual = $this->object->getArrCardInfo($listCardInfos);

        // 預かり情報が複数件の場合、返ってくる値に変更がないこと
        $this->assertEquals($listCardInfos, $actual);
    }

    protected function createFormDataCredit(Order $Order)
    {
        // 支払方法の設定情報を取得する（決済モジュール管理対象である場合、内部識別コードを同時に設定する）
        $paymentExtension = $this->app['yamato_payment.util.payment']->getPaymentTypeConfig($Order->getPayment()->getId());
        $paymentInfo = $paymentExtension->getArrPaymentConfig();

        $sendPayMethod = $this->app['yamato_payment.util.payment']->getCreditPayMethod();
        $listPayMethod = array();
        if (isset($paymentInfo['pay_way'])) {
            foreach ((array)$paymentInfo['pay_way'] as $pay_method) {
                if (!is_null($sendPayMethod[$pay_method])) {
                    $listPayMethod[$pay_method] = $sendPayMethod[$pay_method];
                }
            }
        }

        $form = array(
            '_token' => 'dummy',
            // カード番号
            'card_no' => '1234567890123456',
            // 有効期限(月)
            'card_exp_month' => '05',
            // 有効期限(年)
            'card_exp_year' => '28',
            // カード名義
            'card_owner' => 'KURONEKO YAMATO',
            // セキュリティコード
            'security_code' => '1234',
            // 支払方法
            'method' => array_rand($listPayMethod, 1),
            // このカードを登録する 登録する：1 登録しない：0
            'register_card' => 1,
            // 登録されたカードを使用する 使用する：1 使用しない：0
            'use_registed_card' => 0,
            // カードキー
            'card_key' => 1,
        );
        return $form;
    }

    private function createPageHelper_Credit($bool)
    {
        $mock = $this->getMockBuilder('Plugin\YamatoPayment\Helper\CreditPageHelper')
            ->setConstructorArgs(array($this->app))
            ->setMethods(array('doNext', 'do3dTran'))
            ->getMock();

        $mock->expects($this->any())
            ->method('doNext')
            ->will($this->returnValue($bool));
        $mock->expects($this->any())
            ->method('do3dTran')
            ->will($this->returnValue($bool));

        return $mock;
    }

    private function createCreditClientService(
        $doPaymentRequest = false,
        $doPaymentRequest2 = false,
        $getResults = array(),
        $doSecureTran = false
    ) {
        $mock = $this->getMock('CreditClientService', array(
            'doPaymentRequest',
            'getResults',
            'getError',
            'doSecureTran'
        ));
        $mock->expects($this->any())
            ->method('doPaymentRequest')
            ->will($this->onConsecutiveCalls($doPaymentRequest, $doPaymentRequest2));
        $mock->expects($this->any())
            ->method('getResults')
            ->will($this->returnValue($getResults));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));
        $mock->expects($this->any())
            ->method('doSecureTran')
            ->will($this->returnValue($doSecureTran));

        return $mock;
    }

    private function createPaymentUtil(
        $paymentTypeConfig = null,
        $isReservedOrder = false,
        $isOption = null,
        $OrderExtension = null,
        $isReserve = false,
        $getMaxScheduledShippingDate = null
    ) {
        $sendPayMethod = $this->app['yamato_payment.util.payment']->getCreditPayMethod();

        $mock = $this->getMock('PaymentUtil', array(
            'getPaymentTypeConfig',
            'isReservedOrder',
            'isOption',
            'getCreditPayMethod',
            'getOrderPayData',
            'isReserve',
            'getMaxScheduledShippingDate'
        ));
        $mock->expects($this->any())
            ->method('getPaymentTypeConfig')
            ->will($this->returnValue($paymentTypeConfig));
        $mock->expects($this->any())
            ->method('isReservedOrder')
            ->will($this->returnValue($isReservedOrder));
        $mock->expects($this->any())
            ->method('isOption')
            ->will($this->returnValue($isOption));
        $mock->expects($this->any())
            ->method('getCreditPayMethod')
            ->will($this->returnValue($sendPayMethod));
        $mock->expects($this->any())
            ->method('getOrderPayData')
            ->will($this->returnValue($OrderExtension));
        $mock->expects($this->any())
            ->method('isReserve')
            ->will($this->returnValue($isReserve));
        $mock->expects($this->any())
            ->method('getMaxScheduledShippingDate')
            ->will($this->returnValue($getMaxScheduledShippingDate));

        return $mock;
    }
}
