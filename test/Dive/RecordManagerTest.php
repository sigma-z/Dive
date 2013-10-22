<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test;

use Dive\Hydrator\HydratorInterface;
use Dive\RecordManager;
use Dive\TestSuite\TestCase;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 24.11.12
 */
class RecordManagerTest extends TestCase
{

    /**
     * @var RecordManager
     */
    private $rm;


    protected function setUp()
    {
        parent::setUp();

        // record manager
        $this->rm = $this->createDefaultRecordManager();
    }


    public function testCreatedRecordManager()
    {
        $this->assertInstanceOf('\Dive\RecordManager', $this->rm);
    }


    public function testGetTable()
    {
        $table = $this->rm->getTable('user');
        $this->assertInstanceOf('\Dive\Table', $table);
    }


    public function testGetTableRepository()
    {
        $repository = $this->rm->getTableRepository('user');
        $this->assertInstanceOf('\Dive\Table\Repository', $repository);
    }


    /**
     * @param string $hydratorName
     * @param string $expectedHydratorClassName
     *
     * @dataProvider provideGetDiveDefinedHydrator
     */
    public function testGetDiveDefinedHydrator($hydratorName, $expectedHydratorClassName)
    {
        $collHydrator = $this->rm->getHydrator($hydratorName);
        $this->assertInstanceOf($expectedHydratorClassName, $collHydrator);
    }


    /**
     * @return array
     */
    public function provideGetDiveDefinedHydrator()
    {
        return array(
            array(RecordManager::FETCH_RECORD_COLLECTION, '\Dive\Hydrator\RecordCollectionHydrator'),
            array(RecordManager::FETCH_RECORD, '\Dive\Hydrator\RecordHydrator'),
            array(RecordManager::FETCH_ARRAY, '\Dive\Hydrator\ArrayHydrator'),
            array(RecordManager::FETCH_SINGLE_ARRAY, '\Dive\Hydrator\SingleArrayHydrator'),
            array(RecordManager::FETCH_SCALARS, '\Dive\Hydrator\ScalarHydrator'),
            array(RecordManager::FETCH_SINGLE_SCALAR, '\Dive\Hydrator\SingleScalarHydrator'),
        );
    }


    /**
     * @expectedException \Dive\Exception
     */
    public function testGetDiveDefinedHydratorNotExistingException()
    {
        $this->rm->getHydrator('notexistingname');
    }


    public function testGetSchema()
    {
        $schema = $this->rm->getSchema();
        $this->assertInstanceOf('\Dive\Schema\Schema', $schema);
    }


    public function testSetCustomHydrator()
    {
        /** @var HydratorInterface $customHydrator */
        $customHydrator = $this->getMockForAbstractClass('\Dive\Hydrator\HydratorInterface');
        $this->rm->setHydrator('custom', $customHydrator);
        $actualCustomHydrator = $this->rm->getHydrator('custom');
        $this->assertEquals($customHydrator, $actualCustomHydrator);
    }


    /**
     * @expectedException \Dive\Schema\SchemaException
     */
    public function testTableNotFoundException()
    {
        $this->rm->getTable('notexistingtablename');
    }


}
