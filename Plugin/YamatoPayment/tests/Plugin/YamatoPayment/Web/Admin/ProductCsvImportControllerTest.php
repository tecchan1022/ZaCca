<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Web\Admin;

use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Entity\ProductStock;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductCsvImportControllerTest extends AbstractAdminWebTestCase
{
    protected $Products;
    protected $filepath;

    public function setUp()
    {
        parent::setUp();
        $this->filepath = $this->app['config']['root_dir']  . '/app/cache/product.csv';
    }

    public function tearDown()
    {
        if (file_exists($this->filepath)) {
            unlink($this->filepath);
        }
        parent::tearDown();
    }

    /**
     * CSVを生成するための配列を返す.
     *
     * @param boolean $has_header ヘッダ行を含める場合 true
     * @return array CSVを生成するための配列
     * @see CsvImportController::getProductCsvHeader()
     */
    public function createCsvAsArray($has_header = true)
    {
        //---------ヤマト決済プラグイン START---------
        // 予約商品出荷予定日をランダム作成
        date_default_timezone_set('UTC');
        $start = strtotime('2020-01-01 00:00:00'); // 0
        $end = strtotime('2038-01-19 03:14:07'); // 2147483647
        $reserve_date = date('Ymd', mt_rand($start, $end));
        //---------ヤマト決済プラグイン E N D---------

        /** @var \Faker\Generator $faker */
        $faker = $this->getFaker();
        $csv = array(
            '商品ID' => null,
            '公開ステータス(ID)' => 1,
            '商品名' => $faker->word,
            'ショップ用メモ欄' => $faker->paragraph,
            '商品説明(一覧)' => $faker->paragraph,
            '商品説明(詳細)' => $faker->text,
            '検索ワード' => $faker->word,
            'フリーエリア' => $faker->paragraph,
            '商品削除フラグ' => 0,
            '商品画像' => $faker->word.'.jpg,'.$faker->word.'.jpg',
            '商品カテゴリ(ID)' => '5,6',
            '商品種別(ID)' => 1,
            '規格分類1(ID)' => 3,
            '規格分類2(ID)' => 6,
            '発送日目安(ID)' => 1,
            '商品コード' => $faker->word,
            '在庫数' => 100,
            '在庫数無制限フラグ' => 0,
            '販売制限数' => null,
            '通常価格' => $faker->randomNumber(5),
            '販売価格' => $faker->randomNumber(5),
            '送料' => 0,
            '商品規格削除フラグ' => 0,
            //---------ヤマト決済プラグイン START---------
            '予約商品出荷予定日' => $reserve_date,
            '後払い不可商品' => 0,
            //---------ヤマト決済プラグイン E N D---------
        );
        $result = array();
        if ($has_header) {
            $result[] = array_keys($csv);
        }
        $result[] = array_values($csv);
        return $result;
    }

    /**
     * 引数の配列から CSV を生成し, リソースを返す.
     */
    public function createCsvFromArray(array $csv, $filename = 'product.csv')
    {
        $fp = fopen($this->filepath, 'w');
        if ($fp !== false) {
            foreach ($csv as $row) {
                fputcsv($fp, $row);
            }
        } else {
            throw new \Exception('create error!');
        }
        fclose($fp);
        return $this->filepath;
    }

    public function testCsvReserveProduct_新規登録()
    {
        // 既存の商品全件取得
        $existingYamatoProducts = $this->app['yamato_payment.repository.yamato_product']->findAll();
        $existingYamatoProductsCount = count($existingYamatoProducts);

        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        //---------ヤマト決済プラグイン START---------
        // 登録済みの商品全件取得
        $YamatoProducts = $this->app['yamato_payment.repository.yamato_product']->findAll();

        $this->expected = 3 + $existingYamatoProductsCount;    // 3商品 + 既存商品数
        $this->actual = count($YamatoProducts);
        // アップロードした商品と既存商品の合計数が正しいこと
        $this->verify();
        //---------ヤマト決済プラグイン E N D---------

        // アップロード完了後のメッセージが取得できること
        $this->assertRegexp('/商品登録CSVファイルをアップロードしました。/u',
            $crawler->filter('div.alert-success')->text());

        // 規格1のみ商品の確認
        // dtb_product_class.del_flg = 1 の確認をしたいので PDO で取得
        $pdo = $this->app['orm.em']->getConnection()->getWrappedConnection();
        $sql = "SELECT * FROM dtb_product_class WHERE product_code = 'class1-only' ORDER BY del_flg DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $this->expected = 2;
        $this->actual = count($result);
        $this->verify('取得できるのは2行');

        $this->expected = 1;
        $this->actual = $result[0]['del_flg'];
        $this->verify('result[0] は del_flg = 1');

        $this->expected = null;
        $this->actual = $result[0]['class_category_id1'];
        $this->verify('class_category_id1 は null');

        $this->expected = null;
        $this->actual = $result[0]['class_category_id2'];
        $this->verify('class_category_id2 は null');

        // del_flg = 0 の行の確認
        $this->expected = 0;
        $this->actual = $result[1]['del_flg'];
        $this->verify('result[1] は del_flg = 0');

        $this->expected = 3;
        $this->actual = $result[1]['class_category_id1'];
        $this->verify('class_category_id1 は 3');

        $this->expected = null;
        $this->actual = $result[1]['class_category_id2'];
        $this->verify('class_category_id2 は null');
    }

    //---------ヤマト決済プラグイン START---------
    public function testCsvReserveProduct_予約商品出荷予定日が数字以外の値の場合エラーメッセージが返る()
    {
        // 2商品生成
        $csv = $this->createCsvAsArray();
        // 予約商品出荷予定日に数字以外の値をセット
        $csv[1][23] = 'abcdefgh';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の予約商品出荷予定日は半角数字で入力してください。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvReserveProduct_予約商品出荷予定日が8桁以外の数字の場合エラーメッセージが返る()
    {
        // 2商品生成
        $csv = $this->createCsvAsArray();
        // 予約商品出荷予定日に8桁以外の数字をセット
        $csv[1][23] = 1;
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の予約商品出荷予定日は8文字で入力してください。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvReserveProduct_予約商品出荷予定日が存在しない日付の場合エラーメッセージが返る()
    {
        // 2商品生成
        $csv = $this->createCsvAsArray();
        // 予約商品出荷予定日に存在しない日付をセット
        $csv[1][23] = 12345678;
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の予約商品出荷予定日は存在しない日付です。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvReserveProduct_商品種別が予約商品で予約商品出荷予定日が空白の場合エラーメッセージが返る()
    {
        // 2商品生成
        $csv = $this->createCsvAsArray();
        // 商品種別に予約商品、予約商品出荷予定日に空白をセット
        $csv[1][11] = 9625;
        $csv[1][23] = '';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の予約商品出荷予定日が記入されていません。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvReserveProduct_後払い不可商品が空白でないかつ0か1でなかった場合エラーメッセージが返る()
    {
        // 2商品生成
        $csv = $this->createCsvAsArray();
        // 後払い不可商品に空白でなくかつ0,1以外の数字をセット
        $csv[1][24] = 2;
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertRegexp('/行目の後払い不可商品は0か1で記入してください。/u',
            $crawler->filter('div.text-danger')->text());
    }

    public function testCsvReserveProduct_既存の商品に予約商品出荷予定日を追加する()
    {
        // 既存の商品(商品ID=1)の削除フラグを0に更新
        $this->app['orm.em']
            ->getConnection()
            ->update('dtb_product', array('del_flg' => 0), array('product_id' => 1));

        // 既存の商品(商品ID=1)取得
        $Product = $this->app['eccube.repository.product']->find(1);

        // 商品ID=1の商品が存在すること
        $this->assertNotNull($Product);

        // CSVデータ生成
        $csv = $this->createCsvAsArray();
        // 商品ID設定
        $csv[1][0] = 1;

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // アップロード完了後のメッセージが取得できること
        $this->assertRegexp('/商品登録CSVファイルをアップロードしました。/u',
            $crawler->filter('div.alert-success')->text());

        // 商品ID=1の商品マスタ追加項目を取得
        $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']->find(1);

        // 商品ID=1の商品マスタ追加項目が存在すること
        $this->assertNotNull($YamatoProduct);

        // 商品ID=1の商品マスタ追加項目に更新した予約商品出荷予定日が存在すること
        $expected = $csv[1][23];
        $this->assertEquals($expected, $YamatoProduct->getReserveDate());
    }

    /**
     * 既存の規格なし商品に商品規格を追加する.
     */
    public function testCsvImportWithExistsProductsAddProductClass()
    {
        // 既存の商品全件取得
        $existingProducts = $this->app['eccube.repository.product']->findAll();
        $existingProductsCount = count($existingProducts);

        // 商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][0] = 2;                        // 商品ID = 2 に規格を追加する
        $csv[1][15] = 'add-class';             // 商品コード

        $this->filepath = $this->createCsvFromArray($csv);

        $crawler = $this->scenario();

        $Products = $this->app['eccube.repository.product']->findAll();

        $this->expected = $existingProductsCount;    // 既存商品
        $this->actual = count($Products);
        $this->verify();

        $this->assertRegexp('/商品登録CSVファイルをアップロードしました。/u',
            $crawler->filter('div.alert-success')->text());

        // 規格1のみ商品の確認
        // dtb_product_class.del_flg = 1 の確認をしたいので PDO で取得
        $pdo = $this->app['orm.em']->getConnection()->getWrappedConnection();
        $sql = "SELECT * FROM dtb_product_class WHERE product_id = 2 ORDER BY del_flg DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $this->expected = 2;
        $this->actual = count($result);
        $this->verify('取得できるのは2行');

        $this->expected = 1;
        $this->actual = $result[0]['del_flg'];
        $this->verify('result[0] は del_flg = 1');

        $this->expected = null;
        $this->actual = $result[0]['class_category_id1'];
        $this->verify('class_category_id1 は null');

        $this->expected = null;
        $this->actual = $result[0]['class_category_id2'];
        $this->verify('class_category_id2 は null');

        // del_flg = 0 の行の確認
        $this->expected = 0;
        $this->actual = $result[1]['del_flg'];
        $this->verify('result[1] は del_flg = 0');

        $this->expected = 3;
        $this->actual = $result[1]['class_category_id1'];
        $this->verify('class_category_id1 は 3');

        $this->expected = 6;
        $this->actual = $result[1]['class_category_id2'];
        $this->verify('class_category_id2 は 6');
    }

    public function testCsvTemplate()
    {
        // 一旦別の変数に代入しないと, config 以下の値を書きかえることができない
        $config = $this->app['config'];
        $config['csv_export_encoding'] = 'UTF-8'; // SJIS だと比較できないので UTF-8 に変更しておく
        $this->app['config'] = $config;

        // ヘッダー取得
        $csv = $this->createCsvAsArray();
        $header = implode(',', $csv[0]);

        // ヘッダー確認用データ作成
        $this->expectOutputString($header."\n");

        // CSVテンプレートファイルのダウンロード
        $this->client->request(
            'GET',
            $this->app->path('yamato_reserve_product_csv_template')
        );

        // ダウンロードが完了していること
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // ダウンロードしたCSVファイル内にヘッダー確認用データの文字列が存在すること
    }
    //---------ヤマト決済プラグイン E N D---------

    /**
     * $this->filepath のファイルを CSV アップロードし, 完了画面の crawler を返す.
     */
    public function scenario($bind = 'yamato_reserve_product_csv_upload', $original_name = 'product.csv')
    {
        $file = new UploadedFile(
            $this->filepath,    // file path
            $original_name,     // original name
            'text/csv',         // mimeType
            null,               // file size
            null,               // error
            true                // test mode
        );

        $crawler = $this->client->request(
            'POST',
            $this->app->path($bind),
            array(
                'admin_csv_import' => array(
                    '_token' => 'dummy',
                    'import_file' => $file
                )
            ),
            array('import_file' => $file)
        );
        return $crawler;
    }

    public function test_createProductCategory_存在しない商品カテゴリIDが設定されている場合_エラーメッセージが返る()
    {
        // 商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][10] = '0';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の商品カテゴリ(ID)「' . $csv[1][10] . '」が存在しません。',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductCategory_数字でない商品カテゴリIDが設定されている場合_エラーメッセージが返る()
    {
        // 商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][10] = 'abc';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の商品カテゴリ(ID)「' . $csv[1][10] . '」が存在しません。',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_商品種別IDが設定されていない場合_エラーメッセージが返る()
    {
        // 商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][11] = '';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の商品種別(ID)が設定されていません',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_存在しない商品種別IDが設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][11] = 0;
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の商品種別(ID)が存在しません',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_数字でない商品種別IDが設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][11] = 'abc';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の商品種別(ID)が存在しません',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_存在しない発送日目安IDが設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][14] = 0;
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の発送日目安(ID)が存在しません',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_数字でない発送日目安IDが設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][14] = 'abc';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の発送日目安(ID)が存在しません',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_在庫数無制限フラグが設定されていない場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][17] = '';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の在庫数無制限フラグが設定されていません',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_在庫数が設定されていない場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][16] = '';

        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の在庫数が設定されていません',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_数字でない在庫数が設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][16] = 'abc';

        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の在庫数は0以上の数値を設定してください',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_在庫数無制限フラグが設定されている場合_ProductClassのStockUnlimitedは1_Stockはnull_ProductStockのStockはnullとなる()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][17] = 1;
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $this->scenario();

        /** @var Product $Product */
        $Product = $this->app['eccube.repository.product']->findOneBy(array('name' => $csv[1][2]));
        /** @var ProductClass $ProductClass */
        $ProductClass = $this->app['eccube.repository.product_class']->findOneBy(array('Product' => $Product));
        // $ProductClass->getStockUnlimited()は1であること
        $this->assertEquals(1, $ProductClass->getStockUnlimited());

        // $ProductClass->getStock()はnullであること
        $this->assertNull($ProductClass->getStock());

        /** @var ProductStock $ProductStock */
        $ProductStock = $this->app['eccube.repository.product_stock']->findOneBy(array('ProductClass' => $ProductClass));
        $this->assertNull($ProductStock->getStock());
    }

    public function test_createProductClass_販売制限数が設定されている場合_ProductClassのSaleLimitが設定される()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][18] = 5;
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $this->scenario();

        /** @var Product $Product */
        $Product = $this->app['eccube.repository.product']->findOneBy(array('name' => $csv[1][2]));
        /** @var ProductClass $ProductClass */
        $ProductClass = $this->app['eccube.repository.product_class']->findOneBy(array('Product' => $Product));
        // $ProductClass->getSaleLimit()は5であること
        $this->assertEquals(5, $ProductClass->getSaleLimit());
    }

    public function test_createProductClass_数字でない販売制限数が設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][18] = 'abc';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の販売制限数は0以上の数値を設定してください',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_数字でない通常価格が設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][19] = 'abc';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の通常価格は0以上の数値を設定してください',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_販売価格が設定されていない場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][20] = '';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の販売価格が設定されていません',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_数字でない販売価格が設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][20] = 'abc';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の販売価格は0以上の数値を設定してください',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_数字でない送料が設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][21] = 'abc';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の送料は0以上の数値を設定してください',
            $crawler->filter('div.text-danger')->text());
    }

    public function test_createProductClass_商品規格削除フラグが設定されていない場合_ProductClassのdelFlgが設定される()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][22] = '';
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $this->scenario();

        /** @var Product $Product */
        $Product = $this->app['eccube.repository.product']->findOneBy(array('name' => $csv[1][2]));
        /** @var ProductClass $ProductClass */
        $ProductClass = $this->app['eccube.repository.product_class']->findOneBy(array('Product' => $Product));
        // $ProductClass->getDelFlg()は0であること
        $this->assertEquals(0, $ProductClass->getDelFlg());
    }

    public function test_createProductClass_0か1以外の商品規格削除フラグが設定されている場合_エラーメッセージが返る()
    {
        // 3商品生成
        $csv = $this->createCsvAsArray();
        $csv[1][22] = 2;
        $csv = array_merge($csv, $this->createCsvAsArray(false));

        // 規格1のみの商品
        $csvClass1Only = $this->createCsvAsArray(false);
        $csvClass1Only[0][13] = null; // 規格分類2(ID)
        $csvClass1Only[0][15] = 'class1-only'; // 商品コード
        $csv = array_merge($csv, $csvClass1Only);

        // 生成した商品をCSVファイルに追加
        $this->filepath = $this->createCsvFromArray($csv);

        // CSVファイルのアップロード
        $crawler = $this->scenario();

        // 適切なエラーメッセージが取得できること
        $this->assertContains('行目の商品規格削除フラグが設定されていません',
            $crawler->filter('div.text-danger')->text());
    }
}
