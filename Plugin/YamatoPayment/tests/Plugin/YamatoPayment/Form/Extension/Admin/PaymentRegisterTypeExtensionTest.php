<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;

use Plugin\YamatoPayment\Form\Extension\AbstractTypeExtensionTestCase;

class PaymentRegisterTypeExtensionTest extends AbstractTypeExtensionTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var  \Symfony\Component\Form\FormEvent */
    protected $event;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'charge_flg' => '1',
        'method' => '1',
        'charge'=> '10000',
        'rule_min' => '100',
        'rule_max' => '10000',
    );


    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // 送り状番号登録・編集
        $this->form = $this->app['form.factory']
            ->createBuilder('payment_register', null, array(
                'csrf_protection' => false,
            ))
            ->getForm();
    }

    public function testValidData_Credit()
    {
        // 支払方法設定
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->form->setData($Payment);

        $this->formData['charge_flg'] = '0';
        unset($this->formData['charge']);
        $this->formData['pay_way'] = array(0);
        $this->formData['TdFlag'] = array(1);

        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidData_CVS()
    {
        // 支払方法設定
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->form->setData($Payment);

        $this->formData['conveni'] = array($this->const['CONVENI_ID_SEVENELEVEN']);

        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidData_Deferred()
    {
        // 支払方法設定
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_DEFERRED']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->form->setData($Payment);

        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidData_Other()
    {
        // 支払方法設定
        $Payment = $this->app['eccube.repository.payment']->find(1);
        $this->form->setData($Payment);

        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidData_New()
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalid_Blank_Credit()
    {
        // 支払方法設定
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->form->setData($Payment);

        $this->formData['pay_way'] = array();
        $this->formData['TdFlag'] = array();

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
        $this->assertNotEmpty($this->form->get('pay_way')->getErrors());
        $this->assertNotEmpty($this->form->get('TdFlag')->getErrors());
    }

    public function testInvalid_Blank_CVS()
    {
        // 支払方法設定
        $payment_id = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CVS']))
            ->getId();
        $Payment = $this->app['eccube.repository.payment']->find($payment_id);
        $this->form->setData($Payment);

        $this->formData['conveni'] = array();

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
        $this->assertNotEmpty($this->form->get('conveni')->getErrors());
    }
}
