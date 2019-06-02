<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\GoogleAnalyticsSimpleSetup;

use Eccube\Plugin\AbstractPluginManager;
use Plugin\GoogleAnalyticsSimpleSetup\Entity\GoogleAnalyticsSSPlugin;
use Doctrine\ORM\Query\ResultSetMapping;
use Eccube\Entity\Master\DeviceType;

class PluginManager extends AbstractPluginManager
{
    protected $file_name;

    public function __construct()
    {
        $this->file_name = 'gas_google_analytics';
    }

    public function install($config, $app)
    {
        $this->migrationSchema($app, __DIR__ . '/Migration', $config['code']);

        // ブロックのテンプレートコピー
        copy(__DIR__ . '/Resource/template/Block/' . $this->file_name . '.twig', $app['config']['block_realdir'] . '/' . $this->file_name . '.twig');
    }

    public function uninstall($config, $app)
    {
        $this->migrationSchema($app, __DIR__ . '/Migration', $config['code'], 0);
        $this->deletePlugin($config, $app);

        // ブロック削除
        $path = $app['config']['block_realdir'] . '/' . $this->file_name . '.twig';
        if (file_exists($path)){
            unlink($path);
        }
    }

    public function enable($config, $app)
    {
        $this->createPlugin($config, $app);
    }

    public function disable($config, $app)
    {
        $this->deletePlugin($config, $app);
    }

    public function update($config, $app)
    {
        $this->migrationSchema($app, __DIR__ . '/Migration', $config['code']);
    }

    private function createPlugin($config, $app){
        // ブロックの作成
        /* @var $Block \Eccube\Entity\Block */
        /* @var $BlockRepository \Eccube\Repository\BlockRepository */
        $DeviceType = $app['eccube.repository.master.device_type']->find(DeviceType::DEVICE_TYPE_PC);
        $BlockRepository = $app['eccube.repository.block'];
        $Block           = $BlockRepository->findOrCreate(null, $DeviceType);
        $Block->setName('GoogleAnalytics');
        $Block->setFileName($this->file_name);
        $Block->setLogicFlg(1);
        $Block->setDeletableFlg(0);

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $app['orm.em'];
        $em->persist($Block);
        $em->flush();

        // ブロック位置作成
        // フッターの最後
        /* @var $BlockPosition \Eccube\Entity\BlockPosition */
        /* @var $TargetPageLayout \Eccube\Entity\PageLayout */
        $page_id = 1;
        $TargetPageLayout = $app['eccube.repository.page_layout']->get($DeviceType, $page_id);
        $BlockPosition    = new \Eccube\Entity\BlockPosition();
        $dql = <<< __EOS__
            SELECT MAX(bp.block_row)
              FROM Eccube\Entity\BlockPosition AS bp
             WHERE bp.page_id   = :page_id
               AND bp.target_id = :target_id
__EOS__;
        $Query = $em
            ->createQuery($dql)
            ->setParameter('page_id', $page_id)
            ->setParameter('target_id', \Eccube\Entity\PageLayout::TARGET_ID_FOOTER);
        $max_row = $Query->getSingleScalarResult();

        $BlockPosition
            ->setPageId($page_id)
            ->setTargetId(\Eccube\Entity\PageLayout::TARGET_ID_FOOTER)
            ->setAnywhere(1)
            ->setBlockRow($max_row + 1)
            ->setBlockId($Block->getId())
            ->setBlock($Block)
            ->setPageLayout($TargetPageLayout);
        $TargetPageLayout->addBlockPosition($BlockPosition);
        $em->flush();
    }

    private function deletePlugin($config, $app){
        /* @var $BlockRepository \Eccube\Repository\BlockRepository */
        $DeviceType      = $app['eccube.repository.master.device_type']->find(10);
        $BlockRepository = $app['eccube.repository.block'];
        $Blocks          = $BlockRepository->findBy(array(
            'file_name'  => 'gas_google_analytics',
            'DeviceType' => $DeviceType,
        ));

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $app['orm.em'];
        foreach ($Blocks as $Block) {
            $em->remove($Block);
        }
        $em->flush();
    }
}