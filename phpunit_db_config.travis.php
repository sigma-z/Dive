<?php
return array_merge(
    include 'phpunit_db_config.travis.sqlite.php',
    include 'phpunit_db_config.travis.mysql.php'
);
