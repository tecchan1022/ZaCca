<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type;

class ThreeDTranTypeTest extends AbstractTypeTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = array(
        'COMP_CD' => '0000',
        'CARD_NO' => '0000',
        'CARD_EXP' => '0000',
        'ITEM_PRICE' => '0000',
        'ITEM_TAX' => '0000',
        'CUST_CD' => '0000',
        'SHOP_ID' => '0000',
        'TERM_CD' => '0000',
        'CRD_RES_CD' => '0000',
        'RES_VE' => '0000',
        'RES_PA' => '0000',
        'RES_CODE' => '0000',
        '3D_INF' => '0000',
        '3D_TRAN_ID' => '0000',
        'SEND_DT' => '0000',
        'HASH_VALUE' => '0000',
    );

    public function setUp()
    {
        parent::setUp();

        // CSRF tokenを無効にしてFormを作成
        // 決済結果受信パラメータ構築
        $this->form = $this->app['form.factory']
            ->createBuilder(new ThreeDTranType())
            ->getForm();
    }

    public function testValidData()
    {
        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }
}
