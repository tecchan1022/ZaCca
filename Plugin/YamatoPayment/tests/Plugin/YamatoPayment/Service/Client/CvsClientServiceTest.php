<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Service\Client;

use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Service\AbstractServiceTestCase;
use Plugin\YamatoPayment\Util\PaymentUtil;

class CvsClientServiceTest extends AbstractServiceTestCase
{
    /** @var YamatoPaymentMethod $YamatoPaymentMethod */
    var $YamatoPaymentMethod;
    /** @var  PaymentUtil */
    var $PaymentUtil;

    /**
     * @var CvsClientService
     */
    protected $object;

    function setUp()
    {
        parent::setUp();
        $this->object = $this->app['yamato_payment.service.client.cvs'];
        $this->PaymentUtil = $this->app['yamato_payment.util.payment'];
        $this->YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']));
    }

    function test_doPaymentRequest__trueが返ること__受注支払情報が更新されていること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        // 決済状況：未決済
        $YamatoOrderPayment = $this->createOrderPaymentDataCvs($Order);
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
            // 受注情報から決済ログ取得
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // パラメータ情報作成
        $listParam = array(
            'cvs' => $this->const['CONVENI_ID_SEVENELEVEN']
        );

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理: コンビニ決済（CvsClientService/BaseClientService）モック作成
         */
        $getResults = array(
            'returnDate' => date('YmdHms'),
            'billingNo' => 1 . date('ymdHms'),
            'billingUrl' => 'html://seven_eleven_url',
            'expiredDate' => date('YmdHms', strtotime('+15day')),
        );
        // CvsClientService（BaseClientService）モック化
        $this->object = $this->createCvsClientService(true, $getResults);

        /*
         * コンビニ決済実行
         */
        // Trueが返ること
        $this->assertTrue($this->object->doPaymentRequest($Order, $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済完了ページ表示用データが更新されていること
        $this->assertNotEquals($expected['memo02'], $newYamatoOrderPayment->getMemo02());
        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());
    }

    function test_doPaymentRequest__falseが返ること__エラーメッセージが返ること__受注支払情報が更新されていること()
    {
        // 受注情報作成
        $Order = $this->createOrderData();
        $payment_id = $this->YamatoPaymentMethod->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $Order->setPayment($Payment);
        // 決済状況：未決済
        $YamatoOrderPayment = $this->createOrderPaymentDataCvs($Order);
        $OrderExtension = $this->PaymentUtil->getOrderPayData($Order->getId());
        $OrderExtension->setOrderId($Order->getId());
        $this->app['orm.em']->flush();

        $expected = array(
            // 受注情報から決済状況取得
            'memo04' => $YamatoOrderPayment->getMemo04(),
            // 受注情報から決済データ取得
            'memo05' => $YamatoOrderPayment->getMemo05(),
            // 受注情報から決済ログ取得
            'memo09' => $YamatoOrderPayment->getMemo09(),
        );

        // パラメータ情報作成
        $listParam = array(
            'cvs' => $this->const['CONVENI_ID_SEVENELEVEN']
        );

        // 支払方法の設定情報を取得する
        $PaymentExtension = $this->PaymentUtil->getPaymentTypeConfig($Order->getPayment()->getId());

        /*
         * 決済モジュール 決済処理: コンビニ決済（CvsClientService/BaseClientService）モック作成
         */
        // CvsClientService（BaseClientService）モック化
        $this->object = $this->createCvsClientService(false);
        $this->object->error = array('エラーメッセージ');

        /*
         * コンビニ決済実行
         */
        // Falseが返ること
        $this->assertFalse($this->object->doPaymentRequest($Order, $listParam, $PaymentExtension));

        /** @var YamatoOrderPayment $newYamatoOrderPayment */
        $newYamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());

        // 決済状況が更新されていること
        $this->assertNotEquals($expected['memo04'], $newYamatoOrderPayment->getMemo04());
        // 決済データが更新されていること
        $this->assertNotEquals($expected['memo05'], $newYamatoOrderPayment->getMemo05());
        // 決済ログが更新されていること
        $this->assertNotEquals($expected['memo09'], $newYamatoOrderPayment->getMemo09());

        // エラーメッセージが返ること
        $this->assertContains('エラーメッセージ', $this->object->error);
    }

    private function createCvsClientService($sendOrderRequest = null, $getResults = null)
    {
        $mock = $this->getMock('Plugin\YamatoPayment\Service\Client\CvsClientService', array('sendRequest', 'getResults', 'getError'), array($this->app));
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
