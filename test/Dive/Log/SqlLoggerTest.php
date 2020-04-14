<?php
/*
* This file is part of the Dive ORM framework.
* (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Dive\Test\Log;

use Dive\Log\SqlLogger;
use Dive\TestSuite\TestCase;

/**
 * @author Steven Nikolic <steven@nindoo.de>
 * Date: 23.10.13
 */
class SqlLoggerTest extends TestCase
{
    /**
     * @var SqlLogger
     */
    private $logger;

    /**
     * @var int
     */
    private $expectedLoggedQueries = 0;


    protected function setUp()
    {
        parent::setUp();
        $this->logger = new SqlLogger();
    }


    /**
     * @param bool $disable
     * @param bool $echoOutput
     * @dataProvider provideLogEntry
     */
    public function testLogEntry($disable, $echoOutput)
    {
        ob_start();
        $this->logger->dumpQuery(0);
        $this->expectOutputString($echoOutput ? null : '');
        $this->logger->setEchoOutput($echoOutput);
        $this->logger->setDisabled($disable);
        $this->assertEquals($this->expectedLoggedQueries, $this->logger->getCount());
        $this->logger->startQuery('SELECT * FROM author');
        if (!$disable) {
            $this->expectedLoggedQueries++;
        }
        $this->assertEquals(!$disable, $this->logger->isEnabled());
        $this->assertEquals($this->expectedLoggedQueries, $this->logger->getCount());
        $this->logger->stopQuery();
        $this->assertEquals($this->expectedLoggedQueries, $this->logger->getCount());
        $this->assertInternalType('float', $this->logger->getLastQueryExecutionTime());
        $this->assertInternalType('float', $this->logger->getOverallQueryExectuionTime());

        $this->assertCount($this->logger->getCount(), $this->logger->getQueries());
        ob_end_clean();
    }


    /**
     * @return array
     */
    public function provideLogEntry()
    {
        return array(
            array(true, true),
            array(false, true),
            array(true, false),
            array(false, false)
        );
    }


    public function testAccessingNotExistingEntry()
    {
        $logger = new SqlLogger();
        ob_start();
        $logger->dumpQuery(0);
        $logger->startQuery('SELECT * FROM author');
        $logger->stopQuery();
        $logger->dumpQuery(300);
        $content = ob_get_clean();
        self::assertContains('no queries logged yet', $content);
        self::assertContains('Query 300 was not logged', $content);
    }


    public function testClear()
    {
        $this->logger->clear();
        $this->assertAttributeEmpty('queries', $this->logger);
        $this->assertAttributeEmpty('current', $this->logger);
    }
}
