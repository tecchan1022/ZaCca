<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller\Admin;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Entity\Master\CsvType;
use Eccube\Service\CsvExportService;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eccube\Controller\Admin\Order\OrderControllerを上書き
 * 修正箇所は「ヤマト決済プラグイン」で検索してください。
 */
class OrderControllerExtension extends AbstractController
{
    /**
     * 受注CSVの出力.
     * 既存のOrderController::exportOrderを上書き
     *
     * @param Application $app
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportOrder(Application $app, Request $request)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $app['orm.em'];
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($app, $request) {

            // CSV種別を元に初期化.
            $app['eccube.service.csv.export']->initCsvType(CsvType::CSV_TYPE_ORDER);

            // ヘッダ行の出力.
            $app['eccube.service.csv.export']->exportHeader();

            // 受注データ検索用のクエリビルダを取得.
            $qb = $app['eccube.service.csv.export']
                ->getOrderQueryBuilder($request);

            // データ行の出力.
            $app['eccube.service.csv.export']->setExportQueryBuilder($qb);
            $app['eccube.service.csv.export']->exportData(function ($entity, $csvService) use ($app){
                /** @var CsvExportService $csvService  */
                $Csvs = $csvService->getCsvs();

                /** @var Order $Order */
                $Order = $entity;
                $OrderDetails = $Order->getOrderDetails();

                //---------ヤマト決済プラグイン START---------
                $YamatoOrderScheduledShippingDate = $app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($Order->getId());
                //---------ヤマト決済プラグイン E N D---------

