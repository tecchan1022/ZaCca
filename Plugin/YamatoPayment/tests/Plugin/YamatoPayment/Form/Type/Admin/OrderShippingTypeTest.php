<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Plugin\YamatoPayment\Form\Type\AbstractTypeTestCase;

class OrderShippingTypeTest extends AbstractTypeTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'deliv_slip_number' => '123456789013',
    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // 送り状番号登録・編集
        $this->form = $this->app['form.factory']
            ->createBuilder('yamato_shipping', null, array(
                'csrf_protection' => false,
            ))
            ->getForm();
    }

    public function testValidData()
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidNum_MaxLength()
    {
        $str = str_repeat(1, 12) . 1;

        $this->formData['deliv_slip_number'] = $str;
        $this->form->submit($this->formData);

        $this->assertFalse($this->form->isValid());
    }

    public function testInvalidNum_Regex()
    {
        $str = str_repeat('S', 12);

        $this->formData['deliv_slip_number'] = $str;
        $this->form->submit($this->formData);

        $this->assertFalse($this->form->isValid());
    }
}
