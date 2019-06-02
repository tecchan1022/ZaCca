<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller\Admin;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Plugin;
use Eccube\Entity\PluginEventHandler;
use Plugin\YamatoPayment\PluginManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * レポート画面 コントローラクラス
 */
class PluginReportController extends AbstractController
{
    /**
     * レポート画面
     *
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Application $app)
    {
        // 表示情報の取得
        $systemInfo = $this->getSystemInfo($app);
        $permissionInfo = $this->getPermissionInfo($app);
        $fileInfo = $this->getFileInfo();
        $configList = $this->getConfigList($app);
        $b2ConfigList = $this->getB2ConfigList($app);
        $pluginList = $this->getPluginList($app);
        $pluginEventList = $this->getPluginEventList($app);

        // エラー判定
        $check = true;
        foreach ($fileInfo as $row) {
            if (!$row['result']) {
                $check = false;
            }
        }
        if ($check) {
            $app->addSuccess('正常にインストールされています。', 'admin');
        } else {
            $app->addError('設定値の不正が検出されました。', 'admin');
        }

        // フォームの描画
        return $app['view']->render('YamatoPayment/Resource/template/admin/Store/plugin_report.twig', array(
            'tpl_subtitle' => $app['yamato_payment.util.plugin']->getPluginName(),
            'systemInfo' => $systemInfo,
            'permissionInfo' => $permissionInfo,
            'fileInfo' => $fileInfo,
            'configList' => $configList,
            'b2ConfigList' => $b2ConfigList,
            'pluginList' => $pluginList,
            'pluginEventList' => $pluginEventList,
        ));
    }

    /**
     * システム情報の取得
     *
     * @param Application $app
     * @return array
     */
    public function getSystemInfo(Application $app)
    {
        $ret = array();

        $ret[] = array(
            'title' => 'EC-CUBE',
            'value' => Constant::VERSION
        );
        $ret[] = array(
            'title' => 'サーバーOS',
            'value' => php_uname()
        );
        $ret[] = array(
            'title' => 'DBサーバー',
            'value' => $app['eccube.service.system']->getDbversion()
        );
        $ret[] = array(
            'title' => 'WEBサーバー',
            'value' => $app['request']->server->get("SERVER_SOFTWARE")
        );
        $ret[] = array(
            'title' => 'PHP',
            'value' => phpversion() . ' (' . implode(', ', get_loaded_extensions()) . ')'
        );
        $ret[] = array(
            'title' => 'HTTPユーザーエージェント',
            'value' => $app['request']->headers->get('User-Agent')
        );
        $ret[] = array(
            'title' => 'グローバルIPアドレス',
            'value' => $app['request']->server->get('REMOTE_ADDR')
        );

        return $ret;
    }

    /**
     * 権限チェックの取得
     *
     * @param Application $app
     * @return array
     */
    public function getPermissionInfo(Application $app)
    {
        $ret = array();

        // 権限チェックを行うパス一覧取得
        $pathList = $this->getPermissionPath();

        $index = 1;
        foreach ($pathList as $path) {
            // パーミッションチェック
            $checkPath = $app['config']['root_dir'] . $path;
            $dirPerms = intval(substr(sprintf('%o', fileperms($checkPath)), -4));
            // 707 or 777 の場合、OK
            $result = ($dirPerms == 707 || $dirPerms == 777) ? true : false;

            $ret[] = array(
                'index' => $index,
                'path' => $checkPath,
                'permission' => $dirPerms,
                'result' => $result
            );
            $index++;
        }

        return $ret;
    }

    /**
     * ヤマト決済プラグイン配置ファイル一覧取得
     *
     * @return array
     */
    public function getFileInfo()
    {
        $ret = array();

        $manager = new PluginManager();
        $directories = $manager->getResourceDirectories();
        foreach ($directories as $directory) {
            $fileList = $this->checkFileExists($directory['source'], $directory['target']);
            $ret = array_merge($ret, $fileList);
        }

        // 連番を付与する
        $index = 1;
        foreach ($ret as &$row) {
            $row['index'] = $index;
            $index++;
        }

        return $ret;
    }

    /**
     * コピー対象ファイルが正しく配置されているかのリスト
     *
     * @param string $origin コピー元ディレクトリ
     * @param string $target コピー先ディレクトリ
     * @return array
     */
    public function checkFileExists($origin, $target)
    {
        $ret = array();

        // コピー元ファイル一覧取得
        $fileList = Finder::create()
            ->in($origin) // ベースディレクトリを指定
            ->files(); // ディレクトリは除外し、ファイルのみ取得

        // コピー先に配置されているかチェック
        foreach ($fileList as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            $filePath = $target . '/' . $fileInfo->getRelativePathname();

            $ret[] = array(
                'path' => $filePath,
                'result' => (file_exists($filePath)) ? : false
            );
        }

        return $ret;
    }

    /**
     * ヤマト決済プラグイン 設定値一覧取得
     *
     * @param Application $app
     * @return array
     */
    public function getConfigList(Application $app)
    {
        $ret = array();
        $index = 1;

        // プラグイン設定
        $settings = $app['yamato_payment.util.plugin']->getUserSettings();
        foreach ((array)$settings as $key => $val) {
            $ret[] = array(
                'index' => $index,
                'key' => $key,
                'value' => (is_array($val)) ? print_r($val, true) : $val,
            );
            $index++;
        }

        // データマスク
        foreach ($ret as &$data) {
            switch ($data['key']) {
                case 'shop_id':
                case 'access_key':
                case 'ycf_str_code':
                case 'ycf_str_password':
                    $data['value'] = str_repeat('*', strlen($data['value']));
                    break;
                default:
                    break;
            }
        }

        return $ret;
    }

