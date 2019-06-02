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

class Version20150808173000 extends AbstractMigration
{

    public function up(Schema $schema)
    {
        $this->createPluginConfigTable($schema);
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('dtb_google_analytics_ss');
    }

    protected function createPluginConfigTable(Schema $schema)
    {
        $table = $schema->createTable("dtb_google_analytics_ss");
        $table->addColumn('plugin_id', 'integer', array('autoincrement' => true));
        $table->addColumn('plugin_code', 'text', array(
                'notnull' => true,
        ));
        $table->addColumn('plugin_name', 'text', array(
                'notnull' => true,
        ));
        $table->addColumn('config_data', 'text', array(
            'notnull' => false,
        ));
        $table->addColumn('del_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));
        $table->addColumn('create_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));
        $table->addColumn('update_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));
        $table->setPrimaryKey(array('plugin_id'));
    }
}
