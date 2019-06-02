<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Plugin\YamatoPayment\Form\Type\AbstractTypeTestCase;
use Symfony\Component\HttpFoundation\Request;

class PluginB2ConfigTypeTest extends AbstractTypeTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'claim_customer_code' => '111111111111',
        'claim_type_code' => '111',
        'transportation_no' => '11',
        'header_output' => '0',
        'b2_payment_type' => array(),
        'b2_delivery_type' => array(),
        'tel_hyphenation' => '0',
        'zip_hyphenation' => '0',
        'service_deliv_mail_enable' => '0',
        'service_deliv_mail_message' => '',
        'service_complete_mail_enable' => '0',
        'service_complete_mail_message' => '',
        'output_order_type' => '0',
        'posting_plan_mail_enable' => '0',
        'posting_plan_mail_message' => '',
        'posting_complete_deliv_mail_enable' => '0',
        'posting_complete_deliv_mail_message' => '',
        'use_b2_format' => '0',
        'shpping_info_regist' => '0',
        'mode' => '',
    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // B2設定登録
        $this->form = $this->app['form.factory']
            ->createBuilder('yamato_b2_config', null, array(
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
        $this->app['request'] = new Request();
        $this->formData['claim_customer_code'] = '';
        $this->formData['transportation_no'] = '';
        $this->formData['header_output'] = '';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalid_Length()
    {
        $str = str_repeat(1, 12) . 1;
        $this->formData['claim_customer_code'] = $str;

        $str = str_repeat(1, 3) . 1;
        $this->formData['claim_type_code'] = $str;

        $str = str_repeat(1, 2) . 1;
        $this->formData['transportation_no'] = $str;

        $str = str_repeat('S', 74) . 1;
        $this->formData['service_deliv_mail_message'] = $str;

        $str = str_repeat('S', 159) . 1;
        $this->formData['service_complete_mail_message'] = $str;

        $str = str_repeat('S', 74) . 1;
        $this->formData['posting_plan_mail_message'] = $str;

        $str = str_repeat('S', 159) . 1;
        $this->formData['posting_complete_deliv_mail_message'] = $str;

        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('claim_customer_code')->getErrors());
        $this->assertNotEmpty($this->form->get('claim_type_code')->getErrors());
        $this->assertNotEmpty($this->form->get('transportation_no')->getErrors());
        $this->assertNotEmpty($this->form->get('service_deliv_mail_message')->getErrors());
        $this->assertNotEmpty($this->form->get('service_complete_mail_message')->getErrors());
        $this->assertNotEmpty($this->form->get('posting_plan_mail_message')->getErrors());
        $this->assertNotEmpty($this->form->get('posting_complete_deliv_mail_message')->getErrors());
    }

    public function testInvalid_Regex()
    {
        $str = str_repeat('S', 12);
        $this->formData['claim_customer_code'] = $str;

        $str = str_repeat('S', 3);
        $this->formData['claim_type_code'] = $str;

        $str = str_repeat('S', 2);
        $this->formData['transportation_no'] = $str;


        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('claim_customer_code')->getErrors());
        $this->assertNotEmpty($this->form->get('claim_type_code')->getErrors());
        $this->assertNotEmpty($this->form->get('transportation_no')->getErrors());
    }

    public function testInvalidDelivMail()
    {
        $this->formData['service_deliv_mail_enable'] = 1;
        $this->formData['service_deliv_mail_message'] = '';
        $this->form->submit($this->formData);

        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidCompMail()
    {
        $this->formData['service_complete_mail_enable'] = 1;
        $this->formData['service_complete_mail_message'] = '';
        $this->form->submit($this->formData);

        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidPostPlanMail()
    {
        $this->formData['posting_plan_mail_enable'] = 1;
        $this->formData['posting_plan_mail_message'] = '';
        $this->form->submit($this->formData);

        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidPostingCompMail()
    {
        $this->formData['posting_complete_deliv_mail_enable'] = 1;
        $this->formData['posting_complete_deliv_mail_message'] = '';
        $this->form->submit($this->formData);

        $this->assertFalse($this->form->isValid());
    }
}
