<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @created 01.03.13
 */

namespace Dive\TestSuite;

use Dive\Table;

class DatasetTestCase extends TestCase
{

    /**
     * @var DatasetRegistry
     */
    private static $datasetRegistryTestCase = null;
    /**
     * @var DatasetRegistry
     */
    private static $datasetRegistryTestClass = null;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$datasetRegistryTestClass = new DatasetRegistry();
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        self::removeDatasets(self::$datasetRegistryTestClass);
    }


    protected function setUp()
    {
        parent::setUp();
        self::$datasetRegistryTestCase = new DatasetRegistry();
    }


    protected function tearDown()
    {
        parent::tearDown();
        self::removeDatasets(self::$datasetRegistryTestCase);
    }


    /**
     * Inserts new data record to database
     *
     * @param  \Dive\Table      $table
     * @param  array            $data
     * @param  DatasetRegistry  $datasetRegistry
     * @return string
     */
    protected static function insertDataset(Table $table, array $data, DatasetRegistry $datasetRegistry = null)
    {
        $conn = $table->getConnection();
        $affectedRows = $conn->insert($table, $data);
        if ($affectedRows == 1) {
            $id = $conn->getLastInsertId();
            if (null === $datasetRegistry) {
                $datasetRegistry = self::$datasetRegistryTestCase;
            }
            $datasetRegistry->add($table, $id);
            return $id;
        }
        return false;
    }


    /**
     * Removes datasets from registry
     *
     * @param DatasetRegistry $registry
     */
    protected static function removeDatasets(DatasetRegistry $registry)
    {
        $tables = $registry->getTables();
        foreach ($tables as $table) {
            $datasetIds = $registry->getByTable($table);
            $conn = $table->getConnection();
            foreach ($datasetIds as $id) {
                $conn->delete($table, $id);
            }
        }
    }

}