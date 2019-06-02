<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Payment;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Util\EntityUtil;
use Plugin\YamatoPayment\ServiceProvider\PaymentServiceProvider;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{
    /**
     * @var array リソースディレクトリ配列
     */
    private $resourceDirectories = array();

    /**
     * @param Application $app
     */
    public function setResourceDirectories($app) {
        // Assetsディレクトリ
        $this->resourceDirectories[] = array(
            'source' => __DIR__ . '/Resource/assets',
            'target' => __DIR__ . '/../../../html/plugin/yamato_payment',
        );

        // Blockテンプレートディレクトリ設定
        $this->resourceDirectories[] = array(
            'source' => __DIR__ . '/Resource/template_copy/Block',
            'target' => __DIR__ . '/../../template/'.$app['config']['template_code'].'/Block/YamatoPayment',
        );

        // Defaultテンプレートディレクトリ設定
        $this->resourceDirectories[] = array(
            'source' => __DIR__ . '/Resource/template_copy/YamatoPayment',
            'target' => __DIR__ . '/../../template/'.$app['config']['template_code'].'/YamatoPayment',
        );
    }

    /**
     * @return array
     */
    public function getResourceDirectories() {
        return $this->resourceDirectories;
    }

    /**
     * コンストラクタ
     */
    public function __construct()
    {
    }

    /**
     * インストール時の処理
     * 
     * @param array $config 設定ファイルの情報
     * @param Application $app
     */
    public function install($config, $app)
    {
        // リソースファイルのコピー
        $this->setResourceDirectories($app);
        $this->copyResource();
    }

    /**
     * アンインストール時の処理
     *
     * @param array $config 設定ファイルの情報
     * @param Application $app
     */
    public function uninstall($config, $app)
    {
        // マイグレーション実行
        $this->migrationSchema($app, __DIR__ . '/Resource/doctrine/migration', $config['code'], 0);
        // リソースファイルの削除
        $this->setResourceDirectories($app);
        $this->removeResource();
    }

    /**
     * プラグイン有効時の処理
     *
     * @param array $config 設定ファイルの情報
     * @param Application $app
     */
    public function enable($config, $app)
    {
        $app->register(new PaymentServiceProvider($app));

        // マイグレーション実行(install()はnamespaceをロードしていないのでここで実行する)
        $this->migrationSchema($app, __DIR__ . '/Resource/doctrine/migration', $config['code']);
        
        // 支払方法データ更新
        $app['yamato_payment.repository.yamato_payment_method']->enableYamatoPaymentByConfig();
    }

    /**
     * プラグイン無効時の処理
     *
     * @param array $config 設定ファイルの情報
     * @param Application $app
     */
    public function disable($config, $app)
    {
        $app->register(new PaymentServiceProvider($app));

        // 支払方法データ更新
        $app['yamato_payment.repository.yamato_payment_method']->disableYamatoPaymentAll();
    }

    /**
     * アップデート時の処理
     *
     * @param array $config 設定ファイルの情報
     * @param $app Application
     */
    public function update($config, $app)
    {
    }

    /**
     * リソースファイルのコピー
     *
     * @return void
     */
    public function copyResource()
    {
        foreach ($this->resourceDirectories as $directory) {
            $file = new Filesystem();
            $file->mirror($directory['source'], $directory['target']);
        }
    }

    /**
     * リソースファイルの削除
     *
     * @return void
     */
    public function removeResource()
    {
        foreach ($this->resourceDirectories as $directory) {
            $file = new Filesystem();
            $file->remove($directory['target']);
        }
    }

}
