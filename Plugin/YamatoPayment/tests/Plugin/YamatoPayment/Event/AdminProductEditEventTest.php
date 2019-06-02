<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Entity\Product;
use Eccube\Common\Constant;
use Plugin\YamatoPayment\Entity\YamatoProduct;

class AdminProductEditEventTest extends AbstractEventTestCase
{
    function setUp()
    {
        parent::setUp();
        $this->adminLogIn();
    }

    function createFormData()
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();
        $form = array(
            'class' => array(
                'product_type' => 1,
                'price01' => $faker->randomNumber(5),
                'price02' => $faker->randomNumber(5),
                'stock' => $faker->randomNumber(3),
                'stock_unlimited' => 0,
                'code' => $faker->word,
                'sale_limit' => null,
                'delivery_date' => ''
            ),
            'name' => $faker->word,
            'product_image' => null,
            'description_detail' => $faker->text,
            'description_list' => $faker->paragraph,
            'Category' => null,
            // v3.0.10対応（v3.0.9もOK）
            //'tag' => $faker->word,
            'search_word' => $faker->word,
            'free_area' => $faker->text,
            'Status' => 1,
            'note' => $faker->text,
            'tags' => null,
            'images' => null,
            'add_images' => null,
            'delete_images' => null,
            'reserve_date' => '20160601',
            'not_deferred_flg' => '1',
            '_token' => 'dummy',
        );
        return $form;
    }

    function testRenderEvent_追加項目の表示確認_予約販売機能を利用するの場合()
    {
        // オプションサービス契約済み・予約販売機能利用
        $pluginSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $pluginSettings['use_option'] = '0';
        $pluginSettings['advance_sale'] = '0';
        $this->app['yamato_payment.util.plugin']->registerUserSettings($pluginSettings);

        $crawler = $this->client->request('GET',
            $this->app->url('admin_product_product_new')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 基本情報エリアのソース取得
        $source = $crawler->filter('#detail_box')->html();

        // 追加項目が表示されていること
        $this->assertRegExp('/予約商品出荷予定日/u', $source);
        $this->assertRegExp('/後払い不可商品/u', $source);
    }

    function testRenderEvent_追加項目の表示確認_予約販売機能を利用しない場合()
    {
        // オプションサービス契約済み・予約販売機能利用
        $pluginSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        $pluginSettings['use_option'] = '0';
        $pluginSettings['advance_sale'] = '1';
        $this->app['yamato_payment.util.plugin']->registerUserSettings($pluginSettings);

        $crawler = $this->client->request('GET',
            $this->app->url('admin_product_product_new')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 基本情報エリアのソース取得
        $source = $crawler->filter('#detail_box')->html();

        // 予約商品出荷予定日項目が表示されていないこと
        $this->assertNotRegExp('/予約商品出荷予定日/u', $source);
        $this->assertRegExp('/後払い不可商品/u', $source);
    }

    function testEditWithPost_追加項目の登録確認()
    {
        if (version_compare(Constant::VERSION, '3.0.11', '>=')) {
            $Product = $this->createProduct(null, 0);
        } else {
            $Product = $this->createProduct();
        }
        $formData = $this->createFormData();
        $this->client->request(
            'POST',
            $this->app->url('admin_product_product_edit', array('id' => $Product->getId())),
            array('admin_product' => $formData)
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_product_product_edit', array('id' => $Product->getId()))));

        /** @var YamatoProduct $EditedYamatoProduct */
        $EditedYamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find($Product->getId());
        $this->expected = $formData['reserve_date'];
        $this->actual = $EditedYamatoProduct->getReserveDate();
        $this->verify();
        $this->expected = $formData['not_deferred_flg'];
        $this->actual = $EditedYamatoProduct->getNotDeferredFlg();
        $this->verify();
    }

    function testCopy_追加項目のコピー確認()
    {
        $Product = $this->createProduct();
        $this->createYamatoProduct($Product);

        $AllYamatoProducts = $this->app['yamato_payment.repository.yamato_product']->findAll();
        $this->client->request(
            'POST',
            $this->app->url('admin_product_product_copy', array('id' => $Product->getId()))
        );

        $this->assertTrue($this->client->getResponse()->isRedirect());

        $AllYamatoProducts2 = $this->app['yamato_payment.repository.yamato_product']->findAll();
        $this->expected = count($AllYamatoProducts) + 1;
        $this->actual = count($AllYamatoProducts2);
        $this->verify();
    }

    /**
     * @param Product $Product
     * @return YamatoProduct
     */
    function createYamatoProduct($Product)
    {
        $YamatoProduct = new YamatoProduct();
        $YamatoProduct
            ->setId($Product->getId())
            ->setReserveDate('20160101')
            ->setNotDeferredFlg('1');
        $this->app['orm.em']->persist($YamatoProduct);
        $this->app['orm.em']->flush($YamatoProduct);
        return $YamatoProduct;
    }
}
