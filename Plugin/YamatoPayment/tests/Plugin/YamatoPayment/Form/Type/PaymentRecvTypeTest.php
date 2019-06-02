<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type;

use Symfony\Component\HttpFoundation\Request;

class PaymentRecvTypeTest extends AbstractTypeTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'settle_price' => '2296',
        'settle_date' => '20160401120000',
        'settle_result' => '1',
        'settle_detail' => '11',
        'settle_method' => '1',
    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // 決済結果受信パラメータ構築
        $this->form = $this->app['form.factory']
            ->createBuilder(new PaymentRecvType($this->app))
            ->getForm();
    }

    public function testValidData()
    {
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);

        $this->formData['trader_code'] = $this->app['yamato_payment.util.plugin']->getUserSettings('shop_id');
        $this->formData['order_no'] = $Order->getId();
        $this->formData['settle_price'] = $Order->getPaymentTotal();

        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInValidOrderId()
    {
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);

        $this->formData['trader_code'] = $this->app['yamato_payment.util.plugin']->getUserSettings('shop_id');
        $this->formData['order_no'] = 1000;
        $this->formData['settle_price'] = $Order->getPaymentTotal();

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInValidYamatoOrderPayment()
    {
        $Order = $this->createOrderData();
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        $memo05 = array('function_div' => null);
        $YamatoOrderPayment->setMemo05($memo05);
        $this->app['orm.em']->persist($YamatoOrderPayment);
        $this->app['orm.em']->flush();

        $this->formData['trader_code'] = $this->app['yamato_payment.util.plugin']->getUserSettings('shop_id');
        $this->formData['order_no'] = $Order->getId();
        $this->formData['settle_price'] = $Order->getPaymentTotal();

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }
    public function testInValidCvs()
    {
        $Order = $this->createOrderData();
        $YamatoOrderPayment = $this->createOrderPaymentDataCredit($Order);
        $memo05 = array(
            'function_div' => 'A01',
            'cvs' => '21'
        );
        $YamatoOrderPayment->setMemo05($memo05);
        $this->app['orm.em']->persist($YamatoOrderPayment);
        $this->app['orm.em']->flush();

        $this->formData['trader_code'] = $this->app['yamato_payment.util.plugin']->getUserSettings('shop_id');
        $this->formData['order_no'] = $Order->getId();
        $this->formData['settle_price'] = $Order->getPaymentTotal();

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalid_Blank()
    {
        $this->app['request'] = new Request();
        $this->formData['trader_code'] = '';
        $this->formData['order_no'] = '';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalid_Equal()
    {
        $str = $this->app['yamato_payment.util.plugin']->getUserSettings('shop_id') . 1;
        $this->formData['trader_code'] = $str;

        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('trader_code')->getErrors());
    }

    public function testInvalidSettlePrice_NotEqual()
    {
        $Order = $this->createOrderData();
        $this->formData['settle_price'] = $Order->getPaymentTotal();
        $str = $this->formData['settle_price'] + 1;

        $this->formData['settle_price'] = $str;
        $this->form->submit($this->formData);

        $this->assertFalse($this->form->isValid());
    }

    public function testInvalid_Length()
    {
        $Order = $this->createOrderData();
        $this->formData['order_no'] = $Order->getId();
        $str = $this->formData['order_no'] . str_repeat(1, $this->app['config']['int_len']);
        $this->formData['order_no'] = $str;

        $str = $this->formData['settle_price'] . str_repeat(1, 7);
        $this->formData['settle_price'] = $str;

        $str = str_repeat(1, 14) . 1;
        $this->formData['settle_date'] = $str;

        $str = str_repeat(1, 1) . 1;
        $this->formData['settle_result'] = $str;

        $str = str_repeat(1, 2) . 1;
        $this->formData['settle_detail'] = $str;

        $str = $this->formData['settle_method'] . str_repeat(1, 2);
        $this->formData['settle_method'] = $str;

        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('order_no')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_price')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_date')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_result')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_detail')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_method')->getErrors());
    }

    public function testInvalid_Regex()
    {
        $str = str_repeat('S', $this->app['config']['int_len']);
        $this->formData['order_no'] = $str;

        $str = str_repeat('S', 7);
        $this->formData['settle_price'] = $str;

        $str = str_repeat('S', 14);
        $this->formData['settle_date'] = $str;

        $str = str_repeat('S', 1);
        $this->formData['settle_result'] = $str;

        $str = str_repeat('S', 2);
        $this->formData['settle_detail'] = $str;

        $str = $this->formData['settle_method'] . str_repeat('S', 2);
        $this->formData['settle_method'] = $str;

        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('order_no')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_price')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_date')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_result')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_detail')->getErrors());
        $this->assertNotEmpty($this->form->get('settle_method')->getErrors());
    }

    public function testInvalidSettleMethod()
    {
        $str = str_repeat(2, 2);

        $this->formData['settle_method'] = $str;
        $this->form->submit($this->formData);

        $this->assertFalse($this->form->isValid());
    }

    public function testInvalid_NotEqual_SettleMethod()
    {
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);

        $this->formData['trader_code'] = $this->app['yamato_payment.util.plugin']->getUserSettings('shop_id');
        $this->formData['order_no'] = $Order->getId();
        $this->formData['settle_price'] = $Order->getPaymentTotal();
        $this->formData['settle_method'] = $this->const['CONVENI_ID_SEVENELEVEN'];

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
        $this->assertContains('支払方法が一致していません', $this->form->getErrors(true)->getChildren()->getMessage());
    }

    public function testInvalid_NotEqual_SettlePrice()
    {
        $Order = $this->createOrderData();
        $this->createOrderPaymentDataCredit($Order);

        $this->formData['trader_code'] = $this->app['yamato_payment.util.plugin']->getUserSettings('shop_id');
        $this->formData['order_no'] = $Order->getId();
        $this->formData['settle_price'] = (int)$Order->getPaymentTotal() + 1;

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
        $this->assertContains('決済金額がECサイトのお支払い合計金額と異なります', $this->form->getErrors(true)->getChildren()->getMessage());
    }

}
