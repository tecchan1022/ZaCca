<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Entity\Order;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Service\AbstractServiceTestCase;
use Plugin\YamatoPayment\Util\PaymentUtil;
use Plugin\YamatoPayment\Util\PluginUtil;

class DeferredClientServiceTest extends AbstractServiceTestCase
{
    /** @var  PluginUtil */
    var $PluginUtil;
    /** @var  PaymentUtil */
    var $PaymentUtil;
    /** @var YamatoPaymentMethod $YamatoPaymentMethod */
    var $YamatoPaymentMethod;

    /**
     * @var DeferredClientService
     */
    protected $object;

    function setUp()
    {
        parent::setUp();
        $this->object = $this->app['yamato_payment.service.client.deferred'];

        $this->PluginUtil = $this->app['yamato_payment.util.plugin'];
        $this->PaymentUtil = $this->app['yamato_payment.util.payment'];
        $this->YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']));

    }

    /**
     * クロネコ代金後払い用のフォームの生成
     *
     * @param Order $order 受注情報
     * @return array フォーム情報
     */
    private function createDeferredFormData($order)
    {
        // ユーザー設定の取得
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();

        // 出荷情報を取得
        $Shippings = $order->getShippings();

        $formData_base = array(
            // 基本情報エリア
            'ycfStrCode' => $userSettings['ycf_str_code'],
            'orderNo' => $order->getId(),
            'orderYmd' => date_format($order->getCreateDate(), 'ymd'),
            'shipYmd' => date('ymd', strtotime('+' . $userSettings['ycf_ship_ymd'] . 'day', $order->getCreateDate()->getTimestamp())),
            'Name' => $order->getName01() . '　' . $order->getName02(),
            'nameKana' => $order->getKana01() . '　' . $order->getKana02(),
            'postCode' => $order->getZip01() . $order->getZip02(),
            'address1' => $order->getPref() . $order->getAddr01(),
            'address2' => $order->getAddr02(),
            'telNum' => $order->getTel01() . $order->getTel02() . $order->getTel03(),
            'email' => $order->getEmail(),
            'totalAmount' => $order->getPaymentTotal(),
            'sendDiv' => 0,

            // 共通情報エリア
            'requestDate' => date('YmdHis'),
            'password' => $userSettings['ycf_str_password'],
        );

        // 商品情報を取得
        $Products = $this->PaymentUtil->getOrderDetailDeferred($order);

        // 商品購入エリア
        $formData_product = array();
        foreach($Products as $key => $val) {
            $seq = $key + 1;
            $formData_product = array_merge($formData_product, array(
                'itemName' . $seq => $val['itemName'],
                'itemCount' . $seq => $val['itemCount'],
                'unitPrice' . $seq => $val['unitPrice'],
                'subTotal' . $seq => $val['subTotal'],
            ));
        }

        $formData_shipping =array();
        // 送り先情報エリア
        foreach($Shippings as $key => $val) {
            $seq = $key + 1;
            if ($seq == 1) {
                // 1件目の項目に数字を振らない
                $seq = '';
            }
            $formData_shipping = array_merge($formData_shipping, array(
                'sendName' . $seq => $val['name01'] . '　' . $val['name02'],
                'sendPostCode' . $seq => $val['zip01'] . $val['zip02'],
                'sendAddress1' . $seq => $val['Pref']['name'] . $val['addr01'],
                'sendAddress2' . $seq => $val['addr02'],
                'sendTelNum' . $seq => $val['tel01'] . $val['tel02'] . $val['tel03'],
            ));
        }

        $formData = array_merge($formData_base, $formData_product, $formData_shipping);

        return $formData;
    }

    function test_doPaymentRequest__trueが返ること__受注支払情報が更新されていること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        // 決済状況：決済手続き中　審査結果：審査中
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

