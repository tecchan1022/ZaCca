<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Controller;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Form\Type\PaymentRecvType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * 決済モジュール 結果受信クラス
 */
class PaymentRecvController extends AbstractController
{
    /**
     * Process for PaymentRecv, send email for admin in case have error.
     * @param Application $app
     * @param Request $request
     * @return Response
     */
    public function index(Application $app, Request $request)
    {
        $const = $app['config']['YamatoPayment']['const'];
        $paymentUtil = $app['yamato_payment.util.payment'];
        $pluginUtil = $app['yamato_payment.util.plugin'];

        // IP制限チェック
        $allowHost = $const['RECV_ALLOW_HOST'];
        if (array_search($request->getClientIp(), (array)$allowHost) === false) {
            throw new AccessDeniedHttpException();
        }

        // POST値ログ出力
        $this->printPostLog($request->request->all(), $app);

        $res = false;
        if ($request->getMethod() === 'POST') {

            $form = $app['form.factory']
                ->createBuilder(new PaymentRecvType($app))
                ->getForm();
            $form->handleRequest($request);

            $data = $form->getData();

            if ($form->isValid()) {
                //注文情報取得
                $OrderExtension = $paymentUtil->getOrderPayData($data['order_no']);
                //レシーブ処理
                $res = $this->doReceive($data, $OrderExtension, $app);
            } else {
                // エラーメッセージ取得
                $errors = array();
                foreach ($form->getErrors(true) as $key => $error) {
                    $errors[] = $error->getMessage();
                }

                //ログ出力
                $pluginUtil->printErrorLog('param_error_all:' . print_r($errors, true));

                //エラーメール送信（受注データ不在）
                if (!$form->get('order_no')->isValid()) {
                    $this->doNoOrder($data, $app);
                } else {
                    //注文情報取得
                    $OrderExtension = $paymentUtil->getOrderPayData(
                        $form->get('order_no')->getData()
                    );
                    //エラーメール送信（決済未使用）
                    if (!$form->get('function_div')->isValid()) {
                        $this->doNoFunctionDiv($data, $app);
                    }
                    //エラーメール送信（支払方法不一致）
                    if (!$form->get('settle_method')->isValid()) {
                        $this->doUnMatchPayMethod($data, $OrderExtension, $app);
                    }
                    //エラーメール送信（決済金額不一致）
                    if (!$form->get('settle_price')->isValid()) {
                        $this->doUnMatchSettlePrice($data, $OrderExtension, $app);
                    }
                }
            }
        } else {
            $pluginUtil->printErrorLog('error GETアクセスは許可されていません。');
        }
        // TODO Responseオブジェクトを返さないとExceptionが発生するが、gmoはechoしている（※要確認）
        return $this->sendResponse($res, $app);
    }

    /**
     * レシーブ処理
     *
     * @param array $formData POST値
     * @param OrderExtension $OrderExtension
     * @param Application $app
     * @return bool
     */
    protected function doReceive(&$formData, OrderExtension $OrderExtension, $app)
    {
        $const = $app['config']['YamatoPayment']['const'];
        $paymentUtil = $app['yamato_payment.util.payment'];

        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        $memo03 = $YamatoOrderPayment->getMemo03();

        switch ($memo03) {
            //コンビニ決済
            case $const['YAMATO_PAYID_CVS']:
                $res = $this->doRecvCvs($formData, $OrderExtension->getOrder(), $app);
                break;
            //クレジットカード決済
            case $const['YAMATO_PAYID_CREDIT']:
                $res = $this->doRecvCredit($formData, $OrderExtension, $app);
                break;
            default:
                $res = false;
                break;
        }

        // 受注データ更新（取引状況）
        if ($res) {
            $paymentUtil->setOrderPayData($YamatoOrderPayment, $formData);
        }

        return $res;
    }

    /**
     * エラーメール送信（受注データ不在）
     *
     * 受注データが存在しない
     *
     * @param array $formData
     * @param Application $app
     * @return void
     */
    function doNoOrder(&$formData, $app)
    {
        $pluginUtil = $app['yamato_payment.util.plugin'];
        $tplpath = 'YamatoPayment/Resource/template/mail_template/recv_no_order.twig';
        $subject = $pluginUtil->getPluginName() . ' 不一致データ検出';
        $this->sendMail($tplpath, $subject, $formData, null, $app);
    }

    /**
     * エラーメール送信（決済未使用）
     *
     * 受注データが存在するが決済を利用していない
     *
     * @param array $formData
     * @param Application $app
     * @return void
     */
    function doNoFunctionDiv(&$formData, $app)
    {
        $pluginUtil = $app['yamato_payment.util.plugin'];
        $tplpath = 'YamatoPayment/Resource/template/mail_template/recv_no_function_div.twig';
        $subject = $pluginUtil->getPluginName() . ' 決済未使用データ検出';
        $this->sendMail($tplpath, $subject, $formData, null, $app);
    }

