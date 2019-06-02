<?php

/*
 * This file is part of the AdminLoginAlert
 *
 * Copyright (C) 2018 refine
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\AdminLoginAlert\Controller;

use Eccube\Application;
use Plugin\AdminLoginAlert\Entity\AdminLoginAlertConfig;
use Symfony\Component\HttpFoundation\Request;

class ConfigController
{

    /**
     * AdminLoginAlert用設定画面
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app, Request $request)
    {
        /** @var AdminLoginAlertConfig $adminLoginAlertConfig */
        $adminLoginAlertConfig = $app['plugin.admin_login_alert.repository.admin_login_alert_config']->find(1);

        $form = $app['form.factory']->createBuilder('adminloginalert_config', $adminLoginAlertConfig)->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $adminLoginAlertConfig->setEmail($data['email']);
            $app['orm.em']->persist($adminLoginAlertConfig);
            $app['orm.em']->flush();

            $app->addSuccess('更新が完了しました', 'admin');

        }

        return $app->render('AdminLoginAlert/Resource/template/admin/config.twig', array(
            'form' => $form->createView(),
        ));
    }

}
