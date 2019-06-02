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

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160122173000 extends AbstractMigration
{

    public function up(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {

        $app = new \Eccube\Application();
        $app->boot();

        $pluginCode = 'GoogleAnalyticsSimpleSetup';
        $pluginName = 'GoogleAnalytics簡単設置プラグイン';
        $datetime   = date('Y-m-d H:i:s');
        $select = "SELECT count(*) as cnt FROM dtb_google_analytics_ss WHERE plugin_code = '$pluginCode'";
        $count = $this->connection->fetchColumn($select);

        if ($count == 0){
            $insert = "INSERT INTO dtb_google_analytics_ss(
                                plugin_code, plugin_name, del_flg, create_date, update_date)
                        VALUES ('$pluginCode', '$pluginName', '0', '$datetime', '$datetime'
                                );";
            $this->connection->executeUpdate($insert);
            $count = $this->connection->fetchColumn($select);
        }
    }

    public function down(Schema $schema)
    {
    }

}
