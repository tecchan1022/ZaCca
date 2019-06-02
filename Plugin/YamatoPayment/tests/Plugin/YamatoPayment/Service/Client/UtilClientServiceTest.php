<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Entity\Master\ProductType;
use Eccube\Entity\OrderDetail;
use Eccube\Util\Str;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Plugin\YamatoPayment\Service\AbstractServiceTestCase;
use Plugin\YamatoPayment\Util\PaymentUtil;
use Plugin\YamatoPayment\Util\PluginUtil;

class UtilClientServiceTest extends AbstractServiceTestCase
{
    /** @var  PluginUtil */
    var $PluginUtil;
    /** @var  PaymentUtil */
    var $PaymentUtil;
    /** @var YamatoPaymentMethod $YamatoPaymentMethod */
    var $YamatoPaymentMethod;
    /** @var  array */
    var $userSettings;

    /**
     * @var UtilClientService
     */
    protected $object;

    function setUp()
    {
        parent::setUp();
        $this->object = $this->app['yamato_payment.service.client.util'];

        $this->PluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->PaymentUtil = $this->app['yamato_payment.util.payment'];
        $this->userSettings = $this->PluginUtil->getUserSettings();
        $this->YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']));

    }

    function test_doShipmentEntry__リクエスト成功の場合__trueと成功した送り状番号が返ること__決済状況が精算確定待ちに更新されること__荷物問い合わせURLが更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $YamatoShippingDelivSlip = $this->createYamatoShippingDelivSlip($Order);
        $expected = array();
        foreach ($YamatoShippingDelivSlip as $YamatoShipping) {
            /** @var YamatoShippingDelivSlip $YamatoShipping */
            $expected[] = $YamatoShipping->getDelivSlipUrl();
        }

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
            'slipUrlPc' => 'http://slipURL',
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(true, $getResults);

        // 決済状況が「精算確定待ち」でないことを確認
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT'], $YamatoOrderPayment->getMemo04());

        /*
         * 出荷情報登録実行
         */
        $actual = $this->object->doShipmentEntry($OrderExtension);

        // trueが返ること
        $this->assertTrue($actual[0]);

        // 成功した送り状番号が返ること
        // 配送先情報数と成功した送り状番号数が同じなこと
        $this->assertEquals(count($YamatoShippingDelivSlip), count($actual[1]));

        // 決済状況が「精算確定待ち」なこと
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT'], $YamatoOrderPayment->getMemo04());

        // 配送情報を取得
        $YamatoShippingDelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->findBy(array('order_id' => $Order->getId()));

