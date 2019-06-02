<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Util;

use Plugin\YamatoPayment\AbstractYamatoPaymentTestCase;
use Plugin\YamatoPayment\Entity\YamatoPlugin;

class PluginUtilTest extends AbstractYamatoPaymentTestCase
{
    /** @var PluginUtil */
    var $object;

    /**
     * @var array サブデータ
     */
    var $subData = array(
        'master_settings' => array(),
        'user_settings' => array(
            'shop_id' => '11111111',
            'exec_mode' => '1',
        ),
    );

    /**
     * @var array B2サブデータ
     */
    var $b2Data = array(
        'master_settings' => array(),
        'user_settings' => array(
            'shop_id' => '22222222',
            'exec_mode' => '2',
        ),
    );

    /**
     * @var array プラグイン情報
     */
    var $pluginInfo = array(
        'code' => 'YamatoPayment',
        'name' => 'ヤマト決済プラグイン',
    );


    function setUp()
    {
        parent::setUp();

        $this->object = $this->app['yamato_payment.util.plugin'];
        $this->object->subData = $this->subData;
        $this->object->b2Data = $this->b2Data;
    }

    function test_getPluginName()
    {
        $expected = 'クロネコヤマト カード・後払い一体型決済モジュール';
        $actual = $this->object->getPluginName();
        $this->assertEquals($expected, $actual);
    }

    function test_getPluginCode()
    {
        $expected = 'YamatoPayment';
        $actual = $this->object->getPluginCode();
        $this->assertEquals($expected, $actual);
    }

    function test_getPluginVersion()
    {
        $expected = '1.0.8';
        $actual = $this->object->getPluginVersion();
        $this->assertEquals($expected, $actual);
    }

    function test_getUserSettings()
    {
        $expected = $this->object->subData['user_settings'];

        // user_settingsが返る
        $this->assertEquals($expected, $this->object->getUserSettings());

        // user_settingsの指定したキーの値が返る
        $key = 'shop_id';
        $this->assertEquals($expected[$key], $this->object->getUserSettings($key));

        // 指定したキーが存在しない場合、NULLが返る
        $key = 'zzzzzzzz';
        $this->assertNull($this->object->getUserSettings($key));
    }

    function test_getSubData()
    {
        $expected = $this->object->subData;

        // subDataが返る
        $this->assertEquals($expected, $this->object->getSubData());

        // 指定したキーの値が返る
        $key = 'user_settings';
        $this->assertEquals($expected[$key], $this->object->getSubData($key));

        // 指定したキーが存在しない場合、NULLが返る
        $key = 'zzzzzzzz';
        $this->assertNull($this->object->getSubData($key));
    }

    function test_getSubData_Null()
    {
        // プラグインデータ作成
        $pluginData = $this->getNewPluginData();
        $expected = $pluginData->getSubData();

        // キャッシュがある場合、キャッシュが返る
        $this->assertNotEquals($expected, $this->object->getSubData());

        // キャッシュがnullの場合、DB値が返る
        $this->object->subData = null;
        $this->assertEquals($expected, $this->object->getSubData());
    }

    function test_registerSubData()
    {
        // データ新規作成
        $pluginData = $this->getNewPluginData();

        // 更新データ作成
        $expected = $pluginData->getSubData();
        $expected['user_settings']['shop_id'] = '33333333';

        // 更新前確認
        $this->object->subData = null;
        $this->assertNotEquals($expected, $this->object->getSubData());

        // データ更新
        $this->object->registerSubData($expected);

        // データ更新確認
        $this->object->subData = null;
        $this->assertEquals($expected, $this->object->getSubData());
    }