                foreach ($OrderDetails as $OrderDetail) {
                    $row = array();

                    // CSV出力項目と合致するデータを取得.
                    foreach ($Csvs as $Csv) {
                        // 受注データを検索.
                        $data = $csvService->getData($Csv, $Order);
                        if (is_null($data)) {
                            // 受注データにない場合は, 受注明細を検索.
                            $data = $csvService->getData($Csv, $OrderDetail);
                            //---------ヤマト決済プラグイン START---------
                            if (is_null($data)) {
                                $data = $csvService->getData($Csv, $YamatoOrderScheduledShippingDate);
                            }
                            //---------ヤマト決済プラグイン E N D---------
                        }
                        $row[] = $data;

                    }

                    // 出力.
                    $csvService->fputcsv($row);
                }
            });
        });

        $now = new \DateTime();
        $filename = 'order_' . $now->format('YmdHis') . '.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return $response;
    }

    //---------ヤマト決済プラグイン START---------
    /**
     * クレジットカード出荷登録CSV のダウンロード
     *
     * @param Application $app
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportWebCollect(Application $app, Request $request)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $app['orm.em'];
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($app, $request) {

            // 受注データ検索用のクエリビルダを取得.
            $qb = $app['eccube.service.csv.export']->getOrderQueryBuilder($request);

            // 検索条件に支払方法が含まれている場合のバッティング回避
            if (is_null($qb->getParameter('payments'))) {
                $qb->leftJoin('o.Payment', 'p');
            }

            // 条件追加（支払方法がWEBコレのクレジット決済）
            /** @var YamatoPaymentMethod $YamatoPaymentMethod */
            $YamatoPaymentMethod = $app['yamato_payment.repository.yamato_payment_method']
                ->findOneBy(array('memo03' => $app['config']['YamatoPayment']['const']['YAMATO_PAYID_CREDIT']));

            $qb
                ->andWhere($qb->expr()->in('p.id', ':credit_id'))
                ->setParameter('credit_id', $YamatoPaymentMethod->getId());

            // 条件追加（注文ステータスが発送済み・クレジットカード出荷登録済み以外）
            $qb
                ->andWhere('o.OrderStatus NOT IN (:OrderStatuses)')
                ->setParameter('OrderStatuses', array(
                    $app['config']['order_deliv'],
                    $app['config']['YamatoPayment']['const']['ORDER_SHIPPING_REGISTERED']
                ));

            // 条件追加（送り状番号が登録されている）
            $qb
                ->leftJoin('\Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip', 'y', 'WITH', 'o.id = y.order_id')
                ->andWhere($qb->expr()->isNotNull('y.deliv_slip_number'));

            // データ行の出力.
            $app['eccube.service.csv.export']->setExportQueryBuilder($qb);
            $app['eccube.service.csv.export']->exportData(function ($entity, $csvService) use ($app) {

                /** @var Order $Order */
                $Order = $entity;
                $Shippings = $Order->getShippings();

                // プラグイン設定情報を取得
                $b2SubData = $app['yamato_payment.util.plugin']->getB2UserSettings();

                foreach ($Shippings as $Shipping) {
                    /** @var Shipping $Shipping */
                    $row = array();

                    // 送り状番号を取得
                    /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
                    $YamatoShippingDelivSlip = $app['yamato_payment.repository.yamato_shipping_deliv_slip']
                        ->find($Shipping->getId());

                    // 配送業者IDを取得
                    $delivery_id = null;
                    if (!is_null($Shipping->getDelivery())) {
                        $delivery_id = $Shipping->getDelivery()->getId();
                    }

                    $row[] = $Order->getId();
                    $row[] = $YamatoShippingDelivSlip->getDelivSlipNumber();
                    if (!is_null($b2SubData) && isset($b2SubData['delivery_service_code'][$delivery_id])) {
                        $row[] = $b2SubData['delivery_service_code'][$delivery_id];
                    } else {
                        $row[] = '';
                    }
                    /** @var CsvExportService $csvService */
                    $csvService->fputcsv($row);
                    // 複数配送は1件のみ出力
                    break;
                }

                // 受注情報を再取得
                $order_id = $Order->getId();
                $Order = $app['eccube.repository.order']->find($order_id);

                // 対応状況を「クレジットカード出荷登録済み」に変更する
                $OrderStatus = $app['eccube.repository.order_status']
                    ->find($app['config']['YamatoPayment']['const']['ORDER_SHIPPING_REGISTERED']);
                $Order->setOrderStatus($OrderStatus);

                $app['orm.em']->persist($Order);
                $app['orm.em']->flush($Order);
            });
        });

        $now = new \DateTime();
        $filename = 'web_collect_' . $now->format('YmdHis') . '.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return $response;
    }

    /**
     * B2 CSV のダウンロード
     *
     * @param Application $app
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportB2(Application $app, Request $request)
    {
        // プラグイン設定情報を取得
        $b2SubData = $app['yamato_payment.util.plugin']->getB2UserSettings();
        if (is_null($b2SubData)) {
            $error_title = 'B2設定情報未登録エラー';
            $error_message = "プラグイン設定画面から ｢B2設定｣ を登録してください。";
            return $app['view']->render('error.twig', array(
                'error_message' => $error_message, 'error_title' => $error_title
            ));
        }

        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $app['orm.em'];
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($app, $request) {

            // プラグイン設定情報を取得
            $b2SubData = $app['yamato_payment.util.plugin']->getB2UserSettings();

            // ヘッダ行の出力.
            if (isset($b2SubData['header_output']) && $b2SubData['header_output'] == 1) {
                $app['yamato_payment.service.csv.export']->exportB2CsvHeader();
            }

            // 受注データ検索用のクエリビルダを取得.
            $qb = $app['eccube.service.csv.export']->getOrderQueryBuilder($request);

            // データ行の出力.
            $app['eccube.service.csv.export']->setExportQueryBuilder($qb);
            $app['eccube.service.csv.export']->exportData(function ($entity, $csvService) use ($app) {

                /** @var Order $Order */
                $Order = $entity;
                $Shippings = $Order->getShippings();

                foreach ($Shippings as $Shipping) {
                    // B2 CSV出力データ作成
                    $row = $app['yamato_payment.service.csv.export']->createB2CsvData($Order, $Shipping);
                    /** @var CsvExportService $csvService */
                    $csvService->fputcsv($row);
                }
            });
        });

        $now = new \DateTime();
        $filename = 'yamato_b2_' . $now->format('YmdHis') . '.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return $response;
    }
    //---------ヤマト決済プラグイン E N D---------

}
