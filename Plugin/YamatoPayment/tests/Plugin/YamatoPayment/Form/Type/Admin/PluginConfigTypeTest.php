<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Plugin\YamatoPayment\Form\Type\AbstractTypeTestCase;
use Symfony\Component\HttpFoundation\Request;

class PluginConfigTypeTest extends AbstractTypeTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'exec_mode' => '0',
        'shop_id' => '111111111',
        'ycf_str_code' => '11111111111',
        'enable_payment_type' => array('10'),
        'use_option' => '0',
        'access_key' => '1111111',
        'advance_sale' => '0',
        'ycf_str_password' => '11111111',
        'ycf_send_div' => '1',
        'ycf_ship_ymd' => '11',
        'ycf_deliv_disp' => '0',
        'ycf_invoice_reissue_mail_address' => 'dev_test@test.com',
        'ycf_invoice_reissue_mail_header' => 'あいうえおアイウエオＡＩＵＥＯａｉｕｅｏ ｱｲｳｴｵaiueoAIUEO　!#$%！＃＄％＆',
        'ycf_invoice_reissue_mail_footer' => 'あいうえおアイウエオＡＩＵＥＯａｉｕｅｏ ｱｲｳｴｵaiueoAIUEO　!#$%！＃＄％＆',
    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // プラグイン設定登録
        $this->form = $this->app['form.factory']
            ->createBuilder('yamato_plugin_config', null, array(
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
        $this->formData['exec_mode'] = '';
        unset($this->formData['enable_payment_type']);

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalid_Length()
    {
        $str = str_repeat(1, 9) . 1;
        $this->formData['shop_id'] = $str;

        $str = str_repeat(1, 11) . 1;
        $this->formData['ycf_str_code'] = $str;

        $str = str_repeat(1, 7) . 1;
        $this->formData['access_key'] = $str;

        $str = str_repeat('S', 8) . 1;
        $this->formData['ycf_str_password'] = $str;

        $str = str_repeat(1, 2) . 1;
        $this->formData['ycf_ship_ymd'] = $str;

        $str = str_repeat('A', 99999) . 1;
        $this->formData['ycf_invoice_reissue_mail_header'] = $str;
        $this->formData['ycf_invoice_reissue_mail_footer'] = $str;

        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('shop_id')->getErrors());
        $this->assertNotEmpty($this->form->get('ycf_str_code')->getErrors());
        $this->assertNotEmpty($this->form->get('access_key')->getErrors());
        $this->assertNotEmpty($this->form->get('ycf_str_password')->getErrors());
        $this->assertNotEmpty($this->form->get('ycf_ship_ymd')->getErrors());
        $this->assertNotEmpty($this->form->get('ycf_invoice_reissue_mail_header')->getErrors());
        $this->assertNotEmpty($this->form->get('ycf_invoice_reissue_mail_footer')->getErrors());
    }

    public function testInvalid_Regex()
    {
        $str = str_repeat('S', 9);
        $this->formData['shop_id'] = $str;

        $str = str_repeat('S', 13);
        $this->formData['ycf_str_code'] = $str;

        $str = str_repeat('S', 7);
        $this->formData['access_key'] = $str;

        $str = str_repeat('あ', 8);
        $this->formData['ycf_str_password'] = $str;

        $str = str_repeat('S', 2);
        $this->formData['ycf_ship_ymd'] = $str;

        $str = str_repeat('あ', 2);
        $this->formData['ycf_invoice_reissue_mail_address'] = $str;

        $this->form->submit($this->formData);
        $this->form->isValid();

        $this->assertNotEmpty($this->form->get('shop_id')->getErrors());
        $this->assertNotEmpty($this->form->get('ycf_str_code')->getErrors());
        $this->assertNotEmpty($this->form->get('access_key')->getErrors());
        $this->assertNotEmpty($this->form->get('ycf_str_password')->getErrors());
        $this->assertNotEmpty($this->form->get('ycf_ship_ymd')->getErrors());
        $this->assertNotEmpty($this->form->get('ycf_invoice_reissue_mail_address')->getErrors());
    }

    public function testInvalidUseOption()
    {
        $this->formData['use_option'] = '0';
        $this->formData['access_key'] = '';
        $this->formData['advance_sale'] = '';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidPaymentType()
    {
        $this->formData['enable_payment_type'] = array('10');
        $this->formData['shop_id'] = '';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidPaymentTypeDeferred()
    {
        $this->formData['enable_payment_type'] = array('60');
        $this->formData['ycf_str_code'] = '';
        $this->formData['ycf_str_password'] = '';
        $this->formData['ycf_send_div'] = '';
        $this->formData['ycf_ship_ymd'] = '';
        $this->formData['ycf_deliv_disp'] = '';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidYcfShipYmd()
    {
        $this->formData['ycf_ship_ymd'] = '91';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInvalid_Email()
    {
        $str = str_repeat('a', 2);
        $this->formData['ycf_invoice_reissue_mail_address'] = $str;

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }
}
