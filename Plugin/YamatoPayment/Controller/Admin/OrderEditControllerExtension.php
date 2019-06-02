<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller\Admin;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderEditControllerExtension extends AbstractController
{
    /**
     * 買手情報一括登録CSV のダウンロード
     *
     * @param Application $app
     * @param Request $request
     * @param integer $id 受注ID
     * @return StreamedResponse
     */
    public function exportBuyer(Application $app, Request $request, $id = null)
    {
        /** @var Order $Order */
        $Order = $app['eccube.repository.order']->find($id);
        if (is_null($Order)) {
            throw new NotFoundHttpException();
        }

        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $app['orm.em'];
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($app, $request, $Order) {

            // ヘッダ行の出力.
            $app['yamato_payment.service.csv.export']->exportBuyerCsvHeader();

            // データ行の出力.
            $row = $app['yamato_payment.service.csv.export']->createBuyerCsvData($Order);

            $app['eccube.service.csv.export']->fopen();
            $app['eccube.service.csv.export']->fputcsv($row);
            $app['eccube.service.csv.export']->fclose();
        });

        $now = new \DateTime();
        $filename = 'deferred_buyer_' . $now->format('YmdHis') . '.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return $response;
    }

}
