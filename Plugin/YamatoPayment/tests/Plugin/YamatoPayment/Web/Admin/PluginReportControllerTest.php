<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Web\Admin;

use Plugin\YamatoPayment\PluginManager;

class PluginReportControllerTest extends AbstractAdminWebTestCase
{
    protected $subData;

    /** @var PluginManager $object */
    var $object;

    public function setUp()
    {
        parent::setUp();

        $this->object = new PluginManager();
    }

    public function testEdit__初期表示_True()
    {
        // ファイルのコピー
        $method = new \ReflectionMethod(get_class($this->object), 'copyResource');
        $method->setAccessible(true);

        $method->invoke($this->object);

        $crawler = $this->client->request(
            'GET',
            $this->app->path('plugin_YamatoPayment_report')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testEdit__初期表示_False()
    {
        // ファイルの削除
        $method = new \ReflectionMethod(get_class($this->object), 'removeResource');
        $method->setAccessible(true);

        $method->invoke($this->object);

        $crawler = $this->client->request(
            'GET',
            $this->app->path('plugin_YamatoPayment_report')
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージを取得すること
        $this->assertRegexp('/設定値の不正が検出されました。/u',
            $crawler->filter('div.alert-danger')->text());

        // ファイルのコピー
        $method = new \ReflectionMethod(get_class($this->object), 'copyResource');
        $method->setAccessible(true);

        $method->invoke($this->object);
    }

    public function testExportCSV()
    {
        // プラグインレポートCSVファイルダウンロード
        $this->client->request('GET',
            $this->app->url('plugin_YamatoPayment_report_export')
        );

        // エクスポートが完了すること
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エクスポートしたCSVファイルをUTF-8に変換
        $csv = mb_convert_encoding($this->getActualOutput(), 'UTF-8', 'SJIS');

        // CSVファイルに『システム情報』が存在すること
        $this->assertRegexp('/システム情報/u', $csv);
    }
}
