<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Util;

use Eccube\Entity\Master\ProductType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Shipping;
use Eccube\Util\Str;
use Plugin\YamatoPayment\AbstractYamatoPaymentTestCase;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoOrderScheduledShippingDate;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoProduct;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\Yaml\Yaml;
use Faker\Generator;

class PaymentUtilTest extends AbstractYamatoPaymentTestCase
{
    /** @var PaymentUtil */
    var $object;

    var $error;

    /** @var Client */
    protected $client;

    function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
        $this->object = $this->app['yamato_payment.util.payment'];
        // プラグイン情報を取得
        $config_file = __DIR__ . '/../../../../config.yml';
        $pluginInfo = Yaml::parse($config_file);
        $this->const = $pluginInfo['const'];

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
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->client = null;
    }

    function test_getArrays()
    {
        // 決済方法の名前一覧が配列であれば、true
        $actual = $this->object->getPaymentTypeNames();
        $this->assertTrue(is_array($actual));

        // 決済方式の内部名一覧が配列であれば、true
        $actual = $this->object->getPaymentTypeCodes();
        $this->assertTrue(is_array($actual));

        // 支払種別一覧が配列であれば、true
        $actual = $this->object->getCreditPayMethod();
        $this->assertTrue(is_array($actual));

        // コンビニの名称一覧が配列であれば、true
        $actual = $this->object->getConveni();
        $this->assertTrue(is_array($actual));

        // 電子マネー決済の名称一覧が配列であれば、true
        $actual = $this->object->getEmoney();
        $this->assertTrue(is_array($actual));

        // Webコレクト決済状況の名称一覧が配列であれば、true
        $actual = $this->object->getPaymentStatus();
        $this->assertTrue(is_array($actual));

        // クレジット決済状況の名称一覧が配列であれば、true
        $actual = $this->object->getCreditPaymentStatus();
        $this->assertTrue(is_array($actual));

        // コンビニ決済状況の名称一覧が配列であれば、true
        $actual = $this->object->getCvsPaymentStatus();
        $this->assertTrue(is_array($actual));

        // クロネコ代金後払い決済の与信結果一覧が配列であれば、true
        $actual = $this->object->getCreditResult();
        $this->assertTrue(is_array($actual));

        // クロネコ代金後払い決済の取引情報取得一覧が配列であれば、true
        $actual = $this->object->getDeferredStatus();
        $this->assertTrue(is_array($actual));

        // マスター：タイムコード一覧が配列であれば、true
        $actual = $this->object->getDelivTimeCode();
        $this->assertTrue(is_array($actual));

        // マスター：動作モード一覧が配列であれば、true
        $actual = $this->object->getExecMode();
        $this->assertTrue(is_array($actual));

        // マスター：オプションサービス一覧が配列であれば、true
        $actual = $this->object->getUseOption();
        $this->assertTrue(is_array($actual));

        // マスター：利用一覧が配列であれば、true
        $actual = $this->object->getUtilization();
        $this->assertTrue(is_array($actual));

        // マスター：利用一覧が配列であれば、true
        $actual = $this->object->getUtilizationFlg();
        $this->assertTrue(is_array($actual));

        // マスター：請求書同梱一覧が配列であれば、true
        $actual = $this->object->getSendDivision();
        $this->assertTrue(is_array($actual));

        // マスター：出力一覧が配列であれば、true
        $actual = $this->object->getOutput();
        $this->assertTrue(is_array($actual));

        // マスター：送り状種別一覧が配列であれば、true
        $actual = $this->object->getDelivSlipType();
        $this->assertTrue(is_array($actual));

        // マスター：クール便区別一覧が配列であれば、true
        $actual = $this->object->getCool();
        $this->assertTrue(is_array($actual));

        // マスター：ハイフンの有無一覧が配列であれば、true
        $actual = $this->object->getHyphen();
        $this->assertTrue(is_array($actual));

        // マスター：ご依頼主出力一覧が配列であれば、true
        $actual = $this->object->getRequestOutput();
        $this->assertTrue(is_array($actual));

        // マスター：配送コード一覧が配列であれば、true
        $actual = $this->object->getDeliveryCode();
        $this->assertTrue(is_array($actual));

        // マスター：B2取込フォーマット一覧が配列であれば、true
        $actual = $this->object->getB2ImportFormat();
        $this->assertTrue(is_array($actual));
    }

    function test_getOrderPayData_存在しない受注IDを渡すとfalseが返る()
    {
        $orderId = 0;
        $this->assertfalse($this->object->getOrderPayData($orderId));
    }

    function test_getOrderPayData_決済情報が存在する受注IDを渡すと受注拡張データが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();
        $memo05OrderId = 1;

        // 受注出荷予定日情報作成
        $YamatoOrderScheduledShippingDate = new YamatoOrderScheduledShippingDate();
        $YamatoOrderScheduledShippingDate->setId($orderId);
        $this->app['orm.em']->persist($YamatoOrderScheduledShippingDate);

        // 受注決済情報作成
        $YamatoOrderPayment = new YamatoOrderPayment();
        $YamatoOrderPayment->setId($orderId);
        $YamatoOrderPayment->setMemo05(array(
            'OrderID' => $memo05OrderId,
        ));
        $this->app['orm.em']->persist($YamatoOrderPayment);
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $OrderExtention = $this->object->getOrderPayData($orderId);

        $expected = $memo05OrderId;
        $this->assertEquals($expected, $OrderExtention->getOrderID());

        $expected = $YamatoOrderScheduledShippingDate;
        $this->assertEquals($expected, $OrderExtention->getYamatoOrderScheduledShippingDate());

        $expected = $YamatoOrderPayment;
        $this->assertEquals($expected, $OrderExtention->getYamatoOrderPayment());

        // 支払情報のpreOrderIdが存在する
        $paymentData = $OrderExtention->getPaymentData();
        $this->assertTrue(isset($paymentData['preOrderId']));
    }

    function test_getOrderPayData_受注情報の支払方法IDと決済情報の支払方法IDが一致しない場合_決済情報の支払方法は更新される()
    {
        // クロネコ代金後払い決済の受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order);

        // 決済情報の支払方法をクレジットカード決済へ変更
        $YamatoOrderPayment->setMemo03(10);
        $this->app['orm.em']->persist($YamatoOrderPayment);
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $OrderExtention = $this->object->getOrderPayData($orderId);

        // 決済情報の支払方法は、受注情報のクロネコ代金後払い決済IDに更新されること
        $this->assertEquals(60, $OrderExtention->getYamatoOrderPayment()->getMemo03());
    }

    function test_getOrderPayData_preOrderIdがnullの受注IDを渡すと支払情報のpreOrderIdが存在しないの受注拡張データが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();
        $Order->setPreOrderId(null);
        $this->app['orm.em']->persist($Order);

        // 受注決済情報作成
        $YamatoOrderPayment = new YamatoOrderPayment();
        $YamatoOrderPayment->setId($orderId);
        $this->app['orm.em']->persist($YamatoOrderPayment);
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $OrderExtention = $this->object->getOrderPayData($orderId);

        // 支払情報のpreOrderIdが存在しない受注拡張データが返る
        $paymentData = $OrderExtention->getPaymentData();
        $this->assertTrue(!isset($paymentData['preOrderId']));
    }

    function test_getOrderPayData_受注決済情報のregister_cardにデータが存在する受注IDを渡すと支払情報のregister_cardデータが存在する受注拡張データが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 受注決済情報作成
        $YamatoOrderPayment = new YamatoOrderPayment();
        $YamatoOrderPayment->setId($orderId);
        $YamatoOrderPayment->setMemo05(array(
            'register_card' => 12345678,
        ));
        $this->app['orm.em']->persist($YamatoOrderPayment);
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $OrderExtention = $this->object->getOrderPayData($orderId);

        // 支払情報のregister_cardデータが存在する受注拡張データが返る
        $paymentData = $OrderExtention->getPaymentData();
        $this->assertNotEmpty($paymentData['register_card']);
    }

    function test_getOrderPayData_決済情報が設定されていない受注IDを渡すと受注IDがNULLの受注拡張データが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 決済情報が設定されていないことを確認
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($orderId);
        $this->assertNull($YamatoOrderPayment);

        // テスト対象メソッド実行
        $OrderExtention = $this->object->getOrderPayData($orderId);

        // 受注拡張データの受注IDはnullが返る
        $this->assertNull($OrderExtention->getOrderID());

        $expected = $orderId;
        $this->assertEquals($expected, $OrderExtention->getYamatoOrderPayment()->getId());
        $this->assertEquals($expected, $OrderExtention->getYamatoOrderScheduledShippingDate()->getId());
    }

    function test_setOrderPayData_受注情報と決済情報を渡すと決済情報が追加された受注情報が返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);

        // 決済情報作成
        $payData = array(
            'action_status' => 1,
            'result_code' => 1,
            'function_div' => null,
        );

        $expected = array(
            'memo04' => $YamatoOrderPayment->getMemo04(),
            'memo05' => $YamatoOrderPayment->getMemo05(),
            'memo06' => $YamatoOrderPayment->getMemo06(),
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // テスト対象メソッド実行
        $YamatoOrderPayment = $this->object->setOrderPayData($YamatoOrderPayment, $payData);

        // memo04が更新されること
        $this->assertNotEquals($expected['memo04'], $YamatoOrderPayment->getMemo04());

        // memo05が更新されること
        $this->assertNotEquals($expected['memo05'], $YamatoOrderPayment->getMemo05());

        // memo06が更新されること
        $this->assertNotEquals($expected['memo06'], $YamatoOrderPayment->getMemo06());

        // memo09が更新されること
        $this->assertNotEquals($expected['memo09'], $YamatoOrderPayment->getMemo09());
    }

    function test_setOrderPayData_受注情報と空の決済情報を渡すとmemo09のみ更新された受注情報が返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);

        //  空の決済情報作成
        $payData = array();

        $expected = array(
            'memo04' => $YamatoOrderPayment->getMemo04(),
            'memo05' => $YamatoOrderPayment->getMemo05(),
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // テスト対象メソッド実行
        $YamatoOrderPayment = $this->object->setOrderPayData($YamatoOrderPayment, $payData);

        // memo04が更新されないこと
        $this->assertEquals($expected['memo04'], $YamatoOrderPayment->getMemo04());

        // memo05が更新されないこと
        $this->assertEquals($expected['memo05'], $YamatoOrderPayment->getMemo05());

        // memo09が更新されること
        $this->assertNotEquals($expected['memo09'], $YamatoOrderPayment->getMemo09());
    }

    function test_setOrderPaymentViewData_受注情報とクロネコ代金後払い決済情報を渡すと決済情報を追加した受注情報が返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order);
        $expected = $YamatoOrderPayment->getMemo02();

        // クロネコ代金後払い決済情報作成
        $payData = array(
            'returnDate' => 20161010,
            'crdCResCd' => 1234,
        );

        // クロネコ代金後払い決済支払方法情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array(
                    'memo03' => $this->const['YAMATO_PAYID_DEFERRED'],
                )
            );
        $PaymentExtension = $this->object->getPaymentTypeConfig($YamatoPaymentMethod->getId());

        // テスト対象メソッド実行
        $YamatoOrderPayment = $this->object->setOrderPaymentViewData($YamatoOrderPayment, $payData, $PaymentExtension);

        // memo02が更新されること
        $this->assertNotEquals($expected, $YamatoOrderPayment->getMemo02());
    }

    function test_setOrderPaymentViewData_受注情報とクレジットカード決済情報を渡すと決済情報を追加した受注情報が返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        $expected = $YamatoOrderPayment->getMemo02();

        // クレジットカード決済情報作成
        $payData = array(
            'returnDate' => 20161010,
            'crdCResCd' => 1234,
        );

        // クレジットカード決済支払方法情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array(
                    'memo03' => $this->const['YAMATO_PAYID_CREDIT'],
                )
            );
        $PaymentExtension = $this->object->getPaymentTypeConfig($YamatoPaymentMethod->getId());

        // テスト対象メソッド実行
        $YamatoOrderPayment = $this->object->setOrderPaymentViewData($YamatoOrderPayment, $payData, $PaymentExtension);

        // memo02が更新されること
        $this->assertNotEquals($expected, $YamatoOrderPayment->getMemo02());
    }

    function test_setOrderPaymentViewData_受注情報とコンビニ決済情報を渡すと決済情報を追加した受注情報が返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCvs($Order);
        $expected = $YamatoOrderPayment->getMemo02();

        // コンビニ決済情報作成
        $payData = array(
            'returnDate' => 20161010,
            'billingNo' => 123456789,
            'billingUrl' => 'https://******',
            'companyCode' => 20,
            'orderNoF' => 123456789,
            'econNo' => 123456789,
            'expiredDate' => 20161031,
            'cvs' => 21,
        );

        // コンビニ決済支払方法情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array(
                    'memo03' => $this->const['YAMATO_PAYID_CVS'],
                )
            );
        $PaymentExtension = $this->object->getPaymentTypeConfig($YamatoPaymentMethod->getId());

        // テスト対象メソッド実行
        $YamatoOrderPayment = $this->object->setOrderPaymentViewData($YamatoOrderPayment, $payData, $PaymentExtension);

        // memo02が更新されること
        $this->assertNotEquals($expected, $YamatoOrderPayment->getMemo02());
    }

    function test_setOrderPaymentViewData_受注情報とコンビニ支払方法データが空の支払方法情報を渡すと決済完了案内が空の受注情報が返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        // 受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCvs($Order);
        $expected = $YamatoOrderPayment->getMemo02();

        // コンビニ決済情報作成
        $payData = array(
            'returnDate' => 20161010,
            'billingNo' => 123456789,
            'billingUrl' => 'https://******',
            'companyCode' => 20,
            'orderNoF' => 123456789,
            'econNo' => 123456789,
            'expiredDate' => 20161031,
            'cvs' => 21,
        );

        // コンビニ決済支払方法情報取得・作成
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array(
                    'memo03' => $this->const['YAMATO_PAYID_CVS'],
                )
            );
        $PaymentExtension = $this->object->getPaymentTypeConfig($YamatoPaymentMethod->getId());
        $PaymentExtension->setArrPaymentConfig(null);

        // テスト対象メソッド実行
        $YamatoOrderPayment = $this->object->setOrderPaymentViewData($YamatoOrderPayment, $payData, $PaymentExtension);

        // memo02が更新されること
        $this->assertNotEquals($expected, $YamatoOrderPayment->getMemo02());

        // 決済完了案内が空であること
        $memo02 = $YamatoOrderPayment->getMemo02();
        $this->assertFalse(isset($memo02['order_mail_title_' . $payData['cvs']]['name']));
        $this->assertFalse(isset($memo02['order_mail_title_' . $payData['cvs']]['value']));
    }

    function test_setOrderPaymentViewData_空の受注情報と空の決済情報と空の支払方法情報を渡すと空の受注情報が返る()
    {
        // 空の受注決済情報作成
        $YamatoOrderPayment = new YamatoOrderPayment();

        $expected = $YamatoOrderPayment;

        // 空の決済情報作成
        $payData = array();

        // 支払方法情報取得
        $PaymentExtension = $this->object->getPaymentTypeConfig(0);

        // テスト対象メソッド実行
        $YamatoOrderPayment = $this->object->setOrderPaymentViewData($YamatoOrderPayment, $payData, $PaymentExtension);

        // 受注情報が設定されていないこと
        $this->assertEquals($expected, $YamatoOrderPayment);
    }

    function test_getPaymentTypeConfig_支払方法拡張データが返る()
    {
        $memo05 = '1234567890';

        // 支払方法IDの取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array(
                    'memo03' => $this->const['YAMATO_PAYID_CVS'],
                )
            );
        $YamatoPaymentMethod->setMemo05($memo05);

        // テスト対象メソッド実行
        $PaymentExtension = $this->object->getPaymentTypeConfig($YamatoPaymentMethod->getId());

        // 支払方法拡張データが返ること
        $this->assertEquals($YamatoPaymentMethod, $PaymentExtension->getYamatoPaymentMethod());
        $this->assertEquals($this->const['YAMATO_PAYCODE_CVS'], $PaymentExtension->getPaymentCode());
        $this->assertEquals($memo05, $PaymentExtension->getArrPaymentConfig());
    }

    function test_getPaymentTypeConfig_存在しない支払方法IDを渡すと空の支払方法拡張データが返る()
    {
        // テスト対象メソッド実行
        $PaymentExtension = $this->object->getPaymentTypeConfig(0);

        // 支払方法拡張データが設定されていないこと
        $this->assertEmpty($PaymentExtension->getYamatoPaymentMethod());
        $this->assertEmpty($PaymentExtension->getPaymentCode());
        $this->assertEmpty($PaymentExtension->getArrPaymentConfig());
    }

    function test_updateOrderPayStatus_受注拡張データと決済ステータスを渡すと受注情報の決済ステータスIDが更新される()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 受注拡張データの取得
        /** @var OrderExtension $OrderExtension */
        $OrderExtension = $this->object->getOrderPayData($orderId);

        $expected = $OrderExtension->getYamatoOrderPayment()->getMemo04();

        // 決済ステータスIDを取得
        $config = $this->const['DEFERRED_STATUS_REGIST_DELIV_SLIP'];

        // テスト対象メソッド実行
        $this->object->updateOrderPayStatus($OrderExtension, $config);

        // 受注情報の決済ステータスIDが更新されること
        $this->assertNotEquals($expected, $OrderExtension->getYamatoOrderPayment()->getMemo04());
    }

    function test_updateOrderSettlePrice_受注決済情報の決済金額が更新される_クレジットカード決済()
    {
        // クレジットカード決済の受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();
        $this->createOrderPaymentDataCredit($Order);

        // 金額を変更
        $Order->setPaymentTotal($Order->getPaymentTotal() + 1000);
        $this->app['orm.em']->persist($Order);
        $this->app['orm.em']->flush();

        // 受注拡張データの取得
        /** @var OrderExtension $OrderExtension */
        $OrderExtension = $this->object->getOrderPayData($orderId);

        $expected = $OrderExtension->getYamatoOrderPayment()->getMemo05();

        // テスト対象メソッド実行
        $this->object->updateOrderSettlePrice($OrderExtension);

        // 受注決済情報の決済金額が更新されること
        $this->assertNotEquals($expected, $OrderExtension->getYamatoOrderPayment()->getMemo05());
    }

    function test_updateOrderSettlePrice_受注決済情報の決済金額が更新される_クロネコ代金後払い決済()
    {
        // クロネコ代金後払い決済の受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();
        $this->createOrderPaymentDataDeferred($Order);

        // 金額を変更
        $Order->setPaymentTotal($Order->getPaymentTotal() + 1000);
        $this->app['orm.em']->persist($Order);
        $this->app['orm.em']->flush();

        // 受注拡張データの取得
        /** @var OrderExtension $OrderExtension */
        $OrderExtension = $this->object->getOrderPayData($orderId);

        $expected = $OrderExtension->getYamatoOrderPayment()->getMemo05();

        // テスト対象メソッド実行
        $this->object->updateOrderSettlePrice($OrderExtension);

        // 受注決済情報の決済金額が更新されること
        $this->assertNotEquals($expected, $OrderExtension->getYamatoOrderPayment()->getMemo05());
    }

    function test_getMaxScheduledShippingDate_予約商品が存在する受注IDを渡すと予約商品出荷予定日を返す()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 商品種別取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);

        $expected = 20161010;
        foreach ($Order->getOrderDetails() as $OrderDetail) {
            // 受注詳細情報の商品種別更新
            /** @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);

            // 商品マスタ追加項目情報作成
            $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($OrderDetail->getProduct()->getId());
            if (is_null($YamatoProduct)) {
                $YamatoProduct = new YamatoProduct();
                $YamatoProduct->setId($OrderDetail->getProduct()->getId());
            }
            $YamatoProduct->setReserveDate($expected);
            $YamatoProduct->setNotDeferredFlg(false);
            $this->app['orm.em']->persist($YamatoProduct);
        }
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $maxScheduledDate = $this->object->getMaxScheduledShippingDate($orderId);

        // 予約商品出荷予定日が返ること
        $this->assertEquals($expected, $maxScheduledDate);
    }

    function test_getMaxScheduledShippingDate_予約商品が存在する複数の受注IDを渡すと出荷予定日が一番未来の予約商品出荷予定日を返す()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 商品種別取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);

        // 商品追加用受注情報作成
        $Order2 = $this->createOrderData();

        // 受注詳細情報に、商品追加用受注詳細情報を追加
        foreach ($Order2->getOrderDetails() as $OrderDetail2) {
            $Order->addOrderDetail($OrderDetail2);
            $this->app['orm.em']->persist($Order);
        }
        $this->app['orm.em']->flush();

        /** @var Generator $faker */
        $faker = $this->getFaker();
        $reserveDate = array();
        foreach ($Order->getOrderDetails() as $OrderDetail) {
            // 受注詳細情報の商品種別更新
            /** @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);

            // 予約商品情報作成
            $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($OrderDetail->getProduct()->getId());
            if (is_null($YamatoProduct)) {
                $YamatoProduct = new YamatoProduct();
                $YamatoProduct->setId($OrderDetail->getProduct()->getId());
            }
            $YamatoProduct->setReserveDate($faker->numberBetween($min = 20200101, $max = 20300101));
            $YamatoProduct->setNotDeferredFlg(false);
            $this->app['orm.em']->persist($YamatoProduct);
        }
        $this->app['orm.em']->flush();

        foreach ($Order->getOrderDetails() as $OrderDetail) {
            // 予約商品情報取得
            $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($OrderDetail->getProduct()->getId());
            // 予約商品出荷予定日取得
            $reserveDate[] = $YamatoProduct->getReserveDate();
        }

        $expected = max($reserveDate);

        // テスト対象メソッド実行
        $maxScheduledDate = $this->object->getMaxScheduledShippingDate($orderId);

        // 一番未来の予約商品出荷予定日が設定されること
        $this->assertEquals($expected, $maxScheduledDate);
    }

    function test_getMaxScheduledShippingDate_商品種別が予約商品かつ商品マスタ拡張データが存在しない受注IDを渡すと空白を返す()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 商品種別取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);

        foreach ($Order->getOrderDetails() as $OrderDetail) {
            // 受注詳細情報の商品種別更新
            /** @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);
        }
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $maxScheduledDate = $this->object->getMaxScheduledShippingDate($orderId);

        // 一番未来の予約商品出荷予定日が設定されること
        $this->assertEmpty($maxScheduledDate);
    }

    function test_getMaxScheduledShippingDate_商品種別が予約商品でない受注IDを渡すと空白を返す()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // テスト対象メソッド実行
        $maxScheduledDate = $this->object->getMaxScheduledShippingDate($orderId);

        // 予約商品出荷予定日が設定されないこと
        $this->assertEmpty($maxScheduledDate);
    }

    function test_isReserve_予約商品が含まれていない受注情報を渡すとfalseが返る()
    {
        // オプションサービスを契約済みに設定
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $userSettings['use_option'] = 0;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);

        // 受注情報の作成
        $Order = $this->createOrderData();

        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isReserve(false, $Order));
    }

    function test_isReserve_オプションサービスを契約していない場合falseが返る()
    {
        // 受注情報の作成
        $Order = $this->createOrderData();

        // UserSettingの登録
        $userSettings['use_option'] = 1;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);

        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isReserve(true, $Order));
    }

    function test_isReserve_出荷予定日がnullの場合falseが返る()
    {
        // 受注情報の作成
        $Order = new Order();
        $this->app['orm.em']->persist($Order);
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isReserve(true, $Order));
    }

    function test_isReserve_予約商品出荷予定日が再与信期限を超えた予約商品の受注情報を渡すとfalseが返る()
    {
        // オプションサービスを契約済みに設定
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $userSettings['use_option'] = 0;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);

        // 受注情報の作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 受注詳細情報の取得
        $OrderDetails = $this->app['eccube.repository.order']
            ->find($orderId)
            ->getOrderDetails();

        // 商品種別取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);

        foreach ($OrderDetails as $OrderDetail) {
            // 受注詳細情報の商品種別更新
            /* @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);

            // 予約商品情報作成
            $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($OrderDetail->getProduct()->getId());
            if (is_null($YamatoProduct)) {
                $YamatoProduct = new YamatoProduct();
                $YamatoProduct->setId($OrderDetail->getProduct()->getId());
            }
            $YamatoProduct->setReserveDate(date('Ymd'));
            $YamatoProduct->setNotDeferredFlg(false);
            $this->app['orm.em']->persist($YamatoProduct);
        }
        $this->app['orm.em']->flush();

        $this->assertFalse($this->object->isReserve(true, $Order));
    }

    function test_isReserve_予約商品出荷予定日が再与信期限内の予約商品の受注情報を渡すとtrueが返る()
    {
        // オプションサービスを契約済みに設定
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $userSettings['use_option'] = 0;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);

        // 受注情報の作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 受注詳細情報の取得
        $OrderDetails = $this->app['eccube.repository.order']
            ->find($orderId)
            ->getOrderDetails();

        // 商品種別取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);

        /** @var Generator $faker */
        $faker = $this->getFaker();

        foreach ($OrderDetails as $OrderDetail) {
            // 受注詳細情報の商品種別更新
            /* @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);

            // 予約商品情報作成
            $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($OrderDetail->getProduct()->getId());
            if (is_null($YamatoProduct)) {
                $YamatoProduct = new YamatoProduct();
                $YamatoProduct->setId($OrderDetail->getProduct()->getId());
            }
            $YamatoProduct->setReserveDate($faker->numberBetween($min = 20300101, $max = 20300131));
            $YamatoProduct->setNotDeferredFlg(false);
            $this->app['orm.em']->persist($YamatoProduct);
        }
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isReserve(true, $Order));
    }

    function test_sfIsOverDeadLineReCredit_予約商品出荷予定日が再与信期限日前ならtrueが返る()
    {
        $scheduled_shipping_date = date('Ymd', strtotime("+10 day"));
        $this->assertTrue($this->object->isWithinReCreditLimit($scheduled_shipping_date));
    }

    function test_sfIsOverDeadLineReCredit_予約商品出荷予定日が再与信期限日を過ぎているならfalseが返る()
    {
        $scheduled_shipping_date = date('Ymd', strtotime("+9 day"));
        $this->assertFalse($this->object->isWithinReCreditLimit($scheduled_shipping_date));
    }

    function test_isReservedOrder_予約商品が含まれていない受注情報を渡すとfalseが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();

        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isReservedOrder($Order));
    }

    function test_isReservedOrder_予約商品を含む受注情報を渡すとtrueが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 受注詳細情報を取得
        $OrderDetails = $this->app['eccube.repository.order']
            ->find($orderId)
            ->getOrderDetails();

        // 商品種別取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);

        foreach ($OrderDetails as $OrderDetail) {
            // 受注詳細情報の商品種別更新
            /* @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);
        }
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isReservedOrder(
            $Order
        ));
    }

    function test_isReservedOrder_複数商品のうち一つでも予約商品を含む受注情報を渡すとtrueが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();

        // 商品種別取得
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);

        // 商品追加用受注情報作成
        $Order2 = $this->createOrderData();
        $orderId2 = $Order2->getId();

        // 商品追加用受注詳細情報取得
        $OrderDetails = $this->app['eccube.repository.order']
            ->find($orderId2)
            ->getOrderDetails();

        foreach ($OrderDetails as $OrderDetail) {
            // 商品追加用受注詳細情報の商品種別更新
            /* @var OrderDetail $OrderDetail */
            $OrderDetail->getProductClass()->setProductType($ProductType);
            $this->app['orm.em']->persist($OrderDetail);

            // 受注詳細情報に、商品追加用受注詳細情報を追加
            $Order->addOrderDetail($OrderDetail);
            $this->app['orm.em']->persist($Order);
        }
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isReservedOrder(
            $Order
        ));
    }

    function test_isOption()
    {
        /*
         * オプションサービスを契約していない場合、
         * falseが返る
         */
        $userSettings['use_option'] = 1;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);
        $this->assertFalse($this->object->isOption());

        /*
         * オプションサービスを契約してる場合、
         * Trueが返る
         */
        $userSettings['use_option'] = 0;
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);
        $this->assertTrue($this->object->isOption());
    }

    function test_isCheckPaymentMethod_クレジットカード決済のチェック()
    {
        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCheckPaymentMethod(
            $this->const['CREDIT_METHOD_UC'],
            $this->const['YAMATO_PAYID_CREDIT']
        ));

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isCheckPaymentMethod(
            $this->const['CREDIT_METHOD_UC'],
            $this->const['YAMATO_PAYID_CVS']
        ));
    }

    function test_isCheckPaymentMethod_コンビニ決済のチェック()
    {
        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCheckPaymentMethod(
            $this->const['CONVENI_ID_SEVENELEVEN'],
            $this->const['YAMATO_PAYID_CVS']
        ));

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isCheckPaymentMethod(
            $this->const['CONVENI_ID_SEVENELEVEN'],
            $this->const['YAMATO_PAYID_CREDIT']
        ));
    }

    function test_isCheckPaymentMethod_電子マネー楽天Edy決済のチェック()
    {
        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_RAKUTENEDY'],
            $this->const['YAMATO_PAYID_EDY']
        ));

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_RAKUTENEDY'],
            $this->const['YAMATO_PAYID_CREDIT']
        ));
    }

    function test_isCheckPaymentMethod_電子マネー楽天モバイルEdy決済のチェック()
    {
        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_M_RAKUTENEDY'],
            $this->const['YAMATO_PAYID_MOBILEEDY']
        ));

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_M_RAKUTENEDY'],
            $this->const['YAMATO_PAYID_CREDIT']
        ));
    }

    function test_isCheckPaymentMethod_電子マネーSuica決済のチェック()
    {
        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_SUICA'],
            $this->const['YAMATO_PAYID_SUICA']
        ));

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_SUICA'],
            $this->const['YAMATO_PAYID_CREDIT']
        ));
    }

    function test_isCheckPaymentMethod_電子マネーモバイルSuica決済のチェック()
    {
        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_M_SUICA'],
            $this->const['YAMATO_PAYID_MOBILESUICA']
        ));

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_M_SUICA'],
            $this->const['YAMATO_PAYID_CREDIT']
        ));
    }

    function test_isCheckPaymentMethod_電子マネーWAON決済のチェック()
    {
        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_WAON'],
            $this->const['YAMATO_PAYID_WAON']
        ));

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_WAON'],
            $this->const['YAMATO_PAYID_CREDIT']
        ));
    }

    function test_isCheckPaymentMethod_電子マネーモバイルWAON決済のチェック()
    {
        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_M_WAON'],
            $this->const['YAMATO_PAYID_MOBILEWAON']
        ));

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isCheckPaymentMethod(
            $this->const['EMONEY_METHOD_M_WAON'],
            $this->const['YAMATO_PAYID_CREDIT']
        ));
    }

    function test_isCheckPaymentMethod_ネットバンク決済のチェック()
    {
        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCheckPaymentMethod(
            $this->const['NETBANK_METHOD_RAKUTENBANK'],
            $this->const['YAMATO_PAYID_NETBANK']
        ));

        // テスト対象メソッド実行
        // trueが返ること
        $this->assertTrue($this->object->isCheckPaymentMethod(
            $this->const['NETBANK_METHOD_RAKUTENBANK'],
            $this->const['YAMATO_PAYID_CREDIT']
        ));
    }

    function test_checkErrorShipmentEntryForCredit_エラーがない場合_空白が返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        // 取引情報の更新
        $YamatoOrderPayment->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_AUTH']);

        // 出荷情報取得
        $Shippings = $Order->getShippings()->getValues();

        // 配送伝票番号情報作成
        $ShippingDelivSlip = new YamatoShippingDelivSlip();
        $ShippingDelivSlip->setId($Shippings[0]['id']);
        $ShippingDelivSlip->setOrderId($orderId);
        $ShippingDelivSlip->setDelivSlipNumber('12345678902');
        $this->app['orm.em']->persist($ShippingDelivSlip);
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForCredit($orderId);

        // 空白が返ること
        $this->assertEmpty($actual);
    }

    function test_checkErrorShipmentEntryForCredit_エラーがない場合_空白が返る_複数配送()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // 受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        // 取引情報の更新
        $YamatoOrderPayment->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_AUTH']);

        // 出荷情報取得
        $Shippings = $Order->getShippings()->getValues();

        // 配送伝票番号情報作成
        $ShippingDelivSlip = new YamatoShippingDelivSlip();
        $ShippingDelivSlip->setId($Shippings[0]['id']);
        $ShippingDelivSlip->setOrderId($orderId);
        $ShippingDelivSlip->setDelivSlipNumber('12345678903');

        for ($i = 0; $i < 1; $i++) {
            // 配送先追加用受注情報作成
            $Order2 = $this->createOrderData();

            // 配送先追加用出荷情報取得
            $Shippings2 = $Order2->getShippings()->getValues();

            // 受注情報に配送先追加用出荷情報を追加
            $Order->addShipping($Shippings2[0]);

            // 配送先追加用配送伝票番号情報作成
            $ShippingDelivSlip2 = new YamatoShippingDelivSlip();
            $ShippingDelivSlip2->setId($Shippings2[0]['id']);
            $ShippingDelivSlip2->setOrderId($orderId);
            $ShippingDelivSlip2->setDelivSlipNumber('12345678904');
            $this->app['orm.em']->persist($ShippingDelivSlip2);
        }
        $this->app['orm.em']->persist($Order);
        $this->app['orm.em']->persist($ShippingDelivSlip);
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForCredit($orderId);

        // 空白が返ること
        $this->assertEmpty($actual);
    }

    function test_checkErrorShipmentEntryForCredit_クレジットカード決済でない受注IDを渡すとメッセージが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クレジットカード決済以外の受注決済情報作成
        $this->createOrderPaymentDataCvs($Order);

        $expected = '操作に対応していない決済です。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForCredit($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_checkErrorShipmentEntryForCredit_受注決済情報の取引情報が与信完了でない受注IDを渡すとメッセージが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クレジットカード決済の受注決済情報作成
        $this->createOrderPaymentDataCredit($Order, $this->const['YAMATO_ACTION_STATUS_WAIT']);

        $expected = '操作に対応していない取引状況です。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForCredit($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_checkErrorShipmentEntryForCredit_受注決済情報に送り状番号が登録されていない受注IDを渡すとメッセージが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クレジットカード決済の受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        // 取引情報の更新
        $YamatoOrderPayment->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_AUTH']);

        $expected = '送り状番号が登録されていない配送先が存在します。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForCredit($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_checkErrorShipmentEntryForCredit_配送先数が上限を超えた受注IDを渡すとメッセージが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クレジットカード決済の受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        // 取引情報の更新
        $YamatoOrderPayment->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_AUTH']);

        // 出荷情報取得
        $Shippings = $Order->getShippings()->getValues();

        // 配送伝票番号情報作成
        $ShippingDelivSlip = new YamatoShippingDelivSlip();
        $ShippingDelivSlip->setId($Shippings[0]['id']);
        $ShippingDelivSlip->setOrderId($orderId);
        $ShippingDelivSlip->setDelivSlipNumber('12345678903');
        $this->app['orm.em']->persist($ShippingDelivSlip);

        for ($i = 0; $i < $this->const['YAMATO_DELIV_ADDR_MAX']; $i++) {
            //出荷情報を追加
            $Order->addShipping($Shippings[0]);
            $this->app['orm.em']->persist($Order);

            // 配送先追加用配送伝票番号情報作成
            $ShippingDelivSlip2 = new YamatoShippingDelivSlip();
            $ShippingDelivSlip2->setId($Shippings[0]['id'] + ($i + 1));
            $ShippingDelivSlip2->setOrderId($orderId);
            $ShippingDelivSlip2->setDelivSlipNumber(sha1(Str::random(32)));
            $this->app['orm.em']->persist($ShippingDelivSlip2);
        }
        $this->app['orm.em']->flush();

        $expected = '1つの注文に対する出荷情報の上限（' . $this->const['YAMATO_DELIV_ADDR_MAX'] . '件）を超えております。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForCredit($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_checkErrorShipmentEntryForCredit_共通送り状番号での注文同梱数が上限を超えた受注IDを渡すとメッセージが返る()
    {
        $orderId = null;
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クレジットカード決済の受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        $YamatoOrderPayment->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_AUTH']);

        // 出荷情報取得
        $Shippings = $Order->getShippings()->getValues();

        // 配送伝票番号情報作成
        $ShippingDelivSlip = new YamatoShippingDelivSlip();
        $ShippingDelivSlip->setId($Shippings[0]['id']);
        $ShippingDelivSlip->setOrderId($orderId);
        $ShippingDelivSlip->setDelivSlipNumber('12345678901');
        $this->app['orm.em']->persist($ShippingDelivSlip);
        $this->app['orm.em']->flush();

        for ($i = 0; $i < $this->const['YAMATO_SHIPPED_MAX']; $i++) {
            // 受注情報作成
            $Order2 = $this->createOrderData();
            $orderId2 = $Order2->getId();

            // クレジットカード決済の受注決済情報作成
            $YamatoOrderPayment2 = $this->createOrderPaymentDataCredit($Order2);
            $YamatoOrderPayment2->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_AUTH']);

            // 出荷情報取得
            $Shippings2 = $Shippings;

            // 配送伝票番号情報作成
            $ShippingDelivSlip = new YamatoShippingDelivSlip();
            $ShippingDelivSlip->setId($Shippings2[0]['id'] + ($i + 1));
            $ShippingDelivSlip->setOrderId($orderId2);
            $ShippingDelivSlip->setDelivSlipNumber('12345678901');
            $this->app['orm.em']->persist($ShippingDelivSlip);
            $this->app['orm.em']->flush();
        }

        $expected = '同一の送り状番号で同梱可能な注文数（' . $this->const['YAMATO_SHIPPED_MAX'] . '件）を超えております。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForCredit($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_checkErrorShipmentEntryForCredit_共通送り状番号で配送先が異なる受注IDを渡すとメッセージが返る()
    {
        $orderId = null;
        for ($i = 1; $i < $this->const['YAMATO_SHIPPED_MAX']; $i++) {
            // 受注情報作成
            $Order = $this->createOrderData();
            $orderId = $Order->getId();

            // クレジットカード決済の受注決済情報作成
            $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
            $YamatoOrderPayment->setMemo04($this->const['YAMATO_ACTION_STATUS_COMP_AUTH']);

            // 出荷情報取得
            $Shippings = $Order->getShippings()->getValues();

            // 配送伝票番号情報作成
            $ShippingDelivSlip = new YamatoShippingDelivSlip();
            $ShippingDelivSlip->setId($Shippings[0]['id']);
            $ShippingDelivSlip->setOrderId($orderId);
            $ShippingDelivSlip->setDelivSlipNumber('12345678904');
            $this->app['orm.em']->persist($ShippingDelivSlip);
            $this->app['orm.em']->flush();
        }

        $expected = '同一の送り状番号で配送先が異なるものが存在しています。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForCredit($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_checkErrorShipmentEntryForDeferred_エラーがない場合_空白が返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クロネコ代金後払い決済の受注決済情報作成
        $this->createOrderPaymentDataDeferred($Order);

        // 出荷情報取得
        $Shippings = $Order->getShippings()->getValues();

        // 配送伝票番号情報作成
        $ShippingDelivSlip = new YamatoShippingDelivSlip();
        $ShippingDelivSlip->setId($Shippings[0]['id']);
        $ShippingDelivSlip->setOrderId($orderId);
        $ShippingDelivSlip->setDelivSlipNumber('12345678903');
        $this->app['orm.em']->persist($ShippingDelivSlip);
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForDeferred($orderId);

        // 空白が返ること
        $this->assertEmpty($actual);
    }

    function test_checkErrorShipmentEntryForDeferred_エラーがない場合_空白が返る_複数配送()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クロネコ代金後払い決済の受注決済情報作成
        $this->createOrderPaymentDataDeferred($Order);

        // 出荷情報取得
        $Shippings = $Order->getShippings()->getValues();

        // 配送伝票番号情報作成
        $ShippingDelivSlip = new YamatoShippingDelivSlip();
        $ShippingDelivSlip->setId($Shippings[0]['id']);
        $ShippingDelivSlip->setOrderId($orderId);
        $ShippingDelivSlip->setDelivSlipNumber('12345678903');

        for ($i = 0; $i < 1; $i++) {
            // 配送先追加用受注情報作成
            $Order2 = $this->createOrderData();

            // 配送先追加用出荷情報取得
            $Shippings2 = $Order2->getShippings()->getValues();

            // 受注情報に配送先追加用出荷情報を追加
            $Order->addShipping($Shippings2[0]);

            // 配送先追加用配送伝票番号情報作成
            $ShippingDelivSlip2 = new YamatoShippingDelivSlip();
            $ShippingDelivSlip2->setId($Shippings2[0]['id']);
            $ShippingDelivSlip2->setOrderId($orderId);
            $ShippingDelivSlip2->setDelivSlipNumber('12345678904');
            $this->app['orm.em']->persist($ShippingDelivSlip2);
        }
        $this->app['orm.em']->persist($Order);
        $this->app['orm.em']->persist($ShippingDelivSlip);
        $this->app['orm.em']->flush();

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForDeferred($orderId);

        // 空白が返ること
        $this->assertEmpty($actual);
    }

    function test_checkErrorShipmentEntryForDeferred_後払い決済でない受注IDを渡すとメッセージが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クロネコ代金後払い決済の受注決済情報作成
        $this->createOrderPaymentDataCvs($Order);

        $expected = '「出荷情報登録」に対応していない決済です。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForDeferred($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_checkErrorShipmentEntryForDeferred_受注決済情報に送り状番号が登録されていない受注IDを渡すとメッセージが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クロネコ代金後払い決済の受注決済情報作成
        $this->createOrderPaymentDataDeferred($Order);

        $expected = '送り状番号が登録されていない配送先が存在します。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForDeferred($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_checkErrorShipmentEntryForDeferred_受注決済情報のクロネコ代金後払い用審査結果がご利用可でない受注IDを渡すとメッセージが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クロネコ代金後払い決済の受注決済情報作成 審査結果：ご利用不可
        $this->createOrderPaymentDataDeferred($Order, null, $this->const['DEFERRED_NOT_AVAILABLE']);

        // 出荷情報取得
        $Shippings = $Order->getShippings()->getValues();

        // 配送伝票番号情報作成
        $ShippingDelivSlip = new YamatoShippingDelivSlip();
        $ShippingDelivSlip->setId($Shippings[0]['id']);
        $ShippingDelivSlip->setOrderId($orderId);
        $ShippingDelivSlip->setDelivSlipNumber('12345678903');
        $this->app['orm.em']->persist($ShippingDelivSlip);
        $this->app['orm.em']->flush();

        $expected = '「出荷情報登録」に対応していない審査結果です。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForDeferred($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_checkErrorShipmentEntryForDeferred_配送先数が上限を超えた受注IDを渡すとメッセージが返る()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $orderId = $Order->getId();

        // クロネコ代金後払い決済の受注決済情報作成
        $this->createOrderPaymentDataDeferred($Order);

        // 出荷情報取得
        $Shippings = $Order->getShippings()->getValues();

        // 配送伝票番号情報作成
        $ShippingDelivSlip = new YamatoShippingDelivSlip();
        $ShippingDelivSlip->setId($Shippings[0]['id']);
        $ShippingDelivSlip->setOrderId($orderId);
        $ShippingDelivSlip->setDelivSlipNumber('12345678903');
        $this->app['orm.em']->persist($ShippingDelivSlip);

        for ($i = 0; $i < $this->const['DEFERRED_DELIV_ADDR_MAX']; $i++) {
            $Order->addShipping($Shippings[0]);
            $this->app['orm.em']->persist($Order);

            // 配送先追加用配送伝票番号情報作成
            $ShippingDelivSlip2 = new YamatoShippingDelivSlip();
            $ShippingDelivSlip2->setId($Shippings[0]['id'] + ($i + 1));
            $ShippingDelivSlip2->setOrderId($orderId);
            $ShippingDelivSlip2->setDelivSlipNumber(sha1(Str::random(32)));
            $this->app['orm.em']->persist($ShippingDelivSlip2);
        }
        $this->app['orm.em']->flush();

        $expected = '1つの注文に対するお届け先の上限（' . $this->const['DEFERRED_DELIV_ADDR_MAX'] . '件）を超えております。';

        // テスト対象メソッド実行
        $actual = $this->object->checkErrorShipmentEntryForDeferred($orderId);

        // 対応したメッセージが返ること
        $this->assertEquals($expected, $actual);
    }

    function test_isCreditOrder()
    {
        /*
         * クレジット決済の場合、trueが返る
         */
        // 受注情報作成
        $Order = $this->createOrderData();

        // クレジット決済の受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);

        // テスト対象メソッド実行
        // trueが返ること
        $this->asserttrue($this->object->isCreditOrder($YamatoOrderPayment));

        /*
         * クレジット決済以外の場合、falseが返る
         */
        // 受注情報作成
        $Order = $this->createOrderData();

        // クレジット決済でないの受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCvs($Order);

        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isCreditOrder($YamatoOrderPayment));
    }

    function test_isDeferredOrder()
    {
        /*
         * 後払い決済の場合、trueが返る
         */
        // 受注情報作成
        $Order = $this->createOrderData();

        // 後払い決済の受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order);

        // テスト対象メソッド実行
        // trueが返ること
        $this->asserttrue($this->object->isDeferredOrder($YamatoOrderPayment));

        /*
         * 後払い決済以外の場合、falseが返る
         */
        // 受注情報作成
        $Order = $this->createOrderData();

        // 後払い決済でないの受注決済情報作成
        $YamatoOrderPayment = $this->createOrderPaymentDataCvs($Order);

        // テスト対象メソッド実行
        // falseが返ること
        $this->assertFalse($this->object->isDeferredOrder($YamatoOrderPayment));
    }

    function testDoDeleteCard__予約販売利用無し__削除後にまだカード情報が存在しない__trueが返ること__テンプレート変数にセットされないこと__登録パラメータを維持すること()
    {
        // 受注情報を取得
        $Order = $this->createOrder($this->createCustomer());
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->createOrderPaymentDataCredit($Order);
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
        $YamatoOrderPayment->setMemo04($this->const['YAMATO_ACTION_STATUS_WAIT']);
        $this->app['orm.em']->flush();

        // フォームデータ作成
        $listParam = $this->createFormDataCredit($Order);

        // クレジットカードのお預かり処理（MemberClientService）モック作成
        // 預かりカード一件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results, null, true);

        // trueが返ること
        $this->assertTrue($this->object->doDeleteCard($Order->getCustomer()->getId(), $listParam, $this));
    }

    function testDoDeleteCard__予約販売利用無し__削除後にまだカード情報が存在する__trueが返ること__テンプレート変数にセットされること__登録パラメータを維持すること()
    {
        // 受注情報を取得
        $Order = $this->createOrder($this->createCustomer());
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->createOrderPaymentDataCredit($Order);
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
        $YamatoOrderPayment->setMemo04($this->const['YAMATO_ACTION_STATUS_WAIT']);
        $this->app['orm.em']->flush();

        // フォームデータ作成
        $listParam = $this->createFormDataCredit($Order);

        // クレジットカードのお預かり処理（MemberClientService）モック作成
        // 預かりカード一件
        $results = $this->createCardData();
        $results['cardData']['lastCreditDate'] = date("Y/m/d");

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results, $results, true);

        // trueが返ること
        $this->assertTrue($this->object->doDeleteCard($Order->getCustomer()->getId(), $listParam, $this));
    }

    function testDoDeleteCard__お預かり情報照会にエラーがある場合__falseが返ること_エラーメッセージが返ること()
    {
        // 受注情報を取得
        $Order = $this->createOrder($this->createCustomer());
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // フォームデータ作成
        $listParam = $this->createFormDataCredit($Order);

        // クレジットカードのお預かり処理（MemberClientService）モック作成
        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(false);

        // falseが返ること
        $this->assertFalse($this->object->doDeleteCard($Order->getCustomer()->getId(), $listParam, $this));

        // エラーメッセージが返ること
        $this->assertRegExp('/お預かり照会でエラーが発生しました/u', $this->error['payment']);
    }

    function testDoDeleteCard__予約販売利用有り__falseが返ること_エラーメッセージが返ること()
    {
        // 受注情報を取得
        $Order = $this->createOrder($this->createCustomer());
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->app['orm.em']->flush();

        // フォームデータ作成
        $listParam = $this->createFormDataCredit($Order);

        // クレジットカードのお預かり処理（MemberClientService）モック作成
        // 預かりカード一件
        $results = $this->createCardData();
        $results['cardData']['subscriptionFlg'] = 1;

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results, null, true);

        // falseが返ること
        $this->assertFalse($this->object->doDeleteCard($Order->getCustomer()->getId(), $listParam, $this));

        // エラーメッセージが返ること
        $this->assertRegExp('/予約販売利用有りのカード情報は削除できません/u', $this->error['payment']);
    }

    function testDoDeleteCard__お預かり情報削除でエラーが発生した場合__falseが返ること__エラーメッセージが返ること()
    {
        // 受注情報を取得
        $Order = $this->createOrder($this->createCustomer());
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        $this->createOrderPaymentDataCredit($Order);
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
        $YamatoOrderPayment->setMemo04($this->const['YAMATO_ACTION_STATUS_WAIT']);
        $this->app['orm.em']->flush();

        // フォームデータ作成
        $listParam = $this->createFormDataCredit($Order);

        // クレジットカードのお預かり処理（MemberClientService）モック作成
        // 預かりカード一件
        $results = $this->createCardData();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results, null, false);

        // falseが返ること
        $this->assertFalse($this->object->doDeleteCard($Order->getCustomer()->getId(), $listParam, $this));

        // エラーメッセージが返ること
        $this->assertRegExp('/お預かり情報削除でエラーが発生しました/u', $this->error['payment']);
    }

    function testGetArrCardInfo()
    {
        /*
         * 預かりカード一件
         */
        $results = $this->createCardData();

        $actual = $this->object->getArrCardInfo($results);

        // 配列の構造が異なったカード情報が返ること
        $this->assertNotEquals($results, $actual);

        /*
         * 預かりカード二件
         */
        $results['cardData'] = array(
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
        $results['cardUnit'] = 2;

        $actual = $this->object->getArrCardInfo($results);

        // カード情報がそのまま返ること
        $this->assertEquals($results, $actual);
    }

    function testDoDeleteCard__削除対象のカード情報が存在しない__falseが返ること__エラーメッセージが返ること()
    {
        // クレジットカードのお預かり処理（MemberClientService）モック作成
        // 預かりカード一件
        $results['cardData'] = array();

        // MemberClientServiceをモック化
        $this->app['yamato_payment.service.client.member'] = $this->createMemberClientService(true, $results, null, true);

        // falseが返ること
        $this->assertFalse($this->object->doDeleteCard(0, array(), $this));

        $this->assertContains('※ 削除するカードを選択してください。', $this->error['payment']);
    }

    function testGetSendDiv__送り先が複数の場合1が返る()
    {
        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer('dummy-user@example.com'));

        // 出荷情報を作成
        for ($i = 0; $i < 1; $i++) {
            $Shipping = new Shipping();
            // 受注情報に出荷情報を追加
            $Order->addShipping($Shipping);
        }

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        // 1が返ること
        $this->assertEquals(1, $this->object->getSendDiv($Order, $Shippings));
    }

    function testGetSendDiv__単一配送で購入者情報と送り先情報が異なる場合1を返る()
    {
        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer('dummy-user@example.com'));

        // 受注情報（購入者）の住所を変更
        $addr01 = $Order->getAddr01();
        $addr01 = $addr01 . 'A';
        $Order->setAddr01($addr01);

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        // 1が返ること
        $this->assertEquals(1, $this->object->getSendDiv($Order, $Shippings));
    }

    function testGetSendDiv__単一配送でプラグイン設定の請求書の同梱が1同梱するの場合2を返す()
    {
        // プラグイン設定の請求書の同梱を1に設定する
        $subData = array(
            'ycf_ship_ymd' => 00,
            'ycf_send_div' => 1,
        );
        $this->app['yamato_payment.util.plugin']->registerUserSettings($subData);

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer('dummy-user@example.com'));

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        // 1が返ること
        $this->assertEquals(2, $this->object->getSendDiv($Order, $Shippings));
    }

    function testGetSendDiv_単一配送でプラグイン設定の請求書の同梱が0の同梱しないになっている場合0を返す()
    {
        // プラグイン設定の請求書の同梱を0に設定する
        $subData = array(
            'ycf_ship_ymd' => 00,
            'ycf_send_div' => 0,
        );
        $this->app['yamato_payment.util.plugin']->registerUserSettings($subData);

        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer('dummy-user@example.com'));

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        // 0が返ること
        $this->assertEquals(0, $this->object->getSendDiv($Order, $Shippings));
    }

    function testGetOrderDetailDeferred__明細が10行以下の場合__10行以下の配列が返る__商品1点__3行()
    {
        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer('dummy-user@example.com'));

        $actual = $this->object->getOrderDetailDeferred($Order);

        // 注文明細情報が返ること
        $this->assertEquals(3, count($actual));
    }

    function testGetOrderDetailDeferred__明細が10行以上の場合__10行の配列が返る()
    {
        // 受注情報作成
        $Order = $this->createOrder($this->createCustomer());
        for($i=0;$i<10;$i++) {
            $addOrder = $this->createOrder($this->createCustomer());
            $addOrderDetails = $addOrder->getOrderDetails();
            foreach($addOrderDetails as $addOrderDetail) {
                $Order->addOrderDetail($addOrderDetail);
            }
        }
        $this->app['orm.em']->flush();

        $actual = $this->object->getOrderDetailDeferred($Order);

        // 注文明細情報が返ること
        $this->assertEquals(10, count($actual));
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

    protected function createCardData()
    {
        $results = array();
        $results['cardData'] = array(
            'cardKey' => '1',
            'maskingCardNo' => '************1111',
            'cardExp' => '0528',
            'cardOwner' => 'KURONEKO YAMATO',
            'subscriptionFlg' => '0',
        );
        $results['cardUnit'] = 1;

        return $results;
    }

    private function createMemberClientService($doGetCard,  $results = null, $results2 = null, $bool = false)
    {
        $mock = $this->getMock('MemberClientService', array('doGetCard', 'getResults', 'getError', 'doRegistCard', 'doDeleteCard'));
        $mock->expects($this->any())
            ->method('doGetCard')
            ->will($this->returnValue($doGetCard));
        $mock->expects($this->any())
            ->method('getResults')
            ->will($this->onConsecutiveCalls($results, $results2));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));
        $mock->expects($this->any())
            ->method('doRegistCard')
            ->will($this->returnValue($bool));
        $mock->expects($this->any())
            ->method('doDeleteCard')
            ->will($this->returnValue($bool));

        return $mock;
    }

    function testCheckCart_予約商品と通常商品が混在する場合_falseが返る()
    {
        // 複数配送を有効にする
        $this->setMultipleShipping(1);

        // カートイン 通常商品＋予約商品
        $this->scenarioReserveItemCartIn(1, 1);
        $this->scenarioReserveItemCartIn($this->app['config']['YamatoPayment']['const']['PRODUCT_TYPE_ID_RESERVE'], 2);

        $actual = $this->object->checkCartProductType();

        //falseが返ること
        $this->assertFalse($actual);
    }

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
