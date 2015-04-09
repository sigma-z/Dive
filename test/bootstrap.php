<?php

/** @var $loader \Composer\Autoload\ClassLoader */
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/TestSuite/ClassLoader.php';

define('FIXTURE_DIR', __DIR__ . '/fixtures');

use Dive\TestSuite\TestCase;

echo 'PHP.version=' . PHP_VERSION . "\n";

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

    $platform = $conn->getDriver()->getPlatform();
    echo get_class($platform) . '.version=' . $conn->querySingleScalar($platform->getVersionSql()) . "\n";

    $conn->disconnect();
}
