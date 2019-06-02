<?php
/*
 * Copyright(c) 2016 SYSTEM_KD
 */

namespace Plugin\SimpleSiteMaintenance\Controller;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;

class ConfigController
{
    public function index(Application $app, Request $request)
    {

        /* var $SsmConfig \Plugin\SimpleSiteMaintenance\Entity\SsmConfig */
        $SsmConfig = $app['ssm.repository.ssmconfig']->get();

        if(empty($SsmConfig)) {
            $SsmConfig = new \Plugin\SimpleSiteMaintenance\Entity\SsmConfig();
            // 初期値設定
            $SsmConfig->setMenteMode(0);
            $SsmConfig->setAdminCloseFlg(0);
        }

        $form = $app['form.factory']->createBuilder('plg_ssm_config', $SsmConfig)->getForm();

        if($request->getMethod() === 'POST') {

            $form->handleRequest($app['request']);

            if ($form->isValid()) {

                // 登録処理
                $app['orm.em']->persist($SsmConfig);
                $app['orm.em']->flush();

                $app->addSuccess('admin.register.complete', 'admin');
            }

        }

        return $app->render('SimpleSiteMaintenance/Resource/template/config.twig', array(
            'form' => $form->createView()
        ));
    }
}
