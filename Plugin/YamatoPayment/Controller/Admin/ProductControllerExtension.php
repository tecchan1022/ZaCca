<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller\Admin;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Product;
use Eccube\Service\CsvExportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductControllerExtension extends AbstractController
{
    /**
     * 商品CSVの出力.
     * Eccube本体のAdmin\Product\ProductController::exportをコピーして改修
     *
     * @param Application $app
     * @param Request $request
     * @return StreamedResponse
     */
    public function export(Application $app, Request $request)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $app['orm.em'];
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($app, $request) {

            // CSV種別を元に初期化.
            $app['eccube.service.csv.export']->initCsvType(CsvType::CSV_TYPE_PRODUCT);

            // ヘッダ行の出力.
            $app['eccube.service.csv.export']->exportHeader();

            // 商品データ検索用のクエリビルダを取得.
            $qb = $app['eccube.service.csv.export']
                ->getProductQueryBuilder($request);

            // joinする場合はiterateが使えないため, select句をdistinctする.
            // http://qiita.com/suin/items/2b1e98105fa3ef89beb7
            // distinctのmysqlとpgsqlの挙動をあわせる.
            // http://uedatakeshi.blogspot.jp/2010/04/distinct-oeder-by-postgresmysql.html
            $qb->resetDQLPart('select')
                ->resetDQLPart('orderBy')
                ->select('p')
                ->orderBy('p.update_date', 'DESC')
                ->distinct();

            // データ行の出力.
            $app['eccube.service.csv.export']->setExportQueryBuilder($qb);
            //---------ヤマト決済プラグイン START---------
            $app['eccube.service.csv.export']->exportData(function ($entity, $csvService) use ($app) {
            //---------ヤマト決済プラグイン E N D---------

                /** @var CsvExportService $csvService */
                $Csvs = $csvService->getCsvs();

                /** @var Product $Product */
                $Product = $entity;

                //---------ヤマト決済プラグイン START---------
                // 追加商品項目情報を取得
                $YamatoProduct = $app['yamato_payment.repository.yamato_product']->find($Product->getId());
                //---------ヤマト決済プラグイン E N D---------

                $ProductClassess = $Product->getProductClasses();

                foreach ($ProductClassess as $ProductClass) {
                    $row = array();

                    // CSV出力項目と合致するデータを取得.
                    foreach ($Csvs as $Csv) {
                        // 商品データを検索.
                        $data = $csvService->getData($Csv, $Product);
                        if (is_null($data)) {
                            // 商品規格情報を検索.
                            $data = $csvService->getData($Csv, $ProductClass);
                        }
                        //---------ヤマト決済プラグイン START---------
                        if (is_null($data)) {
                            // 追加商品項目情報を検索
                            $data = $csvService->getData($Csv, $YamatoProduct);
                        }
                        // 後払い不可フラグの変換
                        if($Csv->getFieldName() == 'not_deferred_flg'){
                                $data = ($data) ? 1 : 0;
                        }
                        //---------ヤマト決済プラグイン E N D---------
                        $row[] = $data;
                    }

                    //$row[] = number_format(memory_get_usage(true));
                    // 出力.
                    $csvService->fputcsv($row);
                }
            });
        });

        $now = new \DateTime();
        $filename = 'product_' . $now->format('YmdHis') . '.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return $response;
    }

}
