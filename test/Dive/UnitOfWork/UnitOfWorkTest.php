<?php
/*
 * EcoWebDesk
 * Copyright(c) EcoIntense GmbH
 * kontakt@ecointense.de
 *
 * http://www.ecointense.de/
 */


namespace Dive\Test\UnitOfWork;

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
     * @throws \Dive\Table\TableException
     */
    public function testInsertRecordWithIdentifier($database, $tableName, $givenId = null, $expectedId = null, $expectedException = null)
    {
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
    }


    public function provideInsertRecordWithIdentifier()
    {
        $testCases = [];

        // important: these test cases are dependent on each other and order!
        // first author => expected id '1'
        $testCases['autoincrement-without-id'] = ['author', null, '1'];
        // second author => expected id '2'
        $testCases['autoincrement-without-id2'] = ['author', null, '2'];
        // third author with given id => expected same id // @see #20
        $testCases['autoincrement-with-id'] = ['author', '12345', '12345'];
        // fourth author => expected id '12346' (next autoincrement after given id
        $testCases['autoincrement-without-id3'] = ['author', null, '12346'];

        // @see #19
        $testCases['non-autoincrement-without-id'] = ['no_autoincrement_test', null, null, RecordInvalidException::class];
        $testCases['non-autoincrement-with-id'] = ['no_autoincrement_test', 'abc', 'abc'];

        return self::getDatabaseAwareTestCases($testCases);
    }
}
