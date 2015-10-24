<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dive\Test\Record;

use Dive\Exception;
use Dive\Table;
use Dive\Record;
use Dive\TestSuite\TestCase;
use Dive\Util\FieldValuesGenerator;

class SelfReferenceTest extends TestCase
{
    /** @var Table */
    protected $table;
    /** @var  Record */
    protected $record;


    public function __call($name, $arguments)
    {
        $class = __CLASS__;
        $this->markTestIncomplete("incomplete: missing function '$name' ('$class')");
    }


    public function testSelfReferenceMakesRecordInvalid()
    {
        $this->givenIHaveTableWithParentRelation();
        $this->givenIHaveCreatedAValidRecordForThisTable();

        $this->whenILinkRecordAsParent();
        $this->thenRecordHasSelfReference();

        // TODO: not working yet
        $this->markTestIncomplete('currently relation cleared on save. Discuss behavior!');
        $this->thenTheRecordShouldBeInvalid();
        $this->thenSaveShouldFail();
    }


    private function givenIHaveTableWithParentRelation()
    {
        $this->table = self::createDefaultRecordManager()->getTable('tree_node');
    }


    private function givenIHaveCreatedAValidRecordForThisTable()
    {
        $generator = new FieldValuesGenerator();
        $data = $generator->getRequiredRandomRecordData($this->table->getFields());
        $this->record = $this->table->createRecord($data);
        $this->thenTheRecordShouldBeValid();
    }


    private function whenILinkRecordAsParent()
    {
        $this->record->Parent = $this->record;
    }


    private function thenTheRecordShouldBeInvalid()
    {
        $this->assertFalse($this->isRecordValid());
    }


    private function thenTheRecordShouldBeValid()
    {
        $this->assertTrue($this->isRecordValid());
    }


    private function thenSaveShouldFail()
    {
        $e = null;
        try {
            $rm = $this->record->getRecordManager();
            $rm->scheduleSave($this->record);
            $rm->commit();
        }
        catch (Exception $e) {
            // see assert
        }
        $this->assertNotNull($e, 'record was saved: ' . print_r($this->table->createQuery()->fetchArray(), true));
    }


    /**
     * @return mixed
     */
    private function isRecordValid()
    {
        return $this->record->getRecordManager()->getRecordValidationContainer()->validate($this->record);
    }


    private function thenRecordHasSelfReference()
    {
        $this->assertEquals($this->record, $this->record->Parent);
    }
}
