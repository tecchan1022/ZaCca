<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace DoctrineMigrations;

use Doctrine\ORM\Tools\SchemaTool;
use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Doctrine\Filter\SoftDeleteFilter;
use Eccube\Entity\Block;
use Eccube\Entity\Csv;
use Eccube\Entity\Delivery;
use Eccube\Entity\DeliveryFee;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\Pref;
use Eccube\Entity\Master\ProductType;
use Eccube\Entity\Member;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Master\DeviceType;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoPlugin;

class Version20160217140830 extends AbstractMigration
{
    /**
     * @var Application
     */
    private $app;

    public function init()
    {
        if(is_null($this->app)){
            $this->app = Application::getInstance();
        }
    }

    public function up(Schema $schema)
    {
        $this->init();

        // DB テーブルの生成
        $rootName = 'Plugin\\YamatoPayment\\Entity\\';
        $this->createTable($schema, 'plg_yamato_order_payment', $rootName . 'YamatoOrderPayment');
        $this->createTable($schema, 'plg_yamato_order_scheduled_shipping_date', $rootName . 'YamatoOrderScheduledShippingDate');
        $this->createTable($schema, 'plg_yamato_payment_method', $rootName . 'YamatoPaymentMethod');
        $this->createTable($schema, 'plg_yamato_plugin', $rootName . 'YamatoPlugin');
        $this->createTable($schema, 'plg_yamato_product', $rootName . 'YamatoProduct');
        $this->createTable($schema, 'plg_yamato_shipping_deliv_slip', $rootName . 'YamatoShippingDelivSlip');
    }

    public function down(Schema $schema)
    {
        // プラグインデータの削除
        $this->addSql("UPDATE plg_yamato_plugin SET del_flg = 1 WHERE del_flg = 0;");

        // ブロックポジションの削除
        $this->addSql("DELETE FROM dtb_block_position WHERE page_id IN (SELECT page_id FROM dtb_page_layout WHERE url = 'yamato_mypage_change_card');");
        $this->addSql("DELETE FROM dtb_block_position WHERE page_id IN (SELECT page_id FROM dtb_page_layout WHERE url = 'yamato_shopping_payment');");
        $this->addSql("DELETE FROM dtb_block_position WHERE block_id IN (SELECT block_id FROM dtb_block WHERE file_name = 'YamatoPayment/payment_deferred');");
        $this->addSql("DELETE FROM dtb_block_position WHERE block_id IN (SELECT block_id FROM dtb_block WHERE file_name = 'YamatoPayment/payment_credit');");
        $this->addSql("DELETE FROM dtb_block_position WHERE block_id IN (SELECT block_id FROM dtb_block WHERE file_name = 'YamatoPayment/payment_conveni');");

        // ページレイアウトの削除
        $this->addSql("DELETE FROM dtb_page_layout WHERE url = 'yamato_mypage_change_card';");
        $this->addSql("DELETE FROM dtb_page_layout WHERE url = 'yamato_shopping_payment';");

        // ブロック情報の削除
        $this->addSql("DELETE FROM dtb_block WHERE file_name = 'YamatoPayment/payment_deferred';");
        $this->addSql("DELETE FROM dtb_block WHERE file_name = 'YamatoPayment/payment_credit';");
        $this->addSql("DELETE FROM dtb_block WHERE file_name = 'YamatoPayment/payment_conveni';");

        // CSVレコードの削除
        $this->addSql("DELETE FROM dtb_csv WHERE csv_type = 1 AND field_name = 'reserve_date';");
        $this->addSql("DELETE FROM dtb_csv WHERE csv_type = 1 AND field_name = 'not_deferred_flg';");
        $this->addSql("DELETE FROM dtb_csv WHERE csv_type = 3 AND field_name = 'scheduled_shipping_date';");
    }

    public function postUp(Schema $schema)
    {
        $this->init();

        // プラグインデータの登録
        $this->addYamatoPlugin();

        // マスタデータの登録
        $this->addMtbProductType();
        $this->addMtbCustomerOrderStatus();
        $this->addMtbOrderStatus();
        $this->addMtbOrderStatusColor();

        // ページレイアウトの登録
        $this->addPageLayout();

        // ブロックの登録
        $this->addBlock();

        // CSVデータの登録
        $this->addProductCsv();
        $this->addOrderCsv();

        // 配送完了メールテンプレートの登録
        $this->addDelivCompleteMailTemplate();

        // 支払い方法の登録
        $paymentIdList = $this->addPayment();

        // 配送方法登録（予約商品用）
        $this->addReserveDeliveryData($paymentIdList);
    }

