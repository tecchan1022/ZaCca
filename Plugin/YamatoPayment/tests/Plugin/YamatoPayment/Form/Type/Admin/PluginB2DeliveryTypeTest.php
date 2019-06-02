<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Plugin\YamatoPayment\Form\Type\AbstractTypeTestCase;
use Symfony\Component\HttpFoundation\Request;

class PluginB2DeliveryTypeTest extends AbstractTypeTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'delivery_id' => '',
        'delivery_name' => '',
        'cool_kb' => '0',
        'delivery_service_code' => '0',
        'b2_delivtime_code' => array(),
    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // B2クール便区分設定登録
        $this->form = $this->app['form.factory']
            ->createBuilder('yamato_b2_delivery', null, array(
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
