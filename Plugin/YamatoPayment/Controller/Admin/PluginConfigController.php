<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller\Admin;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * プラグイン設定画面 コントローラクラス
 */
class PluginConfigController extends AbstractController
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
        $subData = $pluginUtil->getUserSettings();

        // フォームを作成する
        $form = $this->app['form.factory']
            ->createBuilder('yamato_plugin_config', $subData)
            ->getForm();

        $grobal_ip_adress = '-';
        // 登録処理
        if ('POST' === $request->getMethod()) {
            $mode = $request->get('mode');

            if($mode == 'getGID') {
                $grobal_ip_adress = $app['yamato_payment.service.client.util']->doGetGlobalIpAddress();
                if($grobal_ip_adress == false) {
                    $errorMsgs = $app['yamato_payment.service.client.util']->getError();
                    $res = $app['yamato_payment.service.client.util']->getResults();
                    if($res['errorCode'] == 'Z012000007') {
                        $errorMsgs = array('テスト環境申込がされていないため、グローバルIPの照会は行えません。');
                    }
                    $app->addDanger('グローバルIPアドレス取得エラー：'.implode(',', $errorMsgs) , 'admin');
                }
            } else {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $formData = $form->getData();

                    $this->app['orm.em']->getConnection()->beginTransaction();

                    // プラグイン設定情報登録
                    $pluginUtil->registerUserSettings($formData);

                    // 支払情報の有効/無効更新
                    $this->app['yamato_payment.repository.yamato_payment_method']
                        ->enableYamatoPaymentByConfig();

                    $this->app['orm.em']->getConnection()->commit();

                    $app->addSuccess('admin.register.complete', 'admin');
                } else {
                    $app->addError('admin.register.failed', 'admin');
                }
            }
        }

        // フォームの描画
        return $this->app['view']->render('YamatoPayment/Resource/template/admin/Store/plugin_config.twig', array(
            'form' => $form->createView(),
            'tpl_subtitle' => $pluginUtil->getPluginName(),
            'recv_url' => $app->url('yamato_shopping_payment_recv'),
            'subData' => $subData,
            'grobal_ip_adress' => $grobal_ip_adress
        ));
    }

}
