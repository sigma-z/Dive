<?php

/** @var $loader \Composer\Autoload\ClassLoader */
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/TestSuite/ClassLoader.php';

define('FIXTURE_DIR', __DIR__ . '/fixtures');

use Dive\TestSuite\TestCase;

$classLoader = new Dive\TestSuite\ClassLoader();
$classLoader->setNamespace('Dive\TestSuite', __DIR__ . '/TestSuite');
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
