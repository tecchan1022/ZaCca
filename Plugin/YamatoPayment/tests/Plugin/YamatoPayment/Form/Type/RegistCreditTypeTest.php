<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type;

use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;

class RegistCreditTypeTest extends AbstractTypeTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'card_no' => '1111111111111111',
        'card_exp_month' => '11',
        'card_exp_year' => '25',
        'card_owner' => 'TARO YAMADA',
        'security_code' => '111',
        'CardSeq' => null,
        'mode' => '',
        'pay_way' => 1,
        'register_card' => 1,
        'use_registed_card' => false,
        'card_key' => '1',
    );

    public function setUp()
    {
        parent::setUp();

        // クレジットカード支払方法設定
        $payment_method = array(
            'pay_way' => array(1, 2),
            'TdFlag' => 1,
            'order_mail_title' => "お支払いについて",
            'order_mail_body' => "お支払いクレジットカード",
        );
        /** @var YamatoPaymentMethod $YamatoPaymentMethodCredit */
        $YamatoPaymentMethodCredit = $this->app['yamato_payment.repository.yamato_payment_method']
            ->findOneBy(array('memo03' => $this->const['YAMATO_PAYID_CREDIT']));
        $YamatoPaymentMethodCredit->setMemo05($payment_method);
        $this->app['orm.em']->flush();

        $paymentInfo = array();
        $paymentInfo['use_securitycd'] = null;
        $paymentInfo['enable_customer_regist'] = false;
        $payWayList = $this->app['yamato_payment.util.payment']->getCreditPayMethod();
        $paymentInfo['pay_way'] = array_keys($payWayList);

        // CSRF tokenを無効にしてFormを作成
        // カード情報登録・更新
        $creditForm = new RegistCreditType($this->app, $paymentInfo);
        $this->form = $this->app['form.factory']
            ->createBuilder($creditForm, null, array(
                'csrf_protection' => false,
            ))
            ->getForm();
    }

    public function testValidData()
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalid_Blank()
    {
        $this->formData['card_no'] = '';
        $this->formData['card_exp_month'] = '';
        $this->formData['card_exp_year'] = '';
        $this->formData['card_owner'] = '';
        $this->formData['security_code'] = '';
        $this->formData['pay_way'] = null;

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testValid_Blank_カード情報削除モードならエラーにならない()
    {
        $this->formData['mode'] = 'deleteCard';
        $this->formData['card_no'] = '';
        $this->formData['card_exp_month'] = '';
        $this->formData['card_exp_year'] = '';
        $this->formData['card_owner'] = '';
        $this->formData['security_code'] = '';

        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testValid_Blank_クレジット決済で預かりカード利用時にセキュリティコードが空白の場合_エラーが返る()
    {
        $this->formData['card_no'] = '';
        $this->formData['card_exp_month'] = '';
        $this->formData['card_exp_year'] = '';
        $this->formData['card_owner'] = '';
        $this->formData['security_code'] = '';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
        $this->assertNotEmpty($this->form->get('security_code')->getErrors());
    }

    public function testInvalid_MinLength()
    {
        $str = str_repeat(1, 11);
        $this->formData['card_no'] = $str;

        $str = str_repeat(1, 2);
        $this->formData['security_code'] = $str;

        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('card_no')->getErrors());
        $this->assertNotEmpty($this->form->get('security_code')->getErrors());
    }

    public function testInvalid_MaxLength()
    {
        $str = str_repeat(1, 16) . 1;
        $this->formData['card_no'] = $str;

        $str = str_repeat(1, 2) . 1;
        $this->formData['card_exp_month'] = $str;

        $str = str_repeat(2, 2) . 5;
        $this->formData['card_exp_year'] = $str;

        $str = str_repeat('S', 25) . 1;
        $this->formData['card_owner'] = $str;

        $str = str_repeat(1, 4) . 1;
        $this->formData['security_code'] = $str;

        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('card_no')->getErrors());
        $this->assertNotEmpty($this->form->get('card_exp_month')->getErrors());
        $this->assertNotEmpty($this->form->get('card_exp_year')->getErrors());
        $this->assertNotEmpty($this->form->get('card_owner')->getErrors());
        $this->assertNotEmpty($this->form->get('security_code')->getErrors());
    }

    public function testInvalid_Regex()
    {
        $str = str_repeat('S', 16);
        $this->formData['card_no'] = $str;

        $str = str_repeat('S', 2);
        $this->formData['card_exp_month'] = $str;

        $str = str_repeat('S', 2);
        $this->formData['card_exp_year'] = $str;

        $str = str_repeat('あ', 25);
        $this->formData['card_owner'] = $str;

        $str = str_repeat('S', 4);
        $this->formData['security_code'] = $str;

        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('card_no')->getErrors());
        $this->assertNotEmpty($this->form->get('card_exp_month')->getErrors());
        $this->assertNotEmpty($this->form->get('card_exp_year')->getErrors());
        $this->assertNotEmpty($this->form->get('card_owner')->getErrors());
        $this->assertNotEmpty($this->form->get('security_code')->getErrors());
    }

    public function testInvalidExpireMonthYear()
    {
        $str = strtotime('-1 month');
        $card_exp_month = date("m", $str);
        $card_exp_year = substr(date("Y", $str), 2);

        $this->formData['card_exp_month'] = $card_exp_month;
        $this->formData['card_exp_year'] = $card_exp_year;
        $this->form->submit($this->formData);

        $this->assertFalse($this->form->isValid());
    }

    public function test_getZeroYearr()
    {
        $a = new RegistCreditType($this->app);
        $actual = $a->getZeroYear(null, null);
        $expected = array();
        for ($i = DATE('Y'); $i <= DATE('Y')+3; $i++) {
            $key = substr($i, -2);
            $expected[$key] = $key;
        }
        $this->assertEquals($expected, $actual);
    }
}
