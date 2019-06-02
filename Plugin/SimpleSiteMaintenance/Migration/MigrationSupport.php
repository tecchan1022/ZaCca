<?php
/*
 * Copyright(c) 2016 SYSTEM_KD
 */

namespace Plugin\SimpleSiteMaintenance\Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;


/**
 * マイグレーションサポート
 *
 * @author systemkd
 *
 */
abstract class MigrationSupport extends AbstractMigration
{

    protected $createTables;

    protected $updateTables;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // テーブル追加
        $this->tableCreate($schema);

        // テーブル更新
        $this->updateTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // テーブル削除
        $this->dropTable($schema);
    }

    /**
     * テーブル生成
     *
     * @param Schema $schema
     */
    private function tableCreate(Schema $schema) {

        foreach ($this->createTables as $key => $createTable) {

            $tableName = $key;

            $table = $schema->createTable($tableName);

            // カラム追加
            $arrPkey = array();
            foreach ($createTable as $columns) {

                if(count($columns) < 3) {
                    continue;
                }

                $table->addColumn($columns[0], $columns[1], $columns[2]);

                if(count($columns) == 4) {
                    $arrPkey[] = $columns[0];
                }
            }

            if(count($arrPkey) > 0) {
                $table->setPrimaryKey($arrPkey);
            }
        }

    }

    /**
     * テーブル削除
     *
     * @param Schema $schema
     */
    private function dropTable(Schema $schema) {

        foreach ($this->createTables as $key => $createTable) {

            $tableName = $key;

            $schema->dropTable($tableName);
        }

    }

    /**
     * テーブル更新(Alter)
     * @param Schema $schema
     */
    private function updateTable(Schema $schema) {

        foreach ($this->updateTables as $key => $createTable) {

            $tableName = $key;
            $table = $schema->getTable($tableName);

            // カラム追加
            $arrPkey = array();
            foreach ($createTable as $columns) {

                if(count($columns) < 3) {
                    continue;
                }

                $table->addColumn($columns[0], $columns[1], $columns[2]);

            }
        }
    }
}