    function test_registerUserSettings()
    {
        $key = 'user_settings';

        // データ新規作成
        $pluginData = $this->getNewPluginData();

        // 更新データ作成
        $expected = $pluginData->getSubData();
        $expected['user_settings']['shop_id'] = '33333333';

        // 更新前確認
        $this->object->subData = null;
        $this->assertNotEquals($expected[$key], $this->object->getUserSettings());

        // データ更新
        $this->object->registerUserSettings($expected[$key]);

        // データ更新確認
        $this->object->subData = null;
        $this->assertEquals($expected[$key], $this->object->getUserSettings());
    }

    function test_getB2UserSettings()
    {
        $expected = $this->object->b2Data['user_settings'];

        // user_settingsが返る
        $this->assertEquals($expected, $this->object->getB2UserSettings());

        // user_settingsの指定したキーの値が返る
        $key = 'shop_id';
        $this->assertEquals($expected[$key], $this->object->getB2UserSettings($key));

        // 指定したキーが存在しない場合、NULLが返る
        $key = 'zzzzzzzz';
        $this->assertNull($this->object->getB2UserSettings($key));
    }

    function test_getB2Data()
    {
        $expected = $this->object->b2Data;

        // user_settingsが返る
        $this->assertEquals($expected, $this->object->getB2Data());

        // 指定したキーの値が返る
        $key = 'user_settings';
        $this->assertEquals($this->b2Data[$key], $this->object->getB2Data($key));

        // 指定したキーが存在しない場合、NULLが返る
        $key = 'zzzzzzzz';
        $this->assertNull($this->object->getB2Data($key));
    }

    function test_getB2Data_Null()
    {
        // プラグインデータ作成
        $pluginData = $this->getNewPluginData();
        $expected = $pluginData->getb2Data();

        // キャッシュがある場合、キャッシュが返る
        $this->assertNotEquals($expected, $this->object->getb2Data());

        // キャッシュがnullの場合、DB値が返る
        $this->object->b2Data = null;
        $this->assertEquals($expected, $this->object->getb2Data());
    }

    function test_registerB2Data()
    {
        // データ新規作成
        $pluginData = $this->getNewPluginData();

        // 更新データ作成
        $expected = $pluginData->getB2Data();
        $expected['user_settings']['shop_id'] = '33333333';

        // 更新前確認
        $this->object->b2Data = null;
        $this->assertNotEquals($expected, $this->object->getB2Data());

        // データ更新
        $this->object->registerB2Data($expected);

        // データ更新確認
        $this->object->b2Data = null;
        $this->assertEquals($expected, $this->object->getB2Data());
    }

    function test_registerB2UserSettings()
    {
        $key = 'user_settings';

        // データ新規作成
        $pluginData = $this->getNewPluginData();

        // 更新データ作成
        $expected = $pluginData->getB2Data();
        $expected['user_settings']['shop_id'] = '33333333';

        // 更新前確認
        $this->object->subData = null;
        $this->assertNotEquals($expected[$key], $this->object->getB2UserSettings());

        // データ更新
        $this->object->registerB2UserSettings($expected[$key]);

        // データ更新確認
        $this->object->subData = null;
        $this->assertEquals($expected[$key], $this->object->getB2UserSettings());
    }

    function test_getDeferredCharge_請求書同梱が未設定なら0が返る()
    {
        // 請求書同梱が未設定
        $this->object->subData['user_settings']['ycf_send_div'] = '';

        $expected = 0;
        $this->assertEquals($expected, $this->object->getDeferredCharge());
    }

    function test_getDeferredCharge_請求書同梱無しなら同梱なしの手数料が返る()
    {
        // 請求書同梱なし
        $this->object->subData['user_settings']['ycf_send_div'] = '0';

        $expected = $this->const['DEFERRED_CHARGE'];
        $this->assertEquals($expected, $this->object->getDeferredCharge());
    }

    function test_getDeferredCharge_請求書同梱ありなら同梱ありの手数料が返る()
    {
        // 請求書同梱あり
        $this->object->subData['user_settings']['ycf_send_div'] = '1';

        $expected = $this->const['DEFERRED_SEND_DELIV_CHARGE'];
        $this->assertEquals($expected, $this->object->getDeferredCharge());
    }