    /**
     * テーブル作成
     *
     * @param Schema $schema
     * @param $tableName
     * @param $entityName
     * @return bool
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    protected function createTable(Schema $schema, $tableName, $entityName)
    {
        if ($schema->hasTable($tableName)) {
            return true;
        }
        $ClassMetadata = $this->app['orm.em']->getClassMetadata($entityName);
        $tool = new SchemaTool($this->app['orm.em']);
        $tool->createSchema(array($ClassMetadata));
    }

    /**
     * プラグインデータ登録
     *
     * @return void
     */
    protected function addYamatoPlugin()
    {
        $sql = 'SELECT * FROM plg_yamato_plugin WHERE del_flg = 0';

        $YamatoPlugin = new YamatoPlugin();
        $YamatoPlugin
            ->setCode('YamatoPayment')
            ->setName('クロネコヤマト カード・後払い一体型決済モジュール');

        $results = $this->connection->fetchAll($sql);
        if (count($results) == 0) {
            $this->app['orm.em']->persist($YamatoPlugin);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * 商品種別マスター登録
     *
     * @return void
     */
    protected function addMtbProductType()
    {
        $sql = 'SELECT * FROM mtb_product_type WHERE id = :id';

        $Master = new \Eccube\Entity\Master\ProductType();
        $Master
            ->setId(9625)
            ->setName('予約商品')
            ->setRank(9625);

        $results = $this->connection->fetchAll($sql, array('id' => $Master->getId()));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Master);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * 会員用注文ステータスマスター登録
     *
     * @return void
     */
    protected function addMtbCustomerOrderStatus()
    {
        $sql = 'SELECT * FROM mtb_customer_order_status WHERE id = :id';

        $Master = new \Eccube\Entity\Master\CustomerOrderStatus();
        $Master
            ->setId(9625)
            ->setName('クレジットカード出荷登録済み')
            ->setRank(9625);

        $results = $this->connection->fetchAll($sql, array('id' => $Master->getId()));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Master);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * 注文ステータスマスター登録
     *
     * @return void
     */
    protected function addMtbOrderStatus()
    {
        $sql = 'SELECT * FROM mtb_order_status WHERE id = :id';

        $Master = new \Eccube\Entity\Master\OrderStatus();
        $Master
            ->setId(9625)
            ->setName('クレジットカード出荷登録済み')
            ->setRank(9625);

        $results = $this->connection->fetchAll($sql, array('id' => $Master->getId()));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Master);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * 注文ステータスカラーマスター登録
     *
     * @return void
     */
    protected function addMtbOrderStatusColor()
    {
        $sql = 'SELECT * FROM mtb_order_status_color WHERE id = :id';

        $Master = new \Eccube\Entity\Master\OrderStatusColor();
        $Master
            ->setId(9625)
            ->setName('#CCFFCC')
            ->setRank(9625);

        $results = $this->connection->fetchAll($sql, array('id' => $Master->getId()));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Master);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * ページレイアウト登録
     *
     * @return void
     */
    protected function addPageLayout()
    {
        $sql = 'SELECT * FROM dtb_page_layout WHERE url = :url';

        /** @var DeviceType $deviceType */
        $DeviceType = $this->app['eccube.repository.master.device_type']->find(10);

        $PageLayout = new PageLayout();
        $PageLayout
            ->setName('MYページ/カード情報編集')
            ->setUrl('yamato_mypage_change_card')
            ->setFileName('YamatoPayment/mypage_card_edit')
            ->setMetaRobots('noindex')
            ->setEditFlg(PageLayout::EDIT_FLG_DEFAULT)
            ->setDeviceType($DeviceType);

        $results = $this->connection->fetchAll($sql, array('url' => $PageLayout->getUrl()));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($PageLayout);
        }

        $PageLayout = new PageLayout();
        $PageLayout
            ->setName('商品購入/ヤマト決済画面')
            ->setUrl('yamato_shopping_payment')
            ->setFileName('YamatoPayment/shopping_payment')
            ->setMetaRobots('noindex')
            ->setEditFlg(PageLayout::EDIT_FLG_DEFAULT)
            ->setDeviceType($DeviceType);

        $results = $this->connection->fetchAll($sql, array('url' => $PageLayout->getUrl()));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($PageLayout);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * ブロック登録
     *
     * @return void
     */
    protected function addBlock()
    {
        $sql = 'SELECT * FROM dtb_block WHERE file_name = :file_name';

        /** @var DeviceType $deviceType */
        $DeviceType = $this->app['eccube.repository.master.device_type']->find(10);

        $Block = new Block();
        $Block
            ->setDeviceType($DeviceType)
            ->setName('クロネコ代金後払い決済入力フォーム')
            ->setFileName('YamatoPayment/payment_deferred')
            ->setLogicFlg(0)
            ->setDeletableFlg(0);

        $results = $this->connection->fetchAll($sql, array('file_name' => $Block->getFileName()));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Block);
        }

        $Block = new Block();
        $Block
            ->setDeviceType($DeviceType)
            ->setName('クレジットカード決済入力フォーム')
            ->setFileName('YamatoPayment/payment_credit')
            ->setLogicFlg(0)
            ->setDeletableFlg(0);

        $results = $this->connection->fetchAll($sql, array('file_name' => $Block->getFileName()));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Block);
        }

        $Block = new Block();
        $Block
            ->setDeviceType($DeviceType)
            ->setName('コンビニ決済入力フォーム')
            ->setFileName('YamatoPayment/payment_conveni')
            ->setLogicFlg(0)
            ->setDeletableFlg(0);

        $results = $this->connection->fetchAll($sql, array('file_name' => $Block->getFileName()));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Block);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * 商品CSVデータ追加
     */
    protected function addProductCsv()
    {
        $sql = 'SELECT * FROM dtb_csv WHERE csv_type = :csv_type AND field_name =:field_name';

        $entity_name = 'Plugin\\YamatoPayment\\Entity\\YamatoProduct';
        /** @var CsvType $CsvType */
        $CsvType = $this->app['eccube.repository.master.csv_type']->find(CsvType::CSV_TYPE_PRODUCT);

        $Member = $this->getDummyMember();

        /** @var Csv $Csv */
        $Csv = $this->app['eccube.repository.csv']->findOneBy(
            array('CsvType' => $CsvType),
            array('rank' => 'DESC')
        );
        $rank = $Csv->getRank() + 1;

        $Csv = new Csv();
        $Csv
            ->setCsvType($CsvType)
            ->setCreator($Member)
            ->setEntityName($entity_name)
            ->setFieldName('reserve_date')
            ->setDispName('予約商品出荷予定日')
            ->setRank($rank)
            ->setEnableFlg(Constant::DISABLED);

        $results = $this->connection->fetchAll($sql, array(
            'csv_type' => CsvType::CSV_TYPE_PRODUCT,
            'field_name' => 'reserve_date',
        ));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Csv);
            $rank += 1;
        }

        $Csv = new Csv();
        $Csv
            ->setCsvType($CsvType)
            ->setCreator($Member)
            ->setEntityName($entity_name)
            ->setFieldName('not_deferred_flg')
            ->setDispName('後払い不可商品')
            ->setRank($rank)
            ->setEnableFlg(Constant::DISABLED);

        $results = $this->connection->fetchAll($sql, array(
            'csv_type' => CsvType::CSV_TYPE_PRODUCT,
            'field_name' => 'not_deferred_flg',
        ));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Csv);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * 受注CSVデータ追加
     */
    protected function addOrderCsv()
    {
        $sql = 'SELECT * FROM dtb_csv WHERE csv_type = :csv_type AND field_name =:field_name';

        $entity_name = 'Plugin\\YamatoPayment\\Entity\\YamatoOrderScheduledShippingDate';
        /** @var CsvType $CsvType */
        $CsvType = $this->app['eccube.repository.master.csv_type']->find(CsvType::CSV_TYPE_ORDER);

        $Member = $this->getDummyMember();

        /** @var Csv $Csv */
        $Csv = $this->app['eccube.repository.csv']->findOneBy(
            array('CsvType' => $CsvType),
            array('rank' => 'DESC')
        );
        $rank = $Csv->getRank() + 1;

        $Csv = new Csv();
        $Csv
            ->setCsvType($CsvType)
            ->setCreator($Member)
            ->setEntityName($entity_name)
            ->setFieldName('scheduled_shipping_date')
            ->setDispName('出荷予定日')
            ->setRank($rank)
            ->setEnableFlg(Constant::DISABLED);

        $results = $this->connection->fetchAll($sql, array(
            'csv_type' => CsvType::CSV_TYPE_ORDER,
            'field_name' => 'scheduled_shipping_date',
        ));
        if (count($results) == 0) {
            $this->app['orm.em']->persist($Csv);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * 配送方法登録（予約商品用）
     *
     * @param array $paymentIdList
     * @return void
     */
    protected function addReserveDeliveryData($paymentIdList)
    {
        $sql = 'SELECT * FROM dtb_delivery WHERE del_flg = 0 AND product_type_id = 9625';
        $results = $this->connection->fetchAll($sql);

        // データが存在する場合は、以下を処理しない
        if (count($results) > 0) {
            return null;
        }

        // 配送業者登録（予約商品用）
        $Delivery = $this->addReserveDelivery();

        // 配送方法/支払方法紐づけ登録
        $this->addPaymentOption($Delivery, $paymentIdList);

        // 配送料金マスター登録
        $this->addDeliveryFee($Delivery);
    }

    /**
     * 配送業者登録（予約商品用）
     *
     * @return Delivery
     */
    protected function addReserveDelivery()
    {
        $Member = $this->getDummyMember();
        /** @var ProductType $ProductType */
        $ProductType = $this->app['eccube.repository.master.product_type']->find(9625);
        /** @var Delivery $Delivery */
        $Delivery = $this->app['eccube.repository.delivery']->findOneBy(array(), array('rank' => 'DESC'));
        $rank = $Delivery->getRank() + 1;

        $Delivery = new Delivery();
        $Delivery->setName('予約商品配送業者');
        $Delivery->setServiceName('予約商品配送業者');
        $Delivery
            ->setCreator($Member)
            ->setProductType($ProductType)
            ->setRank($rank)
            ->setDelFlg(0);

        $this->app['orm.em']->persist($Delivery);
        $this->app['orm.em']->flush();

        return $Delivery;
    }

    /**
     * 配送方法/支払方法紐づけ登録
     *
     * @param Delivery $Delivery
     * @param array $paymentIdList
     * @return void
     */
    protected function addPaymentOption($Delivery, $paymentIdList)
    {
        foreach ($paymentIdList as $paymentId) {
            $this->app['orm.em']->getConnection()->insert(
                'dtb_payment_option', array(
                'delivery_id' => $Delivery->getId(),
                'payment_id' => $paymentId,
            ));
        }
    }

    /**
     * 送料登録
     *
     * @param Delivery $Delivery
     * @return void
     */
    protected function addDeliveryFee($Delivery)
    {
        $Prefs = $this->app['eccube.repository.master.pref']->findBy(array(), array('rank' => 'ASC'));

        foreach ($Prefs as $Pref) {
            /** @var Pref $Pref */
            $DeliveryFee = new DeliveryFee();
            $DeliveryFee
                ->setDelivery($Delivery)
                ->setPref($Pref)
                ->setFee(0);
            $this->app['orm.em']->persist($DeliveryFee);
        }

        $this->app['orm.em']->flush();
    }

    /**
     * 支払い方法登録
     *
     * @return array
     */
    protected function addPayment()
    {
        $ret = array();

        $sql = 'SELECT * FROM plg_yamato_payment_method WHERE memo03 = :memo03';

        // クレジットカード決済
        $results = $this->connection->fetchAll($sql, array('memo03' => 10));
        if (count($results) == 0) {
            $Payment = $this->app['eccube.repository.payment']->findOrCreate(0);
            $Payment
                ->setMethod('クレジットカード決済')
                ->setRuleMin(1)
                ->setRuleMax(300000)
                ->setCharge(null)
                ->setRank(0)
                ->setChargeFlg(Constant::DISABLED)
                ->setDelFlg(Constant::ENABLED);
            $this->app['orm.em']->persist($Payment);
            $this->app['orm.em']->flush($Payment);

            $YamatoPaymentMethod = new YamatoPaymentMethod();
            $YamatoPaymentMethod
                ->setId($Payment->getId())
                ->setMethod('クレジットカード決済')
                ->setMemo03(10);
            $this->app['orm.em']->persist($YamatoPaymentMethod);
            $this->app['orm.em']->flush();

            $ret[] = $Payment->getId();
        } else {
            $ret[] = $results[0]['payment_id'];
        }

        // コンビニ決済
        $results = $this->connection->fetchAll($sql, array('memo03' => 30));
        if (count($results) == 0) {
            $Payment = $this->app['eccube.repository.payment']->findOrCreate(0);
            $Payment
                ->setMethod('コンビニ決済')
                ->setRuleMin(1)
                ->setRuleMax(300000)
                ->setCharge(0)
                ->setRank(0)
                ->setDelFlg(Constant::ENABLED);
            $this->app['orm.em']->persist($Payment);
            $this->app['orm.em']->flush($Payment);

            $YamatoPaymentMethod = new YamatoPaymentMethod();
            $YamatoPaymentMethod
                ->setId($Payment->getId())
                ->setMethod('コンビニ決済')
                ->setMemo03(30);
            $this->app['orm.em']->persist($YamatoPaymentMethod);

            $this->app['orm.em']->flush();

            $ret[] = $Payment->getId();
        } else {
            $ret[] = $results[0]['payment_id'];
        }

        // クロネコ代金後払い決済
        $results = $this->connection->fetchAll($sql, array('memo03' => 60));
        if (count($results) == 0) {
            $Payment = $this->app['eccube.repository.payment']->findOrCreate(0);
            $Payment
                ->setMethod('クロネコ代金後払い決済')
                ->setRuleMin(1)
                ->setRuleMax(50000)
                ->setCharge(0)
                ->setRank(0)
                ->setDelFlg(Constant::ENABLED);
            $this->app['orm.em']->persist($Payment);
            $this->app['orm.em']->flush($Payment);

            $YamatoPaymentMethod = new YamatoPaymentMethod();
            $YamatoPaymentMethod
                ->setId($Payment->getId())
                ->setMethod('クロネコ代金後払い決済')
                ->setMemo03(60);
            $this->app['orm.em']->persist($YamatoPaymentMethod);

            $this->app['orm.em']->flush();

            $ret[] = $Payment->getId();
        } else {
            $ret[] = $results[0]['payment_id'];
        }

        return $ret;
    }


    /**
     * 配送完了メールテンプレート登録
     *
     * @return void
     */
    protected function addDelivCompleteMailTemplate()
    {
        // 配送完了メール
        $sql = 'SELECT * FROM dtb_mail_template WHERE template_id = 9625';

        $results = $this->connection->fetchAll($sql);
        if (count($results) > 0) {
            return;
        }

        $header = 'この度はご注文いただき誠にありがとうございます。
下記ご注文の配送が完了しました。
';

        $footer = '
============================================


このメッセージはお客様へのお知らせ専用ですので、
このメッセージへの返信としてご質問をお送りいただいても回答できません。
ご了承ください。

ご質問やご不明な点がございましたら、こちらからお願いいたします。

';

        $this->app['orm.em']->getConnection()->insert(
            'dtb_mail_template',
            array(
                'template_id' => 9625,
                'name' => '配送完了メール',
                'file_name' => 'Mail/order.twig',
                'subject' => '配送完了しました',
                'header' => $header,
                'footer' => $footer,
                'creator_id' => 1,
                'del_flg' => 0,
                'create_date' => new \DateTime(),
                'update_date' => new \DateTime(),
            ),
            array(
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                'datetime',
                'datetime',
            )
        );
    }

    /**
     * メンバーダミーデータ取得
     *
     * @return Member
     */
    protected function getDummyMember()
    {
        /** @var SoftDeleteFilter $softDeleteFilter */
        $softDeleteFilter = $this->app['orm.em']->getFilters()->getFilter('soft_delete');
        $originExcludes = $softDeleteFilter->getExcludes();

        $softDeleteFilter->setExcludes(array(
            'Eccube\Entity\Member',
        ));

        /** @var Member $member */
        $Member = $this->app['eccube.repository.member']->find(1);

        $softDeleteFilter->setExcludes($originExcludes);

        return $Member;
    }

}
