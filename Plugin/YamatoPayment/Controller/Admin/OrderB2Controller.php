<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller\Admin;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Exception\CsvImportException;
use Eccube\Service\CsvImportService;
use Eccube\Util\Str;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Plugin\YamatoPayment\Util\CommonUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManager;

class OrderB2Controller extends AbstractController
{
    protected $title;
    protected $subtitle;

    private $errors = array();
    private $fileName;
    /** @var EntityManager */
    private $em;
    private $b2Twig = 'YamatoPayment/Resource/template/admin/Order/order_b2_csv_upload.twig';

    public $listCreditOrderId = array();
    public $listDeferredOrderId = array();
    public $listRowShipmentEntryReport;

    public function __construct()
    {
        $this->title = '受注管理';
        $this->subtitle = '送り状番号登録';
    }

    public function csvB2(Application $app, Request $request)
    {
        $b2UserSettings = $app['yamato_payment.util.plugin']->getB2UserSettings();

        $form = $app['form.factory']->createBuilder('admin_csv_import')->getForm();

        $headers = $this->getB2CsvHeader($app);

        if ('POST' === $request->getMethod()) {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $formFile = $form->get('import_file')->getData();

                if (!empty($formFile)) {

                    $data = $this->getImportData($app, $formFile);
                    $size = count($data);
                    if ($size < 1) {
                        $this->addErrors('CSVデータが存在しません。');
                        return $this->render($app, $form, $headers, $this->b2Twig);
                    }
                    $this->em = $app['orm.em'];
                    $this->em->getConfiguration()->setSQLLogger(null);
                    $this->em->getConnection()->beginTransaction();

                    // 登録対象の列数
                    $col_max_count = count($headers);
                    // 行数
                    $line_count = 0;

                    $pluginUtil = $app['yamato_payment.util.plugin'];

                    // CSVファイルの登録処理
                    foreach ($data as $row) {

                        // 行カウント
                        $line_count++;
                        // ヘッダ行はスキップ
                        if ($line_count == 1 && $b2UserSettings['header_output'] == 1) {
                            continue;
                        }
                        // 空行はスキップ
                        if (empty($row)) {
                            continue;
                        }

                        // 取り込みフォーマットが ｢95項目以上｣ の場合、列数が94個以下の場合エラー
                        // 取り込みフォーマットが ｢2項目｣ の場合、列数が異なる場合エラー
                        $col_count = count($row);
                        if (($b2UserSettings['use_b2_format'] == '1' && $col_count < 95 ) ||
                            ($b2UserSettings['use_b2_format'] != '1' && $col_max_count != $col_count)
                        ) {
                            $this->addErrors($line_count . '行目：CSVのフォーマットが一致しません。');
                            return $this->render($app, $form, $headers, $this->b2Twig);
                        }

                        // 配列を格納する
                        if ($b2UserSettings['use_b2_format'] == '1') {
                            $row = array(
                                '注文番号_発送情報ID' => $row[0],
                                '送り状番号' => $row[3],
                            );
                        } else {
                            $row = array(
                                '注文番号_発送情報ID' => $row[0],
                                '送り状番号' => $row[1],
                            );
                        }

                        // 注文番号_発送情報IDを分解
                        if (!Str::isBlank($row['注文番号_発送情報ID'])) {
                            list($order_id, $shipping_id) = explode('_', $row['注文番号_発送情報ID']);
                            if (Str::isBlank($order_id) || Str::isBlank($shipping_id)) {
                                $this->addErrors(($data->key() + 1) . '行目の注文番号_発送情報IDが不正です。');
                                return $this->render($app, $form, $headers, $this->b2Twig);
                            }
                            if (!CommonUtil::isInt($order_id) || !CommonUtil::isInt($shipping_id)) {
                                $this->addErrors(($data->key() + 1) . '行目の注文番号_発送情報IDが不正です。');
                                return $this->render($app, $form, $headers, $this->b2Twig);
                            }

                            // 配送情報存在チェック（注文番号_発送情報IDが問題ない場合のみ）
                            $Shipping = $app['eccube.repository.shipping']->find($shipping_id);
                            if (is_null($Shipping)) {
                                $this->addErrors(($data->key() + 1) . '行目の注文番号_発送情報ID(' . $row['注文番号_発送情報ID'] . ')が存在しません。');
                                return $this->render($app, $form, $headers, $this->b2Twig);
                            }

                            if ($Shipping->getOrder()->getId() != $order_id || $Shipping->getId() != $shipping_id) {
                                $this->addErrors(($data->key() + 1) . '行目の注文番号_発送情報ID(' . $row['注文番号_発送情報ID'] . ')が存在しません。');
                                return $this->render($app, $form, $headers, $this->b2Twig);
                            }
                        } else {
                            $this->addErrors(($data->key() + 1) . '行目の注文番号_発送情報IDが設定されていません。');
                            return $this->render($app, $form, $headers, $this->b2Twig);
                        }

                        $deliveryServiceCode = $pluginUtil->getDeliveryServiceCode($order_id);

                        // 送り状番号の有効性チェック
                        if (!Str::isBlank($row['送り状番号'])) {
                            if (!CommonUtil::checkDelivSlip(intval($row['送り状番号'])) && $deliveryServiceCode == '00' ) {
                                $this->addErrors(($data->key() + 1) . '行目の送り状番号が不正です。'.$row['送り状番号'].$deliveryServiceCode);
                                return $this->render($app, $form, $headers, $this->b2Twig);
                            }
                        } else {
                            $this->addErrors(($data->key() + 1) . '行目の送り状番号が設定されていません。');
                            return $this->render($app, $form, $headers, $this->b2Twig);
                        }

                        // 伝票番号登録を行う
                        list($order_id, $shipping_id) = explode('_', $row['注文番号_発送情報ID']);
                        $YamatoShippingDelivSlip = $app['yamato_payment.repository.yamato_shipping_deliv_slip']->find($shipping_id);
                        if (is_null($YamatoShippingDelivSlip)) {
                            $YamatoShippingDelivSlip = new YamatoShippingDelivSlip();
                        }
                        $YamatoShippingDelivSlip->setId($shipping_id);
                        $YamatoShippingDelivSlip->setOrderId($order_id);
                        $YamatoShippingDelivSlip->setDelivSlipNumber($row['送り状番号']);
                        $YamatoShippingDelivSlip->setLastDelivSlipNumber($YamatoShippingDelivSlip->getLastDelivSlipNumber());

                        $this->em->persist($YamatoShippingDelivSlip);
                        $this->em->flush();

                        // 出荷情報登録リストに追加する
                        $this->addOrderId($order_id, $app);
                    }

                    $result = true;
                    if ($b2UserSettings['shpping_info_regist'] == '1') {

                        //出荷情報登録前確認処理
                        $listErrShipment = $this->checkErrorShipmentEntry($app);
                        if (count($listErrShipment) > 0) {
                            foreach ($listErrShipment as $message) {
                                $this->addErrors($message);
                            }
                            return $this->render($app, $form, $headers, $this->b2Twig);
                        }

                        //出荷情報登録処理
                        $result = $this->doShipmentEntry($app);
                    }

                    $this->em->getConnection()->commit();

                    if ($result) {
                        $app->addSuccess('送り状番号CSVファイルをアップロードしました。', 'admin');
                    }
                }
            }
        }
        return $this->render($app, $form, $headers, $this->b2Twig);
    }

    /**
     * 出荷情報リストに注文IDを追加する
     *
     * @param integer $orderId
     * @param Application $app
     */
    public function addOrderId($orderId, $app)
    {
        $paymentUtil = $app['yamato_payment.util.payment'];

        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $app['yamato_payment.repository.yamato_order_payment']
            ->find($orderId);

        //クレジット決済、かつ保持していない場合
        if ($paymentUtil->isCreditOrder($YamatoOrderPayment)
            && !in_array($orderId, $this->listCreditOrderId))
        {
            $this->listCreditOrderId[] = $orderId;
        }

        //後払い決済、かつ保持していない場合
        if ($paymentUtil->isDeferredOrder($YamatoOrderPayment)
            && !in_array($orderId, $this->listDeferredOrderId))
        {
            $this->listDeferredOrderId[] = $orderId;
        }
    }

    /**
     * 決済情報登録を行う.
     *
     * @param Application $app
     * @return bool
     */
    public function doShipmentEntry($app)
    {
        $result = true;
        //クレジット決済及びクロネコ代金後払い決済が1件もない場合は空の配列を返す
        if (count($this->listCreditOrderId) == 0 && count($this->listDeferredOrderId) == 0) {
            return $result;
        }
        //決済情報登録
        foreach ($this->listCreditOrderId as $order_id) {

            //決済クライアント生成
            $objClient = $app['yamato_payment.service.client.util'];
            $orderExtension = $app['yamato_payment.util.payment']->getOrderPayData($order_id);
            list($ret, $listSuccessSlip) = $objClient->doShipmentEntry($orderExtension);

            if ($ret) {
                $this->addRowShipmentEntryReport($order_id, '出荷情報登録成功しました。');
            } else {
                $listErr = $objClient->getError();
                $this->addRowShipmentEntryReport($order_id, implode(' / ', $listErr));
                //複数配送時出荷情報登録ロールバック
                $objClient->doShipmentRollback($orderExtension, $listSuccessSlip);
                $result = false;
            }
        }

        foreach ($this->listDeferredOrderId as $order_id) {

            //決済クライアント生成
            $objClient = $app['yamato_payment.service.client.deferred_util'];
            $orderExtension = $app['yamato_payment.util.payment']->getOrderPayData($order_id);
            list($ret, $success_cnt, $failure_cnt) = $objClient->doShipmentEntry($orderExtension);

            $mess = '出荷情報登録 成功' . $success_cnt . '件 失敗' . $failure_cnt . '件';
            if ($ret) {
                $this->addRowShipmentEntryReport($order_id, '出荷情報登録成功しました。 / ' . $mess);
            } else {
                $listErr = $objClient->getError();
                $this->addRowShipmentEntryReport($order_id, implode(' / ', $listErr) . ' / ' . $mess);
                $result = false;
            }
        }

        return $result;
    }

    /**
     * 出荷情報登録結果のメッセージをプロパティへ追加する
     *
     * @param  integer $order_id
     * @param  string $message
     * @return void
     */
    public function addRowShipmentEntryReport($order_id, $message)
    {
        $this->listRowShipmentEntryReport[] = '注文番号：' . $order_id . ' 処理結果：' . $message;
    }

    /**
     * 決済情報登録前入力チェックを行う.
     *
     * @param Application $app
     * @return array
     */
    public function checkErrorShipmentEntry(Application $app)
    {
        $listErr = array();
        $paymentUtil = $app['yamato_payment.util.payment'];

        //クレジット決済及びクロネコ代金後払い決済が1件もない場合は空の配列を返す
        if (count($this->listCreditOrderId) == 0 && count($this->listDeferredOrderId) == 0) {
            return array();
        }

        // クレジット決済
        foreach ($this->listCreditOrderId as $orderId) {
            // 出荷情報登録エラーチェック
            $errorMsg = $paymentUtil->checkErrorShipmentEntryForCredit($orderId);
            if (!empty($errorMsg)) {
                $listErr[] = "注文番号:{$orderId} " . $errorMsg;
                continue;
            }
        }

        // 後払い決済
        foreach ($this->listDeferredOrderId as $orderId) {
            // 出荷情報登録エラーチェック
            $errorMsg = $paymentUtil->checkErrorShipmentEntryForDeferred($orderId);
            if (!empty($errorMsg)) {
                $listErr[] = "注文番号:{$orderId} " . $errorMsg;
                continue;
            }
        }

        return $listErr;
    }

    /**
     * 登録、更新時のエラー画面表示
     *
     * @param Application $app
     * @param Form $form
     * @param string $headers
     * @param string $twig csv_product.twig
     * @return string エラー画面表示
     */
    protected function render($app, $form, $headers, $twig)
    {
        if ($this->hasErrors()) {
            if ($this->em) {
                $this->em->getConnection()->rollback();
            }
        }

        if (!empty($this->fileName)) {
            try {
                $fs = new Filesystem();
                $fs->remove($app['config']['csv_temp_realdir'] . '/' . $this->fileName);
            } catch (\Exception $e) {
                // エラーが発生しても無視する
            }
        }

        return $app->render($twig, array(
            'form' => $form->createView(),
            'headers' => $headers,
            'errors' => $this->errors,
            'listRowShipmentEntryReport' => $this->listRowShipmentEntryReport,
        ));
    }

    /**
     * @return array
     */
    protected function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return boolean
     */
    protected function hasErrors()
    {
        return count($this->getErrors()) > 0;
    }

    /**
     * アップロードされたCSVファイルの行ごとの処理
     *
     * @param Application $app
     * @param UploadedFile $formFile
     * @return CsvImportService
     */
    protected function getImportData($app, $formFile)
    {
        // アップロードされたCSVファイルを一時ディレクトリに保存
        $this->fileName = 'upload_' . Str::random() . '.' . $formFile->getClientOriginalExtension();
        $formFile->move($app['config']['csv_temp_realdir'], $this->fileName);

        $file = file_get_contents($app['config']['csv_temp_realdir'] . '/' . $this->fileName);
        // アップロードされたファイルがUTF-8以外は文字コード変換を行う
        $encode = Str::characterEncoding(substr($file, 0, 6));
        if ($encode != 'UTF-8') {
            $file = mb_convert_encoding($file, 'UTF-8', $encode);
        }
        $file = Str::convertLineFeed($file);

        $tmp = tmpfile();
        fwrite($tmp, $file);
        rewind($tmp);
        $meta = stream_get_meta_data($tmp);
        $file = new \SplFileObject($meta['uri']);

        set_time_limit(0);

        // アップロードされたCSVファイルを行ごとに取得
        $data = new CsvImportService($file, $app['config']['csv_import_delimiter'], $app['config']['csv_import_enclosure']);

        return $data;
    }

    /**
     * アップロード用CSV雛形ファイルダウンロード
     *
     * @param Application $app
     * @param Request $request
     * @param $type
     * @return StreamedResponse
     * @throws NotFoundHttpException
     */
    public function csvTemplate(Application $app, Request $request, $type)
    {
        set_time_limit(0);

        $response = new StreamedResponse();

        if ($type == 'b2') {
            $headers = $this->getB2CsvHeader($app);
            $filename = 'order_b2.csv';
        } else {
            throw new NotFoundHttpException();
        }

        $response->setCallback(function () use ($app, $request, $headers) {

            // ヘッダ行の出力
            $row = array();
            foreach ($headers as $key => $value) {
                $row[] = mb_convert_encoding($key, $app['config']['csv_export_encoding'], 'UTF-8');
            }

            $fp = fopen('php://output', 'w');
            fputcsv($fp, $row, $app['config']['csv_export_separator']);
            fclose($fp);

        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return $response;
    }

    /**
     * 送り状番号登録CSVヘッダー定義
     *
     * @param Application $app
     * @return array
     */
    private function getB2CsvHeader($app)
    {
        $b2UserSettings = $app['yamato_payment.util.plugin']
            ->getB2UserSettings();

        // 2項目
        if ($b2UserSettings['use_b2_format'] == '0') {
            return array(
                '注文番号_発送情報ID' => 'order_shipping_id',
                '送り状番号' => 'plg_yfcapi_deliv_slip',
            );
        }

        return array(
            '注文番号_発送情報ID' => 'order_shipping_id',
            '送り状種別' => 'plg_yfcapi_deliv_slip_type',
            'クール区分' => 'plg_yfcapi_cool_kb',
            '送り状番号' => 'plg_yfcapi_deliv_slip',
            '出荷予定日' => 'deliv_date',
            'お届け予定' => 'shipping_date',
            '配達時間帯' => 'plg_yfcapi_deliv_time_code',
            'お届け先コード' => 'plg_yfcapi_deliv_code',
            'お届け先電話番号' => 'shipping_tel',
            'お届け先電話番号枝番' => 'shipping_tel_no',
            'お届け先郵便番号' => 'shipping_zip',
            'お届け先住所' => 'shipping_addr01',
            'お届け先住所（アパートマンション名）' => 'shipping_addr02',
            'お届け先会社・部門名１' => 'shipping_company_name',
            'お届け先会社・部門名２' => 'shipping_company_name02',
            'お届け先名' => 'shipping_name',
            'お届け先名称略カナ' => 'shipping_kana',
            '敬称' => 'shipping_title',
            'ご依頼主コード' => 'order_code',
            'ご依頼主電話番号' => 'order_tel',
            'ご依頼主電話番号枝番' => 'order_tel_no',
            'ご依頼主郵便番号' => 'order_zip',
            'ご依頼主住所' => 'order_addr01',
            'ご依頼主住所（アパートマンション名）' => 'order_addr02',
            'ご依頼主名' => 'order_name',
            'ご依頼主略称カナ' => 'order_kana',
            '品名コード１' => 'shipping_product_code01',
            '品名１' => 'shipping_product_name01',
            '品名コード２' => 'shipping_product_code02',
            '品名２' => 'shipping_product_code02',
            '荷扱い１' => 'shipping_handling01',
            '荷扱い２' => 'shipping_handling02',
            '記事' => 'shipping_memo',
            'コレクト代金引換額（税込）' => 'shipping_collect_inctax',
            'コレクト内消費税額等' => 'shipping_collect_tax',
            '営業所止置き' => 'shipping_hold_reassign',
            '営業所コード' => 'shipping_hold_office_code',
            '発行枚数' => 'publish_count',
            '個数口枠の印字' => 'publish_koguchi',
            'ご請求先顧客コード' => 'plg_yfcapi_claim_customer_code',
            'ご請求先分類コード' => 'plg_yfcapi_claim_type_code',
            '運賃管理番号' => 'plg_yfcapi_transportation_no',
            '注文時カード払いデータ登録' => 'service_card_register',
            '注文時カード払い加盟店番号' => 'service_card_shop_no',
            '注文時カード払い申込受付番号１' => 'service_card_order_no01',
            '注文時カード払い申込受付番号２' => 'service_card_order_no02',
            '注文時カード払い申込受付番号３' => 'service_card_order_no03',
            'お届け予定eメール利用区分' => 'service_deliv_mail_enable',
            'お届け予定eメールe-mailアドレス' => 'service_deliv_mail_address',
            '入力機種' => 'service_deliv_device_id',
            'お届け予定eメールメッセージ' => 'service_deliv_mail_message',
            'お届け完了eメール利用区分' => 'service_complete_mail_enable',
            'お届け完了eメールe-mailアドレス' => 'service_complete_mail_address',
            'お届け完了eメールメッセージ' => 'service_complete_mail_message',
            'クロネコ収納代行利用区分' => 'service_receiving_agent_enable',
            '予備' => 'service_receiving_agent_yobi',
            '収納代行請求金額（税込）' => 'service_receiving_agent_claim_payment_total',
            '収納代行内消費税額等' => 'service_receiving_agent_claim_tax',
            '収納代行請求先郵便番号' => 'service_receiving_agent_zip',
            '収納代行請求先住所' => 'service_receiving_agent_addr01',
            '収納代行請求先住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名)' => 'service_receiving_agent_addr02',
            '収納代行請求先会社･部門名１' => 'service_receiving_agent_claim_campany01',
            '収納代行請求先会社･部門名２' => 'service_receiving_agent_claim_campany02',
            '収納代行請求先名（漢字）' => 'service_receiving_agent_claim_name',
            '収納代行請求先名（カナ）' => 'service_receiving_agent_claim_kana',
            '収納代行問合せ先名（カナ）' => 'service_receiving_agent_info_kana',
            '収納代行問合せ先郵便番号' => 'service_receiving_agent_info_zip',
            '収納代行問合せ先住所' => 'service_receiving_agent_info_addr01',
            '収納代行問合せ先住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）' => 'service_receiving_agent_info_addr02',
            '収納代行問合せ先電話番号' => 'service_receiving_agent_info_tel',
            '収納代行管理番号' => 'service_receiving_agent_no',
            '収納代行品名' => 'service_receiving_agent_product_name',
            '収納代行備考' => 'service_receiving_agent_memo',
            '予備０１' => 'reserve1"',
            '予備０２' => 'reserve2"',
            '予備０３' => 'reserve3"',
            '予備０４' => 'reserve4"',
            '予備０５' => 'reserve5"',
            '予備０６' => 'reserve6"',
            '予備０７' => 'reserve7"',
            '予備０８' => 'reserve8"',
            '予備０９' => 'reserve9"',
            '予備１０' => 'reserve10',
            '予備１１' => 'reserve11',
            '予備１２' => 'reserve12',
            '予備１３' => 'reserve13',
            '投函予定メール利用区分' => 'posting_plan_mail_enable',
            '投函予定メールe-mailアドレス' => 'posting_plan_mail_address',
            '投函予定メールメッセージ' => 'posting_plan_mail_message',
            '投函完了メール(受人宛て)利用区分' => 'posting_complete_deliv_mail_enable',
            '投函完了メール(受人宛て)e-mailアドレス' => 'posting_complete_deliv_mail_address',
            '投函完了メール(受人宛て)メッセージ' => 'posting_complete_deliv_mail_message',
            '投函完了メール(出人宛て)利用区分' => 'posting_complete_order_mail_enable',
            '投函完了メール(出人宛て)e-mailアドレス' => 'posting_complete_order_mail_address',
            '投函完了メール(出人宛て)メッセージ' => 'posting_complete_order_mail_message',
            '連携管理番号' => 'api_control_no',
            '通知メールアドレス' => 'notification_mail_address',
        );
    }

    /**
     * 登録、更新時のエラー画面表示
     *
     * @param string $message
     */
    protected function addErrors($message)
    {
        $e = new CsvImportException($message);
        $this->errors[] = $e;
    }
}