        // 荷物問い合わせURLが更新されること
        $key = 0;
        foreach ($YamatoShippingDelivSlip as $YamatoShipping) {
            /** @var YamatoShippingDelivSlip $YamatoShipping */
            $this->assertNotEquals($expected[$key], $YamatoShipping->getDelivSlipUrl());
            $key++;
        }
    }

    function test_doShipmentEntry__リクエスト失敗の場合__falseと成功した送り状番号が返ること__荷物問い合わせURLが更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        $this->createOrderPaymentDataCredit($Order);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $this->createYamatoShippingDelivSlip($Order);

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService();
        $this->object->error = 'エラーメッセージ';

        /*
         * 出荷情報登録実行
         */
        $actual = $this->object->doShipmentEntry($OrderExtension);

        // falseが返ること
        $this->assertFalse($actual[0]);

        // 成功した送り状番号が返ること
        // 配送情報1件のため返ってきた配列が空白なこと
        $this->assertEmpty($actual[1]);
    }

    function test_doShipmentEntry__配送先情報が存在しない場合__falseと空の配列が返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        $this->createOrderPaymentDataCredit($Order);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 出荷情報登録実行
         */
        $actual = $this->object->doShipmentEntry($OrderExtension);

        // falseが返ること
        $this->assertFalse($actual[0]);

        // 成功した送り状番号が返ること
        // 配送情報1件のため返ってきた配列が空白なこと
        $this->assertEmpty($actual[1]);
    }

    function test_doShipmentCancel__リクエスト成功の場合__trueが返ること__決済状況が与信完了に更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // 決済状況：精算確定待ち
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $this->createYamatoShippingDelivSlip($Order);

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(true, $getResults);

        // 決済状況が「与信完了」でないことを確認
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_COMP_AUTH'], $YamatoOrderPayment->getMemo04());

        /*
         * 出荷情報取消
         */
        // trueが返ること
        $this->assertTrue($this->object->doShipmentCancel($OrderExtension));

        // 決済状況が「与信完了」なこと
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_COMP_AUTH'], $YamatoOrderPayment->getMemo04());
    }

    function test_doShipmentCancel__リクエスト失敗の場合__falseが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // 決済状況：精算確定待ち
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $this->createYamatoShippingDelivSlip($Order);

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService();
        $this->object->error = 'エラーメッセージ';

        /*
         * 出荷情報取消
         */
        // falseが返ること
        $this->assertFalse($this->object->doShipmentCancel($OrderExtension));
    }

    function test_doShipmentCancel__配送先情報が存在しない場合__falseが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // 決済状況：精算確定待ち
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 出荷情報取消
         */
        // falseが返ること
        $this->assertFalse($this->object->doShipmentCancel($OrderExtension));
    }

    function test_doShipmentCancel__クレジットカード決済でない場合__falseが返ること_エラーメッセージが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 出荷情報取消
         */
        // falseが返ること
        $this->assertFalse($this->object->doShipmentCancel($OrderExtension));
        // エラーメッセージが返ること
        $this->assertContains('出荷情報取消に対応していない決済です。', $this->object->error);
    }

    function test_doShipmentRollback__リクエスト成功の場合__決済完了ページ表示用データが更新されていること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // 決済状況：精算確定待ち
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $YamatoShippingDelivSlip = $this->createYamatoShippingDelivSlip($Order);
        $listSuccessSlip = array();
        foreach ($YamatoShippingDelivSlip as $YamatoShipping) {
            /** @var YamatoShippingDelivSlip $YamatoShipping */
            $listSuccessSlip[] = $YamatoShipping->getDelivSlipNumber();
        }

        $expected = $YamatoOrderPayment->getMemo02();

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(true, $getResults);

        /*
         * 出荷情報登録ロールバック
         */
        $this->object->doShipmentRollback($OrderExtension, $listSuccessSlip);

        // 決済完了ページ表示用データが更新されていること
        $this->assertNotEquals($expected, $YamatoOrderPayment->getMemo02());
    }

    function test_doShipmentRollback__リクエスト失敗の場合__決済完了ページ表示用データが更新されないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // 決済状況：精算確定待ち
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $YamatoShippingDelivSlip = $this->createYamatoShippingDelivSlip($Order);
        $listSuccessSlip = array();
        foreach ($YamatoShippingDelivSlip as $YamatoShipping) {
            /** @var YamatoShippingDelivSlip $YamatoShipping */
            $listSuccessSlip[] = $YamatoShipping->getDelivSlipNumber();
        }

        $expected = $YamatoOrderPayment->getMemo02();

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'errorCode' => 'Z019999999',
            'returnDate' => date('YmdHis'),
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(false, $getResults);
        $this->object->error = 'エラーメッセージ';

        /*
         * 出荷情報登録ロールバック
         */
        $this->object->doShipmentRollback($OrderExtension, $listSuccessSlip);

        // 決済完了ページ表示用データが更新さないこと
        $this->assertEquals($expected, $YamatoOrderPayment->getMemo02());
    }

    function test_doShipmentRollback__成功出荷情報が0件の場合__何も返ってこないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // 決済状況：精算確定待ち
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $listSuccessSlip = array();

        /*
         * 出荷情報登録ロールバック
         */
        // 何も返ってこないこと
        $this->assertNull($this->object->doShipmentRollback($OrderExtension, $listSuccessSlip));
    }

    function test_doChangeDate__リクエスト成功の場合__trueが返ること__決済完了ページ表示用データが更新されていること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);

        // 商品種別（予約商品）を取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);
        foreach ($Order->getOrderDetails() as $OrderDetail) {
            // 商品種別を予約商品に設定
            /** @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);
        }

        $this->app['orm.em']->flush();

        // 決済状況：精算確定待ち
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 出荷予定日作成
        $this->createYamatoOrderScheduledShippingDateData($Order);

        $expected = $YamatoOrderPayment->getMemo02();

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(true, $getResults);

        /*
         * 出荷予定日変更
         */
        // trueが返ること
        $this->assertTrue($this->object->doChangeDate($OrderExtension));

        // 決済完了ページ表示用データが更新されていること
        $this->assertNotEquals($expected, $YamatoOrderPayment->getMemo02());
    }

    function test_doChangeDate__リクエスト失敗の場合__falseが返ること__決済完了ページ表示用データが更新されないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);

        // 商品種別（予約商品）を取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);
        foreach ($Order->getOrderDetails() as $OrderDetail) {
            // 商品種別を予約商品に設定
            /** @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);
        }

        $this->app['orm.em']->flush();

        // 決済状況：精算確定待ち
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 出荷予定日作成
        $this->createYamatoOrderScheduledShippingDateData($Order);

        $expected = $YamatoOrderPayment->getMemo02();

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'errorCode' => 'Z019999999',
            'returnDate' => date('YmdHis'),
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(false, $getResults);
        $this->object->error = 'エラーメッセージ';

        /*
         * 出荷予定日変更
         */
        // falseが返ること
        $this->assertFalse($this->object->doChangeDate($OrderExtension));

        // 決済完了ページ表示用データが更新されないこと
        $this->assertEquals($expected, $YamatoOrderPayment->getMemo02());
    }

    function test_doChangeDate__クレジットカード決済でない場合__falseが返ること__エラーメッセージが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();

        // 商品種別（予約商品）を取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);
        foreach ($Order->getOrderDetails() as $OrderDetail) {
            // 商品種別を予約商品に設定
            /** @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);
        }

        $this->app['orm.em']->flush();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 出荷予定日作成
        $this->createYamatoOrderScheduledShippingDateData($Order);

        /*
         * 出荷予定日変更
         */
        // falseが返ること
        $this->assertFalse($this->object->doChangeDate($OrderExtension));

        // エラーメッセージが返ること
        $this->assertContains('出荷予定日変更に対応していない注文です。', $this->object->error);
    }

    function test_doChangeDate__予約商品未購入の場合__falseが返ること__エラーメッセージが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // 決済状況：精算確定待ち
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 出荷予定日作成
        $this->createYamatoOrderScheduledShippingDateData($Order);

        /*
         * 出荷予定日変更
         */
        // falseが返ること
        $this->assertFalse($this->object->doChangeDate($OrderExtension));

        // エラーメッセージが返ること
        $this->assertContains('出荷予定日変更に対応していない注文です。', $this->object->error);
    }

    function test_doCreditCancel__リクエスト成功の場合__trueが返ること__決済状況が取消に更新されること__決済完了ページ表示用データが更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        $expected = $YamatoOrderPayment->getMemo02();

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(true, $getResults);

        // 決済状況が「取消」でないことを確認
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_CANCEL'], $YamatoOrderPayment->getMemo04());

        /*
         * 決済取消(クレジット決済)
         */
        // trueが返ること
        $this->assertTrue($this->object->doCreditCancel($OrderExtension));

        // 決済状況が「取消」なこと
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_CANCEL'], $YamatoOrderPayment->getMemo04());

        // 決済完了ページ表示用データが更新されていること
        $this->assertNotEquals($expected, $YamatoOrderPayment->getMemo02());
    }

    function test_doCreditCancel__リクエスト失敗の場合__falseが返ること__決済状況が更新されないこと__決済完了ページ表示用データが更新されないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        $expected = array(
            'memo02' => $YamatoOrderPayment->getMemo02(),
            'memo04' => $YamatoOrderPayment->getMemo04(),
        );

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'errorCode' => 'Z019999999',
            'returnDate' => date('YmdHis'),
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(false, $getResults);
        $this->object->error = 'エラーメッセージ';

        /*
         * 決済取消(クレジット決済)
         */
        // falseが返ること
        $this->assertFalse($this->object->doCreditCancel($OrderExtension));

        // 決済状況が更新されないこと
        $this->assertEquals($expected['memo04'], $YamatoOrderPayment->getMemo04());

        // 決済完了ページ表示用データが更新されていないこと
        $this->assertEquals($expected['memo02'], $YamatoOrderPayment->getMemo02());
    }

    function test_doCreditCancel__クレジットカード決済でない場合__falseが返ること__エラーメッセージが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済取消(クレジット決済)
         */
        // falseが返ること
        $this->assertFalse($this->object->doCreditCancel($OrderExtension));

        // エラーメッセージが返ること
        $this->assertContains('決済キャンセル・返品エラー：キャンセル・返品処理に対応していない決済です。', $this->object->error);
    }

    function test_doCreditChangePrice__リクエスト成功の場合__trueが返ること__決済金額が更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);

        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);

        $memo05 = $YamatoOrderPayment->getMemo05();
        $expected = $memo05['settle_price'];

        // 金額変更
        $paymentTotal = $Order->getPaymentTotal();
        $paymentTotal = intval($paymentTotal) + 1000;
        $Order->setPaymentTotal($paymentTotal);
        $this->app['orm.em']->flush();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(true, $getResults);

        /*
         * 決済金額変更
         */
        $this->assertTrue($this->object->doCreditChangePrice($OrderExtension));

        // 決済金額が更新されること
        $memo05 = $YamatoOrderPayment->getMemo05();
        $this->assertNotEquals($expected, $memo05['settle_price']);
    }

    function test_doCreditChangePrice__リクエスト失敗の場合__falseが返ること__決済金額が更新されないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);

        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        $memo05 = $YamatoOrderPayment->getMemo05();

        $expected = array(
            'settle_price' => $memo05['settle_price'],
        );

        // 金額変更
        $paymentTotal = $Order->getPaymentTotal();
        $paymentTotal = intval($paymentTotal) + 1000;
        $Order->setPaymentTotal($paymentTotal);
        $this->app['orm.em']->flush();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'errorCode' => 'Z019999999',
            'returnDate' => date('YmdHis'),
            'creditErrorCode' => 'A012060001',
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(false, $getResults);
        $this->object->error = 'エラーメッセージ';

        /*
         * 金額変更
         */
        // falseが返ること
        $this->assertFalse($this->object->doCreditChangePrice($OrderExtension));

        $memo05 = $YamatoOrderPayment->getMemo05();
        // 決済金額が更新されていないこと
        $this->assertEquals($expected['settle_price'], $memo05['settle_price']);
    }

    function test_doCreditChangePrice__クレジットカード決済でない場合__falseが返ること__エラーメッセージが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService();

        /*
         * 金額変更
         */
        // falseが返ること
        $this->assertFalse($this->object->doCreditChangePrice($OrderExtension));

        // エラーメッセージが返ること
        $this->assertContains('金額変更に対応していない決済です。', $this->object->error);
    }

    function test_doGetTradeInfo__リクエスト成功の場合__trueが返ること__決済情報が更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);

        $expected = array(
            // 受注情報から決済状況取得
            'memo04' => $YamatoOrderPayment->getMemo04(),
            // 受注情報から決済データ取得
            'memo05' => $YamatoOrderPayment->getMemo05(),
            // 受注情報から審査結果を取得
            'memo06' => $YamatoOrderPayment->getMemo06(),
            // 受注情報から決済ログ取得
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
            'resultCount' => 1,
            'resultData' => array(
                'orderNo' => $Order->getId(),
                'deviceDiv' => 2,
                'settleMethodDiv' => 0,
                'settleMethod' => 1,
                'statusInfo' => 1,
                'memberId' => Str::random(64),
                'crdCResCd' => '0' . date('YmdHms'),
                'crdCResDate' => date('YmdHms'),
                'threeDCode' => 0000,
                'slipNo' => Str::random(12),
            )
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(true, $getResults);

        /*
         * 決済状況取得
         */
        // trueが返ること
        $this->assertTrue($this->object->doGetTradeInfo($OrderExtension));

        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $YamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $YamatoOrderPayment->getMemo05());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $YamatoOrderPayment->getMemo09());
    }

    function test_doGetTradeInfo__支払方法が異なる場合__falseが返ること__決済状況が更新されないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);

        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        // 支払方法を変更
        $YamatoOrderPayment->setMemo03($this->const['YAMATO_PAYID_CVS']);

        $this->app['orm.em']->flush();

        $expected = $YamatoOrderPayment->getMemo04();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
            'resultCount' => 1,
            'resultData' => array(
                'orderNo' => $Order->getId(),
                'deviceDiv' => 2,
                'settleMethodDiv' => 1,
                'settleMethod' => 1,
                'statusInfo' => 1,
                'memberId' => Str::random(64),
                'crdCResCd' => '0' . date('YmdHms'),
                'crdCResDate' => date('YmdHms'),
                'threeDCode' => 0000,
                'slipNo' => Str::random(12),
            )
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(true, $getResults);

        /*
         * 決済状況取得
         */
        // falseが返ること
        $this->assertFalse($this->object->doGetTradeInfo($OrderExtension));

        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況が更新されていないこと
        $this->assertEquals($expected, $YamatoOrderPayment->getMemo04());
    }

    function test_doGetTradeInfo__リクエスト失敗の場合__falseが返ること__決済完了ページ表示用データが更新されないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);

        $expected = $YamatoOrderPayment->getMemo02();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 各種取引処理（UtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
            'resultCount' => 1,
            'resultData' => array(
                'orderNo' => $Order->getId(),
                'deviceDiv' => 2,
                'settleMethodDiv' => 0,
                'settleMethod' => 1,
                'statusInfo' => 1,
                'memberId' => Str::random(64),
                'crdCResCd' => '0' . date('YmdHms'),
                'crdCResDate' => date('YmdHms'),
                'threeDCode' => 0000,
                'slipNo' => Str::random(12),
            )
        );
        // UtilClientService（BaseClientService）モック化
        $this->object = $this->createUtilClientService(false, $getResults);
        $this->object->error = 'エラーメッセージ';

        /*
         * 決済状況取得
         */
        // falseが返ること
        $this->assertFalse($this->object->doGetTradeInfo($OrderExtension));

        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済完了ページ表示用データが更新されていないこと
        $this->assertEquals($expected, $YamatoOrderPayment->getMemo02());
    }

    private function createUtilClientService($sendRequest = false, $getResults = null)
    {
        $mock = $this->getMock('Plugin\YamatoPayment\Service\Client\UtilClientService', array('sendRequest', 'getResults', 'getError'), array($this->app));
        $mock->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue($sendRequest));
        $mock->expects($this->any())
            ->method('getResults')
            ->will($this->returnValue($getResults));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));

        return $mock;
    }
}
