<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Table\Behaviour;

use Dive\Table\Behaviour\Timestampable;
use Dive\TestSuite\TestCase;

/**
 * Class TimestampableTest
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class TimestampableTest extends TestCase
{

    /** @var Timestampable */
    private $timestampable;


    protected function setUp()
    {
        parent::setUp();
        $this->timestampable = new Timestampable();
    }


    public function testGetTimestamp()
    {
        $actual = $this->timestampable->getTimestamp();
        $this->assertEquals(date('Y-m-d H:i:s'), $actual);
    }


    public function testTimestampOnInsert()
    {
        $this->markTestIncomplete();
        $rm = self::createDefaultRecordManager();

    }


    public function testTimestampOnSave()
    {
        $this->markTestIncomplete();
    }


    public function testTimestampOnUpdate()
    {
        $this->markTestIncomplete();
    }

}
