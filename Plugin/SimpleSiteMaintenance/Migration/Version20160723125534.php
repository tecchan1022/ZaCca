<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use \Plugin\SimpleSiteMaintenance\Migration\MigrationSupport;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160723125534 extends MigrationSupport
{

    private function initTable()
    {
        $this->createTables = array(
            'plg_ssm_config' => array(
                array('ssm_id', 'integer', array('autoincrement' => true), true),
                array('mente_mode', 'smallint', array('notnull' => true)),
                array('admin_close_flg', 'smallint', array('notnull' => true)),
                array('page_html', 'text', array('notnull' => true)),
            ),
        );

        $this->updateTables = array();
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->initTable();
        parent::up($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->initTable();
        parent::down($schema);
    }
}
