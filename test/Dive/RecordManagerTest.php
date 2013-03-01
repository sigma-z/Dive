<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 24.11.12
 */

namespace Dive\Test;

use Dive\RecordManager;


class RecordManagerTest extends \Dive\TestSuite\TestCase
{

    /**
     * @var \Dive\RecordManager
     */
    private $rm;


    protected function setUp()
    {
        parent::setUp();

        // record manager
        $this->rm = $this->getDefaultRecordManager();
    }


    public function testGetTable()
    {
        $table = $this->rm->getTable('user');
        $this->assertInstanceOf('\Dive\Table', $table);
    }


    public function testGetDiveDefinedHydrator()
    {
        $collHydrator = $this->rm->getHydrator(RecordManager::FETCH_RECORD_COLLECTION);
        $this->assertInstanceOf('\Dive\Hydrator\RecordCollectionHydrator', $collHydrator);
    }


    public function testSetCustomHydrator()
    {
        /** @var \Dive\Hydrator\HydratorInterface $customHydrator */
        $customHydrator = $this->getMockForAbstractClass('\Dive\Hydrator\HydratorInterface');
        $this->rm->setHydrator('custom', $customHydrator);
        $actualCustomHydrator = $this->rm->getHydrator('custom');
        $this->assertEquals($customHydrator, $actualCustomHydrator);
    }

}