    /**
     * ヤマト決済プラグイン B2設定値一覧取得
     *
     * @param Application $app
     * @return array
     */
    public function getB2ConfigList(Application $app)
    {
        $ret = array();
        $index = 1;

        // B2プラグイン設定
        $settings = $app['yamato_payment.util.plugin']->getB2UserSettings();
        foreach ((array)$settings as $key => $val) {
            $ret[] = array(
                'index' => $index,
                'key' => $key,
                'value' => (is_array($val)) ? print_r($val, true) : $val,
            );
            $index++;
        }
        // データマスク
        foreach ($ret as &$data) {
            switch ($data['key']) {
                case 'claim_customer_code':
                    $data['value'] = str_repeat('*', strlen($data['value']));
                    break;
                default:
                    break;
            }
        }
        return $ret;
    }

    /**
     * プラグイン一覧情報の取得
     *
     * @param Application $app
     * @return array
     */
    public function getPluginList(Application $app)
    {
        $ret = array();

        // プラグイン一覧の取得
        $Plugins = $app['eccube.repository.plugin']->findAll();
        foreach ($Plugins as $Plugin) {
            /** @var Plugin $Plugin */
            $ret[] = array(
                'id' => $Plugin->getId(),
                'name' => $Plugin->getName(),
                'version' => $Plugin->getVersion(),
                'enable' => $Plugin->getEnable(),
            );
        }

        return $ret;
    }

    /**
     * プラグイン イベント一覧情報の取得
     *
     * @param Application $app
     * @return array
     */
    public function getPluginEventList(Application $app)
    {
        $ret = array();

        // プラグイン イベント一覧の取得
        $PluginEvents = $app['eccube.repository.plugin_event_handler']->findBy(
            array(),
            array('plugin_id' => 'ASC', 'event' => 'ASC')
        );
        foreach ($PluginEvents as $PluginEvent) {
            /** @var PluginEventHandler $PluginEvent */
            $ret[] = array(
                'id' => $PluginEvent->getPluginId(),
                'event' => $PluginEvent->getEvent(),
                'priority' => $PluginEvent->getPriority(),
            );
        }

        return $ret;
    }

    /**
     * CSV出力データの生成
     *
     * @param Application $app
     * @return array
     */
    public function makeCsvOutputData(Application $app)
    {
        $rows = array();

        $rows[] = '【システム情報】';
        $data = $this->getSystemInfo($app);
        foreach ($data as $values) {
            $rows[] = array('', $values['title'], $values['value']);
        }

        $rows[] = array('【権限チェック】', '#', 'path', 'mode', '判定');
        $data = $this->getPermissionInfo($app);
        foreach ($data as $values) {
            $rows[] = array('', $values['index'], $values['path'], $values['permission'], $values['result'] == true ? 'OK' : 'WARN');
        }

        $rows[] = array('【ファイル存在チェック】', '#', 'path', '判定');
        $data = $this->getFileInfo();
        foreach ($data as $values) {
            $rows[] = array('', $values['index'], $values['path'], $values['result'] == true ? 'OK' : 'NG');
        }

        $rows[] = array('【プラグイン設定 設定値一覧】', '#', 'key', 'value');
        $data = $this->getConfigList($app);
        foreach ($data as $values) {
            $rows[] = array('', $values['index'], $values['key'], $values['value']);
        }

        $rows[] = array('【B2設定 設定値一覧】', '#', 'key', 'value');
        $data = $this->getB2ConfigList($app);
        foreach ($data as $values) {
            $rows[] = array('', $values['index'], $values['key'], $values['value']);
        }

        $rows[] = array('【プラグイン一覧】', 'プラグインID', 'プラグイン名', 'バージョン', '状態');
        $data = $this->getPluginList($app);
        foreach ($data as $values) {
            $rows[] = array('', $values['id'], $values['name'], $values['version'], $values['enable'] == true ? '有効' : '無効');
        }

        $rows[] = array('【プラグイン イベント一覧】', 'プラグインID', 'イベント名', '優先度');
        $data = $this->getPluginEventList($app);
        foreach ($data as $values) {
            $rows[] = array('', $values['id'], $values['event'], $values['priority']);
        }

        return $rows;
    }

    /**
     * レポート のダウンロード
     *
     * @param Application $app
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportCSV(Application $app, Request $request)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $app['orm.em'];
        $em->getConfiguration()->setSQLLogger(null);

        // CSV出力データ取得
        $csvData = $this->makeCsvOutputData($app);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($app, $request, $csvData) {

            $app['eccube.service.csv.export']->fopen();

            // CSV出力.
            foreach ($csvData as $rowData) {
                $app['eccube.service.csv.export']->fputcsv((array)$rowData);
            }

            $app['eccube.service.csv.export']->fclose();
        });

        $now = new \DateTime();
        $filename = 'yamato_report_' . $now->format('YmdHis') . '.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->send();

        return $response;
    }

    /**
     * 権限チェックのパス一覧
     *     root_dir は除く
     */
    public function getPermissionPath()
    {
        return array(
            '/html',
            '/app',
            '/app/template',
            '/app/cache',
            '/app/config',
            '/app/config/eccube',
            '/app/log',
            '/app/Plugin',
        );
    }

}
