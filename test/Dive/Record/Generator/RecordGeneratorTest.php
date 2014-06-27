<?php
 /*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Dive\Test\Record\Generator;

use Dive\Record\Generator\RecordGenerator;
use Dive\RecordManager;
use Dive\TestSuite\TestCase;
use Dive\Util\FieldValuesGenerator;

/**
 * Class RecordGeneratorTest
 *
 * @author  Steffen Zeidler <sigma_z@sigma-scripts.de>
 */
class RecordGeneratorTest extends TestCase
{

    /** @var RecordGenerator */
    private $recordGenerator;

    /** @var RecordManager */
    private $rm;


    /**
     * @dataProvider provideGenerate
     * @param array $tablesRows
     * @param array $tablesMappingField
     * @param array $expectedTablesCounts
     */
    public function testGenerate(array $tablesRows, array $tablesMappingField, array $expectedTablesCounts)
    {
        $this->givenRecordGenerator();
        $this->givenTablesRows($tablesRows, $tablesMappingField);
        $this->whenGeneratingRecords();
        $this->thenItShouldHaveGeneratedRecordsWithTablesCounts($expectedTablesCounts);
    }


    /**
     * @dataProvider provideGenerate
     * @param array $tablesRows
     * @param array $tablesMappingField
     * @param array $expectedTablesCounts
     */
    public function testGetGeneratedRecordIds(array $tablesRows, array $tablesMappingField, array $expectedTablesCounts)
    {
        $this->givenRecordGenerator();
        $this->givenTablesRows($tablesRows, $tablesMappingField);
        $this->whenGeneratingRecords();
        $this->thenItShouldHaveGeneratedTheRecords($expectedTablesCounts);
    }


    /**
     * @dataProvider provideGenerate
     * @param array $tablesRows
     * @param array $tablesMappingField
     * @param array $expectedTablesCounts
     */
    public function testRemoveGeneratedRecords(
        array $tablesRows, array $tablesMappingField, array $expectedTablesCounts
    )
    {
        $this->testGenerate($tablesRows, $tablesMappingField, $expectedTablesCounts);
        $this->whenRemovingGeneratedRecords();

        $expectedTablesCounts = array_combine(
            array_keys($expectedTablesCounts),
            array_fill(0, count($expectedTablesCounts), 0)
        );
        $this->thenItShouldHaveGeneratedRecordsWithTablesCounts($expectedTablesCounts);
    }


    /**
     * @return array[]
     */
    public function provideGenerate()
    {
        $testCases = array();

        $testCases[] = array(
            'tablesRows' => array(
                'user' => array('JohnD', 'JamieTK', 'AdamE', 'BartS', 'CelineH'),
            ),
            'tablesMappingField' => array('user' => 'username'),
            'expectedTableCount' => array(
                'user' => 5,
                'author' => 0
            )
        );
        $testCases[] = array(
            'tablesRows' => array(
                'user' => array('JohnD', 'JamieTK', 'AdamE', 'BartS', 'CelineH'),
                'author' => array(
                    'John Doe' => array(
                        'firstname' => 'John',
                        'lastname' => 'Doe',
                        'email' => 'j.doe@example.com',
                        'User' => 'JohnD'
                    ),
                    'Jamie T. Kirk' => array(
                        'firstname' => 'Jamie T',
                        'lastname' => 'Kirk',
                        'email' => 'j.t.kirk@example.com',
                        'User' => 'JamieTK'
                    ),
                    'Bart Simon' => array(
                        'firstname' => 'Bart',
                        'lastname' => 'Simon',
                        'email' => 'b.simon@example.com',
                        'User' => 'BartS',
                        'Editor' => 'John Doe'
                    )
                )
            ),
            'tablesMappingField' => array('user' => 'username'),
            'expectedTableCount' => array(
                'user' => 5,
                'author' => 3
            )
        );
        $testCases[] = array(
            'tablesRows' => array(
                'author' => array(
                    'John Doe' => array(
                        'firstname' => 'John',
                        'lastname' => 'Doe',
                        'email' => 'j.doe@example.com',
                    )
                )
            ),
            'tablesMappingField' => array(),
            'expectedTableCount' => array(
                'user' => 1,
                'author' => 1
            )
        );
        $testCases[] = array(
            'tablesRows' => array(
                'article' => array(
                    'ArticleOfSomeRandomAuthor' => array()
                )
            ),
            'tablesMappingField' => array(),
            'expectedTableCount' => array(
                'user' => 1,
                'author' => 1,
                'article' => 1
            )
        );
        $testCases[] = array(
            'tablesRows' => array(
                'author' => array(
                    'John Doe' => array(
                        'firstname' => 'John',
                        'lastname' => 'Doe',
                        'email' => 'j.doe@example.com',
                    ),
                ),
                'article' => array(
                    'ArticleOfSomeRandomAuthor' => array()
                )
            ),
            'tablesMappingField' => array(),
            'expectedTableCount' => array(
                'user' => 2,
                'author' => 2,
                'article' => 1
            )
        );

        return $testCases;
    }


