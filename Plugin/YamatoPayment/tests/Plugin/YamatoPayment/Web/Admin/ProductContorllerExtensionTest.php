<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Web\Admin;

use Eccube\Entity\Csv;

class ProductControllerExtensionTest extends AbstractAdminWebTestCase
{
    public function testExport()
    {
        // enable_flg = 1に設定
        foreach($this->app['eccube.repository.csv']->findAll() as $csv){
            /** @var Csv $csv */
            $csv->setEnableFlg(1);
            $this->app['orm.em']->persist($csv);
        }
        $this->app['orm.em']->flush();

        // 商品CSVファイルをエクスポート
        $this->client->request('GET',
            $this->app->url('admin_product_export')
        );

        // エクスポートしたCSVファイルをUTF-8に変換
        $csv = mb_convert_encoding($this->getActualOutput(), 'UTF-8', 'SJIS');

        // エクスポートが完了すること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // CSVファイルに『予約商品出荷予定日』、『後払い不可商品』が存在すること
        $this->assertRegexp('/予約商品出荷予定日/u', $csv);
        $this->assertRegexp('/後払い不可商品/u', $csv);
    }
}
