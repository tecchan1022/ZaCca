<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

$loader = require __DIR__ . '/../../../../autoload.php';
$loader->add('Eccube\Tests', __DIR__ . '/../../../../tests');
$loader->add('Plugin\YamatoPayment', __DIR__);
if (file_exists(sys_get_temp_dir() . '/migrations.sql')) {
    unlink(sys_get_temp_dir() . '/migrations.sql');
}
