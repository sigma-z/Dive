<?php

require __DIR__ . '/TestSuite/ClassLoader.php';

define('FIXTURE_DIR', __DIR__ . '/fixtures');
if (!defined('VENDOR_DIR')) {
    define('VENDOR_DIR', 'vendor');
}

use Dive\TestSuite\TestCase;

$extLibDir = __DIR__ . '/../' . VENDOR_DIR;

$classLoader = new Dive\TestSuite\ClassLoader();
$classLoader->setNamespace('Dive\TestSuite', __DIR__ . '/TestSuite');
$classLoader->setNamespace('Dive', __DIR__ . '/../lib');
$classLoader->setNamespace('Symfony', $extLibDir);
$classLoader->setNamespaceOmission('Dive\TestSuite');
$classLoader->register();

$databases = TestCase::getDatabases();
$schema = TestCase::getSchema();
foreach ($databases as $database) {
    $conn = TestCase::createDatabaseConnection($database);
    $dbInit = new \Dive\TestSuite\DbInit($conn, $schema);
    $dbInit->init();
    $conn->disconnect();
}
