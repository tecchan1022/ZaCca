<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Plugin\YamatoPayment\Form\Extension\AbstractTypeExtensionTestCase;

class SearchOrderTypeExtensionTest extends AbstractTypeExtensionTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    public function setUp()
    {
        parent::setUp();
    }

    function createForm()
    {
        // CSRF tokenを無効にしてFormを作成
        $form = $this->app['form.factory']
            ->createBuilder('admin_search_order', null, array(
                'csrf_protection' => false,
            ))
            ->getForm();

        return $form;
    }

    public function testSubmit_検索条件がセッションに保持される()
    {
        /** @var ArrayCollection $expected */
        $expected = new \Doctrine\Common\Collections\ArrayCollection();
        $expected->add($this->app['eccube.repository.master.product_type']->find(9625));

        $formData = array(
            'product_type' => '9625'
        );

        // フォーム作成
        $form = $this->createForm();

        // サブミット
        $form->submit($formData);
        $this->assertTrue($form->isValid());

        // フォームにデータがセットされている
        $data = $form->get('product_type')->getData();
        $this->assertEquals($expected, $data);

        // セッションにデータがセットされている
        $data = $this->app['session']->get('yamato_payment.admin.order.search.product_type');
        $this->assertEquals($expected, $data);
    }

    public function testSetData_検索条件がセッションから取得される()
    {
        /** @var ArrayCollection $expected */
        $expected = new \Doctrine\Common\Collections\ArrayCollection();
        $expected->add($this->app['eccube.repository.master.product_type']->find(9625));

        // 検索条件をセッションに保持
        $this->app['session']->set('yamato_payment.admin.order.search.product_type', $expected);

        // フォーム作成
        $form = $this->createForm();

        // フォームにデータがセットされている
        $data = $form->get('product_type')->getData();
        $this->assertEquals($expected->toArray(), $data);
    }

}
