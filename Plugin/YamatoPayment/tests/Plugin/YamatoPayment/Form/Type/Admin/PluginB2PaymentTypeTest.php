<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Plugin\YamatoPayment\Form\Type\AbstractTypeTestCase;
use Symfony\Component\HttpFoundation\Request;

class PluginB2PaymentTypeTest extends AbstractTypeTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'payment_id' => '',
        'payment_method' => '',
        'deliv_slip_type' => '0',
    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // B2送り状種別設定登録
        $this->form = $this->app['form.factory']
            ->createBuilder('yamato_b2_payment', null, array(
                'csrf_protection' => false,
            ))
            ->getForm();
    }

    public function testValidData()
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }
}
