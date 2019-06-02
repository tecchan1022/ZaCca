<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;

use Plugin\YamatoPayment\Form\Extension\AbstractTypeExtensionTestCase;

class MailTypeExtensionTest extends AbstractTypeExtensionTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'template' => '',
        'subject' => '件名',
        'header' => 'ヘッダー',
        'footer' => 'フッター',
    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // 送り状番号登録・編集
        $this->form = $this->app['form.factory']
            ->createBuilder('mail', null, array(
                'csrf_protection' => false,
            ))
            ->getForm();

        // テンプレートの設定
        $this->formData['template'] = $this->app['eccube.repository.mail_template']->find(1)->getId();
    }

    public function testValidData()
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
        $this->assertEquals($this->formData['template'], $this->form->get('mail_template_id')->getData());
    }

    public function testInvalid_Blank()
    {
        $this->formData['template'] = '';
        $this->formData['subject'] = '';
        $this->formData['header'] = '';
        $this->formData['footer'] = '';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }
}