    /**
     * エラーメール送信（支払方法不一致）
     *
     * 受注データとECサイトの受注の不一致
     * 決済の種類が異なる.コンビニの種類も含める.
     *
     * @param array $formData
     * @param OrderExtension $OrderExtension
     * @param Application $app
     * @return void
     */
    function doUnMatchPayMethod(&$formData, $OrderExtension, $app)
    {
        $pluginUtil = $app['yamato_payment.util.plugin'];
        $tplpath = 'YamatoPayment/Resource/template/mail_template/recv_unmatch_pay_method.twig';
        $subject = $pluginUtil->getPluginName() . ' 支払い方法不一致データ検出';
        $this->sendMail($tplpath, $subject, $formData, $OrderExtension, $app);
    }

    /**
     * エラーメール送信（決済金額不一致）
     *
     * 受注データとECサイトの受注の不一致
     * お支払い合計と決済金額が異なる.
     *
     * @param array $formData
     * @param OrderExtension $OrderExtension
     * @param Application $app
     * @return void
     */
    function doUnMatchSettlePrice(&$formData, $OrderExtension, $app)
    {
        $pluginUtil = $app['yamato_payment.util.plugin'];
        $tplpath = 'YamatoPayment/Resource/template/mail_template/recv_unmatch_settle_price.twig';
        $subject = $pluginUtil->getPluginName() . ' 決済金額不一致データ検出';
        $this->sendMail($tplpath, $subject, $formData, $OrderExtension, $app);
    }

    /**
     * メール送信.
     *
     * @param string $templatePath
     * @param string $subject
     * @param array $formData
     * @param OrderExtension $OrderExtension
     * @param Application $app
     * @return void
     */
    function sendMail($templatePath, $subject, $formData, OrderExtension $OrderExtension = null, $app)
    {
        $pluginUtil = $app['yamato_payment.util.plugin'];
        $pluginUtil->printErrorLog('param_error:' . $subject . ' Param:' . print_r($formData, true));

        //支払方法(名前)セット
        $formData['settle_method'] = $this->getPayNameFromSettleMethod($formData['settle_method'], $app);

        $BaseInfo = $app['eccube.repository.base_info']->get();

        $Order = new Order();
        if (!empty($OrderExtension)) {
            $Order = $OrderExtension->getOrder();
        }
        $body = $app->renderView($templatePath, array(
            'data' => $formData,
            'order' => $Order,
        ));

        /** @var \Swift_Message $message */
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($BaseInfo->getEmail03())
            ->setTo($BaseInfo->getEmail02())
            ->setReturnPath($BaseInfo->getEmail04())
            ->setBody($body);
        $app->mail($message);
    }

    /**
     * 支払方法名取得
     *
     * 決済手段(settle_method)から支払方法名を取得する.
     * コンビニの場合はコンビニの種類も後ろに結合して取得する.
     *
     * @param  string $settle_method 決済手段ID
     * @param Application $app
     * @return string 支払方法名
     */
    protected function getPayNameFromSettleMethod($settle_method, $app)
    {
        $const = $app['config']['YamatoPayment']['const'];
        $paymentUtil = $app['yamato_payment.util.payment'];
        if ($settle_method >= $const['CREDIT_METHOD_UC']
            && $settle_method <= $const['CREDIT_METHOD_TOP']
        ) {
            $payment_name = 'クレジットカード決済';

        } elseif ($settle_method >= $const['CONVENI_ID_SEVENELEVEN']
            && $settle_method <= $const['CONVENI_ID_CIRCLEK']
        ) {
            $listCvs = $paymentUtil->getConveni();
            $payment_name = 'コンビニ決済 ' . $listCvs[$settle_method];

        } elseif ($settle_method >= $const['EMONEY_METHOD_RAKUTENEDY']
            && $settle_method <= $const['EMONEY_METHOD_M_WAON']
        ) {
            $listEmoney = $paymentUtil->getEmoney();
            $payment_name = $listEmoney[$settle_method];

        } elseif ($settle_method == $const['NETBANK_METHOD_RAKUTENBANK']) {
            $payment_name = 'ネットバンク決済';
        } else {
            $payment_name = '不明な支払方法';
        }

        return $payment_name;
    }