    function test_printLog()
    {
        // パス
        $filename = __DIR__ . "/../../../../../../log/YamatoPayment" . "-" . date('Y-m-d') . ".log";

        /*
         * 新しいログファイルを書き込む
         */
        $this->object->printLog('Unit test');
        $this->assertTrue(file_exists($filename));

        /*
         * ログを追記する
         */
        $beforeLog = file_get_contents($filename);
        $this->object->printLog('Unit test 2');
        $afterLog = file_get_contents($filename);
        $this->assertNotEquals($beforeLog, $afterLog);

        $msg = array(
            'card_no' => '1111111111111111'
        );

        /*
         * 新しいログファイルを書き込む
         */
        $this->object->printLog($msg);
        $this->assertTrue(file_exists($filename));

        /*
         * ログを追記する
         */
        $beforeLog = file_get_contents($filename);
        $this->object->printLog('Unit test 2');
        $afterLog = file_get_contents($filename);
        $this->assertNotEquals($beforeLog, $afterLog);

    }

    function test_printLog_各レベルでの確認()
    {
        // パス
        $filename = __DIR__ . "/../../../../../../log/YamatoPayment" . "-" . date('Y-m-d') . ".log";

        /*
         * 新しいエラーログファイルを書き込む
         */
        $this->object->printLog('Unit test', 'ERROR');
        $this->assertTrue(file_exists($filename));
        $this->assertContains('ERROR', file_get_contents($filename));

        /*
         * デバッグログを追記する
         */
        $beforeLog = file_get_contents($filename);
        $this->object->printLog('Unit test 2', 'DEBUG');
        $afterLog = file_get_contents($filename);
        $this->assertNotEquals($beforeLog, $afterLog);
        $this->assertContains('DEBUG', file_get_contents($filename));

         /*
         * 出力レベルがデフォルトの場合、デバッグログを追記する
         */
        $beforeLog = file_get_contents($filename);
        $this->object->printLog('Unit test 3', '');
        $afterLog = file_get_contents($filename);
        $this->assertNotEquals($beforeLog, $afterLog);
        $this->assertContains('DEBUG', file_get_contents($filename));
    }

    function test_printErrorLog()
    {
        // パス
        $filename = __DIR__ . "/../../../../../../log/YamatoPayment" . "-" . date('Y-m-d') . ".log";

        /*
         * 新しいログファイルを書き込む
         */
        $this->object->printErrorLog('Unit test');
        $this->assertTrue(file_exists($filename));

        /*
         * ログを追記する
         */
        $beforeLog = file_get_contents($filename);
        $this->object->printErrorLog('Unit test 2');
        $afterLog = file_get_contents($filename);
        $this->assertNotEquals($beforeLog, $afterLog);
    }

    function test_printDebugLog()
    {
        // パス
        $filename = __DIR__ . "/../../../../../../log/YamatoPayment" . "-" . date('Y-m-d') . ".log";

        /*
         * 新しいログファイルを書き込む
         */
        $this->object->printDebugLog('Unit test');
        $this->assertTrue(file_exists($filename));

        /*
         * ログを追記する
         */
        $beforeLog = file_get_contents($filename);
        $this->object->printDebugLog('Unit test 2');
        $afterLog = file_get_contents($filename);
        $this->assertNotEquals($beforeLog, $afterLog);
    }

    function test_setRegistSuccess_ページ遷移の正当性フラグが保存される()
    {
        $this->assertEquals('', $this->app['session']->get('yamato_payment.pre_regist_success'));

        $this->object->setRegistSuccess();

        $this->assertEquals(true, $this->app['session']->get('yamato_payment.pre_regist_success'));
    }

