<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller\Admin;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Delivery;
use Eccube\Entity\DeliveryTime;
use Eccube\Entity\Payment;
use Symfony\Component\HttpFoundation\Request;

/**
 * B2プラグイン設定画面 コントローラクラス
 */
class PluginB2ConfigController extends AbstractController
{
    /**
     * @var Application
     */
    private $app;

    /**
     * プラグイン設定変更
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Application $app, Request $request)
    {
        $this->app = $app;

        // Utility の取得
        $pluginUtil = $this->app['yamato_payment.util.plugin'];

        // プラグインの設定情報を取得する
        $b2Data = $pluginUtil->getB2UserSettings();

        // フォームを作成する
        $form = $this->app['form.factory']
            ->createBuilder('yamato_b2_config', $b2Data)
            ->getForm();

        // 支払い方法マスタ取得
        $Payments = $this->app['eccube.repository.payment']->findBy(
            array('del_flg' => 0),
            array('rank' => 'DESC')
        );

        // B2送り状種別設定フォーム作成
        $b2PaymentTypes = array();
        foreach ($Payments as $Payment) {
            /** @var Payment $Payment */
            $b2PaymentType = array();
            $b2PaymentType['payment_id'] = $Payment->getId();
            $b2PaymentType['payment_method'] = $Payment->getMethod();
            $b2PaymentType['deliv_slip_type'] = $b2Data['deliv_slip_type'][$Payment->getId()];
            $b2PaymentTypes[] = $b2PaymentType;
        }

        // B2送り状種別設定データセット
        $form->get('b2_payment_type')->setData($b2PaymentTypes);

        // 配送方法マスタ取得
        $Deliveries = $app['eccube.repository.delivery']->findBy(
            array('del_flg' => 0),
            array('rank' => 'ASC')
        );

        // B2クール便区分設定フォーム作成
        // B2配送時間コード設定フォーム作成
        // B2配送サービスコード設定フォーム作成
        $b2DeliveryTypes = array();
        foreach ($Deliveries as $Delivery) {
            /** @var Delivery $Delivery */
            $b2DeliveryType = array();
            $b2DeliveryType['delivery_id'] = $Delivery->getId();
            $b2DeliveryType['delivery_name'] = $Delivery->getName();
            $b2DeliveryType['cool_kb'] = $b2Data['cool_kb'][$Delivery->getId()];
            $b2DeliveryType['delivery_service_code'] = $b2Data['delivery_service_code'][$Delivery->getId()];
            $b2DeliveryType['b2_delivtime_code'] = array();

            $DeliveryTimes = $Delivery->getDeliveryTimes();
            for ($time_index = 0; $time_index < count($DeliveryTimes); $time_index++) {
                /** @var DeliveryTime $DeliveryTime */
                $DeliveryTime = $DeliveryTimes[$time_index];

                $b2DeliveryTimeType = array();
                $b2DeliveryTimeType['delivery_time_id'] = $DeliveryTime->getId();
                $b2DeliveryTimeType['delivery_time'] = '配送時間' . ($time_index + 1) . ' ' . $DeliveryTime->getDeliveryTime();
                $b2DeliveryTimeType['b2_delivtime_code'] = $b2Data['b2_delivtime_code'][$Delivery->getId()][$DeliveryTime->getId()];
                $b2DeliveryType['b2_delivtime_code'][] = $b2DeliveryTimeType;
            }
            $b2DeliveryTypes[] = $b2DeliveryType;
        }
        
        // B2配送データセット
        $form->get('b2_delivery_type')->setData($b2DeliveryTypes);

        // 登録処理
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $formData = $form->getData();

                $this->app['orm.em']->getConnection()->beginTransaction();
                $pluginUtil->registerB2UserSettings($this->processSetUserSettingsData($formData));
                $this->app['orm.em']->getConnection()->commit();

                $app->addSuccess('admin.register.complete', 'admin');
            } else {
                $app->addError('admin.register.failed', 'admin');
            }
        }

        // フォームの描画
        return $this->app['view']->render('YamatoPayment/Resource/template/admin/Store/plugin_b2_config.twig', array(
            'form' => $form->createView(),
            'tpl_subtitle' => $pluginUtil->getPluginName(),
            'subData' => $b2Data,
        ));
    }

    /**
     * B2設定フォーム情報を DB登録用のデータに加工する
     *
     * @param array $formData フォームデータ
     * @return array DBに登録する B2設定データ
     */
    public function processSetUserSettingsData($formData)
    {
        foreach ($formData as $key => $values) {

            switch ($key) {
                case 'b2_payment_type':
                    $formData['deliv_slip_type'] = array();

                    foreach ($formData['b2_payment_type'] as $payment) {
                        $formData['deliv_slip_type'][$payment['payment_id']] = $payment['deliv_slip_type'];
                    }
                    break;
                case 'b2_delivery_type':
                    $formData['cool_kb'] = array();
                    $formData['delivery_service_code'] = array();
                    $formData['b2_delivtime_code'] = array();

                    foreach ($formData['b2_delivery_type'] as $delivery) {
                        $formData['cool_kb'][$delivery['delivery_id']] = $delivery['cool_kb'];
                        $formData['delivery_service_code'][$delivery['delivery_id']] = $delivery['delivery_service_code'];

                        foreach ($delivery['b2_delivtime_code'] as $delivery_time) {
                            $formData['b2_delivtime_code'][$delivery['delivery_id']][$delivery_time['delivery_time_id']] = $delivery_time['b2_delivtime_code'];
                        }
                    }
                    break;

            }
        }

        unset($formData['b2_payment_type']);
        unset($formData['b2_delivery_type']);

        return $formData;
    }

}
