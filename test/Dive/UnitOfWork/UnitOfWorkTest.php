<?php
/*
 * EcoWebDesk
 * Copyright(c) EcoIntense GmbH
 * kontakt@ecointense.de
 *
 * http://www.ecointense.de/
 */


namespace Dive\Test\UnitOfWork;

use Dive\Table\TableException;
use Dive\TestSuite\DbInit;
use Dive\TestSuite\TestCase;
use Dive\Validation\RecordInvalidException;

/**
 * @author gladysch
 * @date   06.05.2019
 */
class UnitOfWorkTest extends TestCase
{

    /**
     * @dataProvider provideInsertRecordWithIdentifier
     * @param array $database
     * @param string $tableName
     * @param string|null $givenId
     * @param string|null $expectedId
     * @param null $expectedException
     * @return array
     * @throws TableException
     */
    public function testInsertRecordWithIdentifier($database, $tableName, $givenId = null, $expectedId = null, $expectedException = null)
    {
        $this->reInitDatabase($database);

        if ($expectedException !== null) {
            $this->setExpectedException($expectedException);
        }

        $recordManager = self::createRecordManager($database);

        $recordGenerator = self::createRecordGenerator($recordManager);
        $recordId = $recordGenerator->generateRecord($tableName, ['id' => $givenId]);
        $record = $recordManager->getTableRepository($tableName)->getByInternalId($recordId);

        $this->assertSame($expectedId, $record->get('id'));
        $recordManager->scheduleSave($record)->commit();
        $this->assertSame($expectedId, $record->get('id'));

        return [
            $recordManager->getConnection(),
            $tableName,
            $expectedId
        ];
    }


    /**
     * not working: @depends      testInsertRecordWithIdentifier
     *
     * @dataProvider provideInsertRecordWithIdentifier
     * @param array $database
     * @param string $tableName
     * @param string|null $givenId
     * @param string|null $expectedId
     * @param null $expectedException
     * @throws TableException
     */
    public function testLastInsertIdOfConnection($database, $tableName, $givenId = null, $expectedId = null, $expectedException = null)
    {
        list($connection, $tableName, $expectedId) = $this->testInsertRecordWithIdentifier(
            $database, $tableName, $givenId, $expectedId, $expectedException
        );
        $this->assertSame($expectedId, $connection->getLastInsertId($tableName));
    }


    public function provideInsertRecordWithIdentifier()
    {
        $testCases = [];

        // first author => expected id '1'
        $testCases['autoincrement-without-id'] = ['author', null, '1'];
        // second author with given id => expected same id // @see #20
        $testCases['autoincrement-with-id'] = ['author', '12345', '12345'];

        // @see #19
        $testCases['non-autoincrement-without-id'] = ['no_autoincrement_test', null, null, RecordInvalidException::class];
        $testCases['non-autoincrement-with-id'] = ['no_autoincrement_test', 'abc', 'abc'];

        return self::getDatabaseAwareTestCases($testCases);
    }


    private function reInitDatabase(array $database)
    {
        $connection = self::createDatabaseConnection($database);

        $dbInit = new DbInit($connection, self::getSchema());
        $dbInit->initSchema();
    }

}
