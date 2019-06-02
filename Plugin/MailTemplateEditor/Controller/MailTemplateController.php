<?php
/*
  * This file is part of the MailTemplateEditor plugin
  *
  * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
  *
  * For the full copyright and license information, please view the LICENSE
  * file that was distributed with this source code.
  */

namespace Plugin\MailTemplateEditor\Controller;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Util\Cache;
use Eccube\Util\Str;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

class MailTemplateController extends AbstractController
{
    /**
     * メールファイル管理一覧画面.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app, Request $request)
    {
        // Mailディレクトリ(app/template、Resource/template)からメールファイルを取得
        $finder = Finder::create()->depth(0);
        $mailDir = $app['config']['template_default_realdir'].'/Mail';

        $files = array();
        foreach ($finder->in($mailDir) as $file) {
            $files[$file->getFilename()] = $file->getFilename();
        }

        $mailDir = $app['config']['template_realdir'].'/Mail';
        if (file_exists($mailDir)) {
            foreach ($finder->in($mailDir) as $file) {
                $files[$file->getFilename()] = $file->getFilename();
            }
        }

        return $app->render('MailTemplateEditor/Resource/template/admin/mail.twig', array(
            'files' => $files,
        ));
    }

    /**
     * メール編集画面.
     *
     * @param Application $app
     * @param Request     $request
     * @param $name
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function edit(Application $app, Request $request, $name)
    {
        $readPaths = array(
            $app['config']['template_realdir'],
            $app['config']['template_default_realdir'],
        );

        $fs = new Filesystem();
        $tplData = null;
        foreach ($readPaths as $readPath) {
            $filePath = $readPath.'/Mail/'.$name;
            if ($fs->exists($filePath)) {
                $tplData = file_get_contents($filePath);
                break;
            }
        }

        if (!$tplData) {
            $app->addError('admin.mailtemplateeditor.mail.edit.error', 'admin');

            return $app->redirect($app->url('plugin_MailTemplateEditor_mail'));
        }

        $builder = $app['form.factory']->createBuilder('admin_mail_template');

        $form = $builder->getForm();

        $form->get('tpl_data')->setData($tplData);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ファイル生成・更新
            $filePath = $app['config']['template_realdir'].'/Mail/'.$name;

            $fs = new Filesystem();
            $pageData = $form->get('tpl_data')->getData();
            $pageData = Str::convertLineFeed($pageData);
            $fs->dumpFile($filePath, $pageData);

            $app->addSuccess('admin.register.complete', 'admin');

            // twig キャッシュの削除.
            Cache::clear($app, false, true);

            return $app->redirect($app->url('plugin_MailTemplateEditor_mail_edit', array(
                'name' => $name,
            )));
        }

        return $app->render('MailTemplateEditor/Resource/template/admin/mail_edit.twig', array(
            'name' => $name,
            'form' => $form->createView(),
        ));
    }

    /**
     * メールファイル初期化処理.
     *
     * @param Application $app
     * @param Request     $request
     * @param $name
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function reedit(Application $app, Request $request, $name)
    {
        $this->isTokenValid($app);

        $readPaths = array(
            $app['config']['template_default_realdir'],
        );

        $fs = new Filesystem();
        $tplData = null;
        foreach ($readPaths as $readPath) {
            $filePath = $readPath.'/Mail/'.$name;
            if ($fs->exists($filePath)) {
                $tplData = file_get_contents($filePath);
                break;
            }
        }

        if (!$tplData) {
            $app->addError('admin.mailtemplateeditor.mail.edit.error', 'admin');

            return $app->redirect($app->url('plugin_MailTemplateEditor_mail'));
        }

        $builder = $app['form.factory']->createBuilder('admin_mail_template');

        $form = $builder->getForm();

        $form->get('tpl_data')->setData($tplData);

        // ファイル生成・更新
        $filePath = $app['config']['template_realdir'].'/Mail/'.$name;

        $fs = new Filesystem();
        $fs->dumpFile($filePath, $tplData);

        $app->addSuccess('admin.mailtemplateeditor.mail.init.complete', 'admin');

        return $app->render('MailTemplateEditor/Resource/template/admin/mail_edit.twig', array(
            'name' => $name,
            'form' => $form->createView(),
        ));
    }
}