    function test_savePagePath_引数の値がセッションに保存される()
    {
        $this->assertEquals('', $this->app['session']->get('yamato_payment.now_page'));
        $this->assertEquals('', $this->app['session']->get('yamato_payment.pre_page'));

        $this->object->savePagePath('a1');

        $this->assertEquals('a1', $this->app['session']->get('yamato_payment.now_page'));
        $this->assertEquals('', $this->app['session']->get('yamato_payment.pre_page'));

        $this->object->savePagePath('a2');

        $this->assertEquals('a2', $this->app['session']->get('yamato_payment.now_page'));
        $this->assertEquals('a1', $this->app['session']->get('yamato_payment.pre_page'));

        $this->object->savePagePath('a3');

        $this->assertEquals('a3', $this->app['session']->get('yamato_payment.now_page'));
        $this->assertEquals('a2', $this->app['session']->get('yamato_payment.pre_page'));
    }

    function test_isPrePage_現ページ情報か前ページ情報がないならfalseが返る()
    {
        $this->app['session']->remove('yamato_payment.now_page');
        $this->app['session']->remove('yamato_payment.pre_page');

        $this->assertFalse($this->object->isPrePage());

        $this->app['session']->set('yamato_payment.now_page', 'aaa');
        $this->app['session']->remove('yamato_payment.pre_page');

        $this->assertFalse($this->object->isPrePage());

        $this->app['session']->remove('yamato_payment.now_page');
        $this->app['session']->set('yamato_payment.pre_page', 'aaa');

        $this->assertFalse($this->object->isPrePage());
    }

    function test_isPrePage_現ページ情報と前ページ情報が一致しないならfalseが返る()
    {
        $this->app['session']->set('yamato_payment.now_page', 'aaa');
        $this->app['session']->set('yamato_payment.pre_page', 'bbb');

        $this->assertFalse($this->object->isPrePage());
    }

    function test_isPrePage_現ページ情報と前ページ情報が一致せず成功フラグが立っているならtrueが返る()
    {
        $this->app['session']->set('yamato_payment.now_page', 'aaa');
        $this->app['session']->set('yamato_payment.pre_page', 'bbb');
        $this->app['session']->set('yamato_payment.pre_regist_success', true);

        $this->assertTrue($this->object->isPrePage());
    }

    function test_isPrePage_現ページ情報と前ページ情報が一致するならtrueが返る()
    {
        $this->app['session']->set('yamato_payment.now_page', 'aaa');
        $this->app['session']->set('yamato_payment.pre_page', 'aaa');

        $this->assertTrue($this->object->isPrePage());
    }

    /**
     * @return YamatoPlugin
     */
    private function getNewPluginData()
    {
        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();

        $subData = $this->subData;
        $subData['user_settings']['shop_id'] = $faker->numberBetween($min = 90000000, $max = 99999999);

        $b2Data = $this->b2Data;
        $b2Data['user_settings']['shop_id'] = $faker->numberBetween($min = 90000000, $max = 99999999);

        $YamatoPlugin = new YamatoPlugin();
        $YamatoPlugin
            ->setCode($this->pluginInfo['code'])
            ->setName($this->pluginInfo['name'])
            ->setSubData($subData)
            ->setB2Data($b2Data)
            ->setAutoUpdateFlg(0)
            ->setDelFlg(0);

        // 登録処理
        $this->registerPluginData($YamatoPlugin);

        return $YamatoPlugin;
    }

    /**
     * @param YamatoPlugin $YamatoPlugin
     */
    private function registerPluginData($YamatoPlugin)
    {
        $YamatoPluginOld = $this->app['yamato_payment.repository.yamato_plugin']
            ->findOneBy(array(
                'code' => $this->pluginInfo['code'],
            ));

        // プラグイン情報を削除
        if (!is_null($YamatoPluginOld)) {
            $this->app['orm.em']->remove($YamatoPluginOld);
        }

        // プラグイン情報を登録
        $this->app['orm.em']->persist($YamatoPlugin);
        $this->app['orm.em']->flush();
    }

}
