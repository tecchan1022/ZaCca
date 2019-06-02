<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;


use Eccube\Entity\Product;
use Plugin\YamatoPayment\Entity\YamatoProduct;
use Plugin\YamatoPayment\Form\Extension\AbstractTypeExtensionTestCase;

class ProductTypeExtensionTest extends AbstractTypeExtensionTestCase
{
    /** @var \Eccube\Application */
    protected $app;

    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData;

    public function setUp()
    {
        parent::setUp();

        // 予約販売機能利用有り
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $userSettings['advance_sale'] = '0';
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);

        // CSRF tokenを無効にしてFormを作成
        $this->form = $this->app['form.factory']
            ->createBuilder('admin_product', null, array(
                'csrf_protection' => false,
            ))
            ->getForm();
    }

    private function createFormDate()
    {
        // 予約商品出荷予定日をランダム作成
        date_default_timezone_set('UTC');
        $start = strtotime('2020-01-01 00:00:00'); // 0
        $end = strtotime('2038-01-19 03:14:07'); // 2147483647
        $reserve_date = date('Ymd', mt_rand($start, $end));

        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();

        $formData = array(
            'name' => $faker->text(5),
            'Status' => array(1),
            'reserve_date' => $reserve_date,
            'class' => array(
                'price02' => $faker->randomNumber(4),
                'product_type' => 9625,
                'stock_unlimited' => '1',
            ),
        );

        return $formData;
    }

    public function testValidData()
    {
        $this->formData = $this->createFormDate();

        $this->form->submit($this->formData);
        $this->assertTrue($this->form->isValid());
    }

    public function testInValidData()
    {
        $this->formData = $this->createFormDate();

        $this->formData['reserve_date'] = '';

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testInValidData_advance_sale()
    {
        // 予約販売機能利用無し
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $userSettings['advance_sale'] = '1';
        $this->app['yamato_payment.util.plugin']->registerUserSettings($userSettings);

        $this->formData = $this->createFormDate();

        $this->form->submit($this->formData);
        $this->assertFalse($this->form->isValid());
    }

    public function testSetData()
    {
        // 商品マスタ取得
        /** @var Product $Product */
        $Product = $this->app['eccube.repository.product']->find(1);
        $YamatoProduct = $this->createYamatoProduct($Product);

        // 商品データを引数にFormを作成
        $this->form = $this->app['form.factory']
            ->createBuilder('admin_product', $Product)
            ->getForm();

        // 追加項目に初期値が設定されていること
        $this->assertEquals($YamatoProduct->getReserveDate(), $this->form->get('reserve_date')->getData());
        $this->assertEquals($YamatoProduct->getNotDeferredFlg(), $this->form->get('not_deferred_flg')->getData());
    }


    /**
     * @param Product $Product
     * @return YamatoProduct
     */
    function createYamatoProduct($Product)
    {
        $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($Product->getId());
        if (is_null($YamatoProduct)) {
            $YamatoProduct = new YamatoProduct();
            $YamatoProduct->setId($Product->getId());
        }
        $YamatoProduct
            ->setReserveDate('20160101')
            ->setNotDeferredFlg('1');
        $this->app['orm.em']->persist($YamatoProduct);
        $this->app['orm.em']->flush($YamatoProduct);
        return $YamatoProduct;
    }

}
