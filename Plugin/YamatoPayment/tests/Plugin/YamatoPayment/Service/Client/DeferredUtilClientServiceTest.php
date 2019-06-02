<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Plugin\YamatoPayment\Service\AbstractServiceTestCase;
use Plugin\YamatoPayment\Util\PaymentUtil;
use Plugin\YamatoPayment\Util\PluginUtil;

class DeferredUtilClientServiceTest extends AbstractServiceTestCase
{
    /** @var  PluginUtil */
    var $PluginUtil;
    /** @var  PaymentUtil */
    var $PaymentUtil;
    /** @var YamatoPaymentMethod $YamatoPaymentMethod */
    var $YamatoPaymentMethod;

    /**
     * @var DeferredUtilClientService
     */
    protected $object;

    function setUp()
    {
        parent::setUp();
        $this->object = $this->app['yamato_payment.service.client.deferred_util'];

        $this->PluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->PaymentUtil = $this->app['yamato_payment.util.payment'];
        $this->YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']));

    }

    function test_doShipmentEntry__リクエストが成功した場合__trueと登録成功数1と登録失敗数0が配列で返ること__決済情報が更新されること__LastDelivSlipNumberが更新されること__決済状況が送り状番号登録済みに更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 決済状況：決済手続き中　審査結果：審査中
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);

        $old_last_deliv_slip = array();
        foreach ($YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $old_last_deliv_slip[] = $YamatoShippingDelivSlip->getLastDelivSlipNumber();
        }

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

        // 決済状況が送り状番号登録済みでないことを確認
        $this->assertNotEquals($this->const['DEFERRED_STATUS_REGIST_DELIV_SLIP'], $YamatoOrderPayment->getMemo04());

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'action_status' => 1,
            'result_code' => 0,
            'requestDate' => date('YmdHis'),
        );
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(true, $getResults);

        $actual = $this->object->doShipmentEntry($OrderExtension);

        // リクエスト結果（$actual[0]）はtrueが返ること
        $this->assertTrue($actual[0]);

        // 登録成功数（$actual[1]）は1が返ること
        $this->assertEquals(1, $actual[1]);

        // 登録失敗数（$actual[2]）は0が返ること
        $this->assertEquals(0, $actual[2]);

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 審査結果が更新されていること
        $this->assertNotEquals($expected['memo06'], $newYamatoOrderPayment->getMemo06());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());

        // 配送先情報取得
        $YamatoShippings = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->findBy(array(
                'order_id' => $OrderExtension->getOrderID(),
            ));

        // LastDelivSlipNumberが更新されること
        $key = 0;
        foreach ($YamatoShippings as $YamatoShipping) {
            /** @var YamatoShippingDelivSlip $YamatoShipping */
            $last_deliv_slip = $YamatoShipping->getLastDelivSlipNumber();
            $this->assertNotEquals($old_last_deliv_slip[$key], $last_deliv_slip);
            $key++;
        }

        // 決済状況が送り状番号登録済みに更新されること
        $this->assertEquals($this->const['DEFERRED_STATUS_REGIST_DELIV_SLIP'], $newYamatoOrderPayment->getMemo04());
    }

    function test_doShipmentEntry__リクエストが失敗した場合__falseと登録成功数0と登録失敗数1が配列で返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $this->createYamatoShippingDelivSlip($Order);

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(false);

        $actual = $this->object->doShipmentEntry($OrderExtension);

        // リクエスト結果（$actual[0]）はtrueが返ること
        $this->assertFalse($actual[0]);

        // 登録成功数（$actual[1]）は0が返ること
        $this->assertEquals(0, $actual[1]);

        // 登録失敗数（$actual[2]）は1が返ること
        $this->assertEquals(1, $actual[2]);
    }

    function test_doShipmentEntry__送信成功している場合__trueと登録成功数0と登録失敗数0が配列で返ること__決済状況が送り状番号登録済みに更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 決済状況：決済手続き中　審査結果：審査中
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);

        foreach ($YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $deliv_slip_number = $YamatoShippingDelivSlip->getDelivSlipNumber();
            $YamatoShippingDelivSlip->setLastDelivSlipNumber($deliv_slip_number);
            $this->app['orm.em']->flush();
        }

        // 決済状況が送り状番号登録済みでないことを確認
        $this->assertNotEquals($this->const['DEFERRED_STATUS_REGIST_DELIV_SLIP'], $YamatoOrderPayment->getMemo04());

        $actual = $this->object->doShipmentEntry($OrderExtension);

        // リクエスト結果（$actual[0]）はtrueが返ること
        $this->assertTrue($actual[0]);

        // 登録成功数（$actual[1]）は0が返ること
        $this->assertEquals(0, $actual[1]);

        // 登録失敗数（$actual[2]）は1が返ること
        $this->assertEquals(1, $actual[2]);

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況が送り状番号登録済みに更新されること
        $this->assertEquals($this->const['DEFERRED_STATUS_REGIST_DELIV_SLIP'], $newYamatoOrderPayment->getMemo04());
    }

    function test_doShipmentEntry__配送先情報が存在しない場合__falseと登録成功数0と登録失敗数0が配列で返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        $actual = $this->object->doShipmentEntry($OrderExtension);

        // リクエスト結果（$actual[0]）はtrueが返ること
        $this->assertFalse($actual[0]);

        // 登録成功数（$actual[1]）は0が返ること
        $this->assertEquals(0, $actual[1]);

        // 登録失敗数（$actual[2]）は0が返ること
        $this->assertEquals(0, $actual[2]);
    }

    function test_doShipmentCancel__リクエストが成功した場合__trueが返ること__決済情報が更新されること__決済状況が承認済みに更新されること__LastDelivSlipNumberが削除されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 決済状況：決済手続き中　審査結果：審査中
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        // 配送先情報作成
        $YamatoShippingDelivSlips = $this->createYamatoShippingDelivSlip($Order);

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

        // 決済状況が承認済みでないことを確認
        $this->assertNotEquals($this->const['DEFERRED_STATUS_AUTH_OK'], $YamatoOrderPayment->getMemo04());

        // LastDelivSlipNumberが存在することを確認
        foreach ($YamatoShippingDelivSlips as $YamatoShippingDelivSlip) {
            /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
            $this->assertNotNull($YamatoShippingDelivSlip->getLastDelivSlipNumber());
        }

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'action_status' => 1,
            'result_code' => 0,
            'requestDate' => date('YmdHis'),
        );
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(true, $getResults);

        // Trueが返ること
        $this->assertTrue($this->object->doShipmentCancel($OrderExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 審査結果が更新されていること
        $this->assertNotEquals($expected['memo06'], $newYamatoOrderPayment->getMemo06());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());

        // 決済状況が承認済みに更新されること
        $this->assertEquals($this->const['DEFERRED_STATUS_AUTH_OK'], $newYamatoOrderPayment->getMemo04());

        // 配送先情報取得
        $YamatoShippings = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->findBy(array(
                'order_id' => $OrderExtension->getOrderID(),
            ));

        // LastDelivSlipNumberが削除されること
        foreach ($YamatoShippings as $YamatoShipping) {
            /** @var YamatoShippingDelivSlip $YamatoShipping */
            $this->assertNull($YamatoShipping->getLastDelivSlipNumber());
        }
    }

    function test_doShipmentCancel__リクエストが失敗した場合__falseが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(false);

        // Falseが返ること
        $this->assertFalse($this->object->doShipmentCancel($OrderExtension));
    }

    function test_doCancel__リクエストが成功した場合__trueが返ること__決済情報が更新されること__決済状況が取消済みに更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 決済状況：決済手続き中　審査結果：審査中
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

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

        // 決済状況が取消済みでないことを確認
        $this->assertNotEquals($this->const['DEFERRED_STATUS_AUTH_CANCEL'], $YamatoOrderPayment->getMemo04());

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $getResults = array(
            'ycfStrCode' => $userSettings['ycf_str_code'],
            'orderNo' => $Order->getId(),
            'action_status' => 1,
            'result_code' => 0,
            'returnDate' => date('ymdHms'),
        );
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(true, $getResults);

        // Trueが返ること
        $this->assertTrue($this->object->doCancel($OrderExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 審査結果が更新されていること
        $this->assertNotEquals($expected['memo06'], $newYamatoOrderPayment->getMemo06());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());

        // 決済状況が取消済みに更新されること
        $this->assertEquals($this->const['DEFERRED_STATUS_AUTH_CANCEL'], $newYamatoOrderPayment->getMemo04());
    }

    function test_doCancel__リクエストが失敗した場合__falseが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 決済状況：決済手続き中　審査結果：審査中
        $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(false);

        // Falseが返ること
        $this->assertFalse($this->object->doCancel($OrderExtension));
    }

    function test_doCancel__後払い決済でない場合__falseが返ること__エラーメッセージが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(false);

        // Falseが返ること
        $this->assertFalse($this->object->doCancel($OrderExtension));

        $this->assertContains('与信取消エラー：与信取消に対応していない決済です。', $this->object->error);
    }

    function test_doGetAuthResult__リクエストが成功した場合__trueが返ること__決済情報が更新されること__決済状況が承認済みなこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 決済状況：決済手続き中　審査結果：審査中
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

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

        // 決済状況が承認済みでないことを確認
        $this->assertNotEquals($this->const['DEFERRED_STATUS_AUTH_OK'], $YamatoOrderPayment->getMemo04());

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $getResults = array(
            'ycfStrCode' => $userSettings['ycf_str_code'],
            'orderNo' => $Order->getId(),
            'result' => 1,
            'result_code' => 0,
            'returnDate' => date('ymdHms'),
        );
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(true, $getResults);

        // Trueが返ること
        $this->assertTrue($this->object->doGetAuthResult($OrderExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 審査結果が更新されていること
        $this->assertNotEquals($expected['memo06'], $newYamatoOrderPayment->getMemo06());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());

        // 決済状況が承認済みなことを確認
        $this->assertEquals($this->const['DEFERRED_STATUS_AUTH_OK'], $YamatoOrderPayment->getMemo04());
    }

    function test_doGetAuthResult__リクエストが失敗した場合__falseが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(false);

        // Falseが返ること
        $this->assertFalse($this->object->doGetAuthResult($OrderExtension));
    }

    function test_doGetOrderInfo__リクエストが成功した場合__trueが返ること__決済情報が更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 決済状況：決済手続き中　審査結果：審査中
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

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

        // 決済状況が承認済みでないことを確認
        $this->assertNotEquals($this->const['DEFERRED_STATUS_AUTH_OK'], $YamatoOrderPayment->getMemo04());

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $getResults = array(
            'ycfStrCode' => $userSettings['ycf_str_code'],
            'orderNo' => $Order->getId(),
            'result' => 1,
            'result_code' => 0,
            'warning' => 0,
            'returnDate' => date('ymdHms'),
        );
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(true, $getResults);

        // Trueが返ること
        $this->assertTrue($this->object->doGetOrderInfo($OrderExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 審査結果が更新されていること
        $this->assertNotEquals($expected['memo06'], $newYamatoOrderPayment->getMemo06());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());

        // 決済状況が承認済みなことを確認
        $this->assertEquals($this->const['DEFERRED_STATUS_AUTH_OK'], $YamatoOrderPayment->getMemo04());
    }

    function test_doGetOrderInfo__リクエストが失敗した場合__falseが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 後払い各種取引処理（DeferredClientService/BaseClientService）モック作成
         */
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(false);

        // Falseが返ること
        $this->assertFalse($this->object->doGetOrderInfo($OrderExtension));
    }

    private function createDeferredUtilClientService($sendOrderRequest = null, $getResults = null)
    {
        $mock = $this->getMock('Plugin\YamatoPayment\Service\Client\DeferredUtilClientService', array('sendRequest', 'getResults', 'getError'), array($this->app));
        $mock->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue($sendOrderRequest));
        $mock->expects($this->any())
            ->method('getResults')
            ->will($this->returnValue($getResults));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));

        return $mock;
    }

    function test_doChangePrice__リクエスト成功の場合__trueが返ること__決済金額が更新されること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);

        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order);

        $memo05 = $YamatoOrderPayment->getMemo05();
        $expected = $memo05['totalAmount'];

        // 金額変更
        $paymentTotal = $Order->getPaymentTotal();
        $paymentTotal = intval($paymentTotal) + 1000;
        $Order->setPaymentTotal($paymentTotal);
        $this->app['orm.em']->flush();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 各種取引処理（DeferredUtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHis'),
        );
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(true, $getResults);

        /*
         * 決済金額変更
         */
        $this->assertTrue($this->object->doChangePrice($OrderExtension));

        // 決済金額が更新されること
        $memo05 = $YamatoOrderPayment->getMemo05();
        $this->assertNotEquals($expected, $memo05['totalAmount']);
    }

    function test_doChangePrice__リクエスト失敗の場合__falseが返ること__決済金額が更新されないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);

        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order);
        $memo05 = $YamatoOrderPayment->getMemo05();

        $expected = array(
            'totalAmount' => $memo05['totalAmount'],
        );

        // 金額変更
        $paymentTotal = $Order->getPaymentTotal();
        $paymentTotal = intval($paymentTotal) + 1000;
        $Order->setPaymentTotal($paymentTotal);
        $this->app['orm.em']->flush();

        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 決済モジュール 決済処理: 各種取引処理（DeferredUtilClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'errorCode' => 'Z019999999',
            'returnDate' => date('YmdHis'),
            'creditErrorCode' => 'A012060001',
        );
        // DeferredUtilClientService（BaseClientService）モック化
        $this->object = $this->createDeferredUtilClientService(false, $getResults);
        $this->object->error = 'エラーメッセージ';

        /*
         * 金額変更
         */
        // falseが返ること
        $this->assertFalse($this->object->doChangePrice($OrderExtension));

        $memo05 = $YamatoOrderPayment->getMemo05();
        // 決済金額が更新されていないこと
        $this->assertEquals($expected['totalAmount'], $memo05['totalAmount']);
    }

    function test_doChangePrice__クロネコ代金後払い決済でない場合__falseが返ること__エラーメッセージが返ること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());

        /*
         * 金額変更
         */
        // falseが返ること
        $this->assertFalse($this->object->doChangePrice($OrderExtension));

        // エラーメッセージが返ること
        $this->assertContains('金額変更に対応していない決済です。', $this->object->error);
    }
}
