<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type;


class CvsTypeTest extends AbstractTypeTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'cvs' => 21,  // セブンイレブン
    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // カード情報登録・更新
        $cvsForm = new CvsType($this->app);
        $this->form = $this->app['form.factory']
            ->createBuilder($cvsForm, null, array(
                'csrf_protection' => false,
            ))
            ->getForm();
    }

    public function testValidData()
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInValidData()
    {
        $this->formData['cvs'] = null;
        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
        $this->assertNotEmpty($this->form->get('cvs')->getErrors());
    }
}