    /**
     * レシーブ処理（コンビニ決済）
     *
     * 正常・異常ともに想定内のPOST値であればtrueを返す.
     * 想定しないPOST値の場合はfalseを返す.
     *
     * @param array $formData
     * @param Order $Order
     * @param Application $app
     * @return bool
     */
    function doRecvCvs(&$formData, Order $Order, $app)
    {
        $dateTime = new \DateTime();

        $const = $app['config']['YamatoPayment']['const'];
        $orderStatus = null;
        switch ($formData['settle_detail']) {
            //入金完了（速報）
            case $const['YAMATO_ACTION_STATUS_PROMPT_REPORT']:
                if ($Order->getOrderStatus()->getId() == $app['config']['order_pay_wait']) {
                    $orderStatus = $app['config']['order_pre_end'];
                }
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_PROMPT_REPORT'];
                break;
            //入金完了（確報）
            case $const['YAMATO_ACTION_STATUS_DIFINIT_REPORT']:
                if ($Order->getOrderStatus()->getId() == $app['config']['order_pay_wait']) {
                    $orderStatus = $app['config']['order_pre_end'];
                }
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_DIFINIT_REPORT'];
                break;
            //購入者都合エラー（支払期限切れ、コンビニエンスストアから入金取消の通知が発生した場合等）
            case $const['YAMATO_ACTION_STATUS_NG_CUSTOMER']:
                $orderStatus = $app['config']['order_cancel'];
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_NG_CUSTOMER'];
                break;
            //決済機関都合エラー（コンビニエンスストアより応答がない場合、異常の応答を受けた場合等）
            case $const['YAMATO_ACTION_STATUS_NG_PAYMENT']:
                //ステータスは更新しない
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_NG_PAYMENT'];
                break;
            //その他のシステムエラー
            case $const['YAMATO_ACTION_STATUS_NG_SYSTEM']:
                //ステータスは更新しない
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_NG_SYSTEM'];
                break;
            default:
                return false;
        }

        //対応状況更新
        if (!empty($orderStatus)) {
            $Order->setOrderStatus($app['eccube.repository.order_status']->find($orderStatus));

            // 対応状況が入金済で、入金日がnullの場合、入金日を更新
            if ($orderStatus == $app['config']['order_pre_end'] && is_null($Order->getPaymentDate())) {
                $Order->setPaymentDate($dateTime);
            }

            // 更新日をセット
            $Order->setUpdateDate($dateTime);

            $app['orm.em']->persist($Order);
            $app['orm.em']->flush();
        }
        return true;
    }

    /**
     * レシーブ処理（予約販売用：クレジットカード決済）
     *
     * 正常・異常ともに想定内のPOST値であればtrueを返す.
     * 想定しないPOST値の場合、または対象でない取引状況の場合はfalseを返す.
     *
     * @param array $formData POST値
     * @param OrderExtension $OrderExtension
     * @param Application $app
     * @return bool
     */
    function doRecvCredit(&$formData, OrderExtension $OrderExtension, $app)
    {
        $const = $app['config']['YamatoPayment']['const'];
        $pluginUtil = $app['yamato_payment.util.plugin'];

        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();

        //取引状況「予約受付完了」の場合のみ処理する.
        if ($YamatoOrderPayment->getMemo04() != $const['YAMATO_ACTION_STATUS_COMP_RESERVE']) {
            $pluginUtil->printErrorLog('error 取引状況が「予約受付完了」ではありません。(' . $YamatoOrderPayment->getMemo04() . ')');
            return false;
        }

        switch ($formData['settle_detail']) {
            //与信完了
            case $const['YAMATO_ACTION_STATUS_COMP_AUTH']:
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_COMP_AUTH'];
                break;
            //購入者都合エラー（カード情報に誤りがある場合等）
            case $const['YAMATO_ACTION_STATUS_NG_CUSTOMER']:
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_NG_CUSTOMER'];
                break;
            //加盟店都合エラー（決済取消等）
            case $const['YAMATO_ACTION_STATUS_NG_SHOP']:
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_NG_SHOP'];
                break;
            //決済機関都合エラー（決済機関から応答が無い場合、異常の応答を受けた場合等）
            case $const['YAMATO_ACTION_STATUS_NG_PAYMENT']:
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_NG_PAYMENT'];
                break;
            //その他システムエラー
            case $const['YAMATO_ACTION_STATUS_NG_SYSTEM']:
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_NG_SYSTEM'];
                break;
            //予約販売与信エラー
            case $const['YAMATO_ACTION_STATUS_NG_RESERVE']:
                $formData['action_status'] = $const['YAMATO_ACTION_STATUS_NG_RESERVE'];
                break;
            default:
                return false;
        }
        return true;
    }

    /**
     * POST ログは全て残す.
     *
     * @param array $postData
     * @param Application $app
     * @return void
     */
    protected function printPostLog($postData, $app)
    {
        $pluginUtil = $app['yamato_payment.util.plugin'];
        $pluginUtil->printDebugLog('******* receiver data start *******');
        $pluginUtil->printDebugLog(print_r($postData, true));
        $pluginUtil->printDebugLog('******* receiver data end *******');
    }

    /**
     * レスポンスを返す。
     *
     * @param bool $result
     * @param Application $app
     * @return Response
     */
    protected function sendResponse($result, $app)
    {
        $pluginUtil = $app['yamato_payment.util.plugin'];
        $response = new Response();
        if ($result) {
            $pluginUtil->printDebugLog('response: true');
            $response->setContent('0');
        } else {
            $pluginUtil->printDebugLog('response: false');
            $response->setContent('1');
        }
        return $response;
    }

}