    /**
     * @expectedException \Dive\Record\Generator\RecordGeneratorException
     */
    public function testGenerateThrowsExceptionWhenRelatedRowDoesMatchForeignKey()
    {
        $this->givenRecordGenerator();

        $tablesRows = array(
            'user' => array('JohnD'),
            'author' => array(
                'John Doe' => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'j.doe@example.com',
                    'User' => 'wrongKey'
                )
            )
        );
        $this->givenTablesRows($tablesRows, array('user' => 'username'));
        $this->whenGeneratingRecords();
    }


    /**
     * @expectedException \Dive\Record\Generator\RecordGeneratorException
     */
    public function testGenerateThrowsExceptionWhenMissingTableMapField()
    {
        $this->givenRecordGenerator();
        $this->givenTablesRows(array('user' => array('JohnD')), array());
        $this->whenGeneratingRecords();
    }


    public function testGenerateRelatedReferencedRelationRecord()
    {
        $this->givenRecordGenerator();

        $kirkKey = 'Kirk';
        $doeKey = 'Doe';

        $tablesRows = array(
            'author' => array(
                $kirkKey => array(
                    'firstname' => 'Jamie T',
                    'lastname' => 'Kirk',
                    'email' => 'j.t.kirk@example.com'
                ),
                $doeKey => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'j.doe@example.com',
                    'Editor' => $kirkKey
                ),
            )
        );
        $this->givenTablesRows($tablesRows, array('author' => 'lastname'));

        $this->whenGeneratingRecords();

        $editor = $this->thenThereShouldBeAnEditorLinkedWith('Doe');
        $kirk = $tablesRows['author'][$kirkKey];
        $this->then_ShouldBeEqual($kirk, $editor);
    }


    public function testGenerateRelatedOwningRelationRecord()
    {
        $this->givenRecordGenerator();

        $doeKey = 'Doe';
        $kirkKey = 'Kirk';

        $tablesRows = array(
            'author' => array(
                $kirkKey => array(
                    'firstname' => 'Jamie T',
                    'lastname' => 'Kirk',
                    'email' => 'j.t.kirk@example.com'
                ),
                $doeKey => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'j.doe@example.com',
                    'Author' => array($kirkKey)
                ),
            )
        );
        $this->givenTablesRows($tablesRows, array('author' => 'lastname'));

        $this->whenGeneratingRecords();
        $kirk = $tablesRows['author'][$kirkKey];
        $author = $this->thenThereShouldBeOneAuthorLinkedWithDoe();
        $this->then_ShouldBeEqual($kirk, $author);
    }


    public function testGenerateRelatedOwningRelationRecordAlternateOrderOfTableRows()
    {
        $this->givenRecordGenerator();

        $doeKey = 'Doe';
        $kirkKey = 'Kirk';

        $tablesRows = array(
            'author' => array(
                $doeKey => array(
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'j.doe@example.com',
                    'Author' => array($kirkKey)
                ),
                $kirkKey => array(
                    'firstname' => 'Jamie T',
                    'lastname' => 'Kirk',
                    'email' => 'j.t.kirk@example.com'
                ),
            )
        );
        $this->givenTablesRows($tablesRows, array('author' => 'lastname'));

        $this->whenGeneratingRecords();
        $doe = $tablesRows['author'][$doeKey];
        $editor = $this->thenThereShouldBeAnEditorLinkedWith('Kirk');
        $this->then_ShouldBeEqual($doe, $editor);
    }

    private function givenRecordGenerator()
    {
        $this->rm = self::createDefaultRecordManager();
        $fieldValuesGenerator = new FieldValuesGenerator();
        $this->recordGenerator = new RecordGenerator($this->rm, $fieldValuesGenerator);
    }


    /**
     * @param array $tablesRows
     * @param array $tablesMappingField
     */
    private function givenTablesRows(array $tablesRows, array $tablesMappingField)
    {
        $this->recordGenerator->setTablesRows($tablesRows);
        $this->recordGenerator->setTablesMapField($tablesMappingField);
    }


    private function whenGeneratingRecords()
    {
        $this->recordGenerator->generate();
    }


    /**
     * @param array $expectedTablesCounts
     */
    private function thenItShouldHaveGeneratedRecordsWithTablesCounts(array $expectedTablesCounts)
    {
        foreach ($expectedTablesCounts as $tableName => $expectedTableCounts) {
            $actual = $this->rm->getTable($tableName)->createQuery()->countByPk();
            $this->assertEquals($expectedTableCounts, $actual);
        }
    }


    private function whenRemovingGeneratedRecords()
    {
        $this->recordGenerator->removeGeneratedRecords();
    }


    /**
     * @param array $expectedTablesCounts
     */
    private function thenItShouldHaveGeneratedTheRecords($expectedTablesCounts)
    {
        foreach ($expectedTablesCounts as $tableName => $expectedTablesCount) {
            $recordIds = $this->recordGenerator->getRecordIds($tableName);
            $this->assertCount($expectedTablesCount, $recordIds);
        }
    }


    /**
     * @return array
     */
    private function thenThereShouldBeOneAuthorLinkedWithDoe()
    {
        $author = $this->rm->createQuery('author', 'e')
            ->select("a.id, a.firstname, a.lastname, a.email")
            ->leftJoin('e.Author a')
            ->where('e.lastname = ?', 'Doe')
            ->andWhere("a.id IS NOT NULL")
            ->fetchArray();
        $this->assertTrue((bool)$author);
        $this->assertCount(1, $author);
        return $author[0];
    }


    /**
     * @return array
     */
    private function thenThereShouldBeAnEditorLinkedWith($lastname)
    {
        $editor = $this->rm->createQuery('author', 'a')
            ->select("e.id, e.firstname, e.lastname, e.email")
            ->leftJoin('a.Editor e')
            ->where('a.lastname = ?', $lastname)
            ->andWhere("e.id IS NOT NULL")
            ->fetchArray();
        $this->assertTrue((bool)$editor);
        $this->assertCount(1, $editor);
        return $editor[0];
    }


    /**
     * @param $expectedAuthor
     * @param $actualAuthor
     */
    private function then_ShouldBeEqual($expectedAuthor, $actualAuthor)
    {
        $this->assertEquals($expectedAuthor['firstname'], $actualAuthor['firstname']);
        $this->assertEquals($expectedAuthor['lastname'], $actualAuthor['lastname']);
        $this->assertEquals($expectedAuthor['email'], $actualAuthor['email']);
    }
}