        $expected = array(
            // 受注情報から決済完了ページ表示用データを取得
            'memo02' => $YamatoOrderPayment->getMemo02(),
            // 受注情報から決済状況取得
            'memo04' => $YamatoOrderPayment->getMemo04(),
            // 受注情報から決済データ取得
            'memo05' => $YamatoOrderPayment->getMemo05(),
            // 受注情報から審査結果を取得
            'memo06' => $YamatoOrderPayment->getMemo06(),
            // 受注情報から決済ログ取得
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // パラメータ情報作成
        $listParam = $this->createDeferredFormData($Order);

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理: 後払い（DeferredClientService/BaseClientService）モック作成
         */
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $getResults = array(
            'ycfStrCode' => $userSettings['ycf_str_code'],
            'result' => 0,
            'result_code' => null,
            'action_status' => null,
            'returnDate' => date('YmdHms'),
        );
        // DeferredClientService（BaseClientService）モック化
        $this->object = $this->createDeferredClientService(true, $getResults);

        /*
         * クロネコ代金後払い決済実行
         */
        // Trueが返ること
        $this->assertTrue($this->object->doPaymentRequest($OrderExtension, $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済完了ページ表示用データが更新されていること
        $this->assertNotEquals($expected['memo02'], $newYamatoOrderPayment->getMemo02());
        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 審査結果が更新されていること
        $this->assertNotEquals($expected['memo06'], $newYamatoOrderPayment->getMemo06());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());
    }

    function test_doPaymentRequest__審査結果がご利用ご利用不可__falseが返ること__受注支払情報が更新されていること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        // 決済状況：決済手続き中　審査結果：審査中
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

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

        // パラメータ情報作成
        $listParam = $this->createDeferredFormData($Order);

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理: 後払い（DeferredClientService/BaseClientService）モック作成
         */
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $getResults = array(
            'ycfStrCode' => $userSettings['ycf_str_code'],
            'result' => 1,
            'result_code' => null,
            'action_status' => null,
            'returnDate' => date('YmdHms'),
            'orderNo' => $Order->getId(),
        );
        // DeferredClientService（BaseClientService）モック化
        $this->object = $this->createDeferredClientService(true, $getResults);

        /*
         * クロネコ代金後払い決済実行
         */
        // Falseが返ること
        $this->assertFalse($this->object->doPaymentRequest($OrderExtension, $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況は変更ないこと
        $this->assertEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 審査結果が更新されていること
        $this->assertNotEquals($expected['memo06'], $newYamatoOrderPayment->getMemo06());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());
    }

    function test_doPaymentRequest__falseが返ること__エラーメッセージが返ること__決済状況と審査結果に変更がないこと()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        // 決済状況：決済手続き中　審査結果：審査中
        $YamatoOrderPayment = $this->createOrderPaymentDataDeferred($Order, $this->const['YAMATO_ACTION_STATUS_WAIT'], $this->const['DEFERRED_UNDER_EXAM']);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

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

        // パラメータ情報作成
        $listParam = $this->createDeferredFormData($Order);

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理: 後払い（DeferredClientService/BaseClientService）モック作成
         */
        // DeferredClientService（BaseClientService）モック化
        $this->object = $this->createDeferredClientService(false);
        $this->object->error = array('エラーメッセージ');

        /*
         * クロネコ代金後払い決済実行
         */
        // Falseが返ること
        $this->assertFalse($this->object->doPaymentRequest($OrderExtension, $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況は変更ないこと
        $this->assertEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 審査結果は変更ないこと
        $this->assertEquals($expected['memo06'], $newYamatoOrderPayment->getMemo06());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());

        // エラーメッセージが返ること
        $this->assertContains('エラーメッセージ', $this->object->error);
    }

    private function createDeferredClientService($sendOrderRequest = null, $getResults = null)
    {
        $mock = $this->getMock('Plugin\YamatoPayment\Service\Client\DeferredClientService', array('sendRequest', 'getResults', 'getError'), array($this->app));
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
}
