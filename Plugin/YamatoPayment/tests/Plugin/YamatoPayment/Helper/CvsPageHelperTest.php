<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Helper;

use Eccube\Application;
use Eccube\Entity\Order;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;

class CvsPageHelperTest extends AbstractHelperTestCase
{
    var $error;

    /** @var CvsPageHelper */
    var $object;

    protected $const;

    /** @var Order */
    var $Order;

    public function setUp()
    {
        parent::setUp();
        $this->const = $this->app['config']['YamatoPayment']['const'];
        $this->object = new CvsPageHelper($this->app);

        // 受注情報を取得
        $this->Order = $this->createOrder($this->createCustomer());
        $this->createOrderPaymentDataCvs($this->Order);
    }

    function testModeAction_modeはnext_isCompleteはtrueとなること_注文状況が入金待ちになること_決済状況が決済依頼済みになること()
    {
        /*
         * CvsClientServiceモック化
         */
        $this->app['yamato_payment.service.client.cvs'] = $this->createCvsClientService(true);

        // フォームデータ作成
        $listParam = array();

        // 支払方法の設定情報作成
        $paymentExtension = $this->app['yamato_payment.util.payment']->getPaymentTypeConfig($this->Order->getPayment()->getId());

        // isCompleteがfalseなことを確認
        $this->assertFalse($this->object->isComplete);

        // 注文状況が「入金待ち」でないことを確認
        $this->assertNotEquals($this->app['config']['order_pay_wait'], $this->Order->getOrderStatus()->getId());

        // 決済状況が「決済依頼済み」でないことを確認
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_SEND_REQUEST'], $YamatoOrderPayment->getMemo04());

        $this->object->modeAction($listParam, $this->Order, $paymentExtension, $this);

        // isCompleteはtrueとなること
        $this->assertTrue($this->object->isComplete);

        // 注文状況が「入金待ち」なこと
        $Order = $this->app['eccube.repository.order']->find($this->Order->getId());
        $this->assertEquals($this->app['config']['order_pay_wait'], $Order->getOrderStatus()->getId());

        // 決済状況が「決済依頼済み」なこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($Order->getId());
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_SEND_REQUEST'], $YamatoOrderPayment->getMemo04());
    }

    function testModeAction_modeはnext_決済でエラーが発生した場合_決済状況が決済中断になること_エラーメッセージが返ること()
    {
        /*
         * CvsClientServiceモック化
         */
        $this->app['yamato_payment.service.client.cvs'] = $this->createCvsClientService(false);

        // フォームデータ作成
        $listParam = array();

        // 支払方法の設定情報作成
        $paymentExtension = $this->app['yamato_payment.util.payment']->getPaymentTypeConfig($this->Order->getPayment()->getId());

        // 決済状況が「決済中断」でないことを確認
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertNotEquals($this->const['YAMATO_ACTION_STATUS_NG_TRANSACTION'], $YamatoOrderPayment->getMemo04());

        $this->object->modeAction($listParam, $this->Order, $paymentExtension , $this);

        // 決済状況が「決済中断」なこと
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($this->Order->getId());
        $this->assertEquals($this->const['YAMATO_ACTION_STATUS_NG_TRANSACTION'], $YamatoOrderPayment->getMemo04());

        $this->assertRegExp('/決済でエラーが発生しました/u', $this->error['payment']);
    }

    private function createCvsClientService($doPaymentRequest = false)
    {
        $mock = $this->getMock('CreditClientService', array('doPaymentRequest', 'getError'));
        $mock->expects($this->any())
            ->method('doPaymentRequest')
            ->will($this->returnValue($doPaymentRequest));
        $mock->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(array('エラーメッセージ')));

        return $mock;
    }
}
