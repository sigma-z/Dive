<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test;


use Dive\Exception;
use Dive\Generator\Formatter\FormatterInterface;
use Dive\Generator\Formatter\PhpClassFormatter;
use Dive\Generator\ModelGenerator;
use Dive\RecordManager;
use Dive\TestSuite\TestCase;

/**
 * @author  Steven Nikolic <steven@nindoo.de>
 * @created 29.11.13
 */
class ModelGeneratorTest extends TestCase
{

    const MODEL_PATH = 'Model';

    /**
     * used as test values
     */
    const DATE = '15.11.13';

    const MAIL = 'sigma_z@sigma-scripts.de';

    const AUTHOR = 'Steffen Zeidler';

    /**
     * @var ModelGenerator
     */
    private $modelGenerator;


    /**
     * @param string[] $expectedExistingModels
     * @param string[] $expectedNotExistingModels
     * @param string   $targetDirectory
     * @dataProvider provideIteration
     */
    public function testGetNeededModels(
        array $expectedExistingModels,
        array $expectedNotExistingModels,
        $targetDirectory
    ) {
        $schema = $this->getSchema();

        $actualNeededModels = $this->modelGenerator->getNeededModels($schema);
        $expectedModels = array_merge($expectedExistingModels, $expectedNotExistingModels);
        $this->assertEquals($expectedModels, $actualNeededModels, '', 0, 10, true);

        $this->assertStringEndsWith(self::MODEL_PATH, $targetDirectory);
        $actualModelClasses = $this->modelGenerator->getExistingModelClasses($targetDirectory);
        $this->assertEquals($expectedExistingModels, $actualModelClasses, '', 0, 10, true);

        $actualMissingModels = $this->modelGenerator->getMissingModels($schema, $targetDirectory);
        $this->assertEquals($expectedNotExistingModels, $actualMissingModels, '', 0, 10, true);
    }


    /**
     * @param string[] $expectedExistingModels
     * @param string[] $expectedNotExistingModels
     * @param string   $targetDirectory
     * @dataProvider provideIteration
     */
    public function testCreateNeededModels(
        /** @noinspection PhpUnusedParameterInspection */
        array $expectedExistingModels,
        array $expectedNotExistingModels,
        $targetDirectory
    ) {
        $schema = $this->getSchema();
        $missingModels = $this->modelGenerator->getMissingModels($schema, $targetDirectory);
        $formatter = $this->getFormatter();
        $this->assertEquals($expectedNotExistingModels, $missingModels, '', 0, 10, true);
        $fileNames = array();
        $modelFileNames = array();
        foreach ($missingModels as $missingModel) {
            $fileName = $formatter->getTargetFileName($missingModel, $targetDirectory);
            $this->assertFileNotExists($fileName);
            $fileNames[] = $fileName;
            $modelFileNames[$fileName] = $missingModel;
        }

        // create files
        $this->modelGenerator->writeMissingModelFiles($schema, $targetDirectory);

        foreach ($fileNames as $fileName) {
            // check created
            $this->assertFileExists($fileName);
            $classContent = $this->modelGenerator->getContent($modelFileNames[$fileName], $schema);
            $this->assertStringEqualsFile($fileName, $classContent);

            // remove created
            unlink($fileName);
            $this->assertFileNotExists($fileName);
        }
    }


    /**
     * @expectedException \Dive\Exception
     */
    public function testGetExistingModelClassesThrowsException()
    {
        $this->modelGenerator->getExistingModelClasses('NOT_EXISTING_FOLDER');
    }


    /**
     * @param string   $modelClassName
     * @param string   $expectedClassName
     * @param string[] $expectedProperties
     * @param string[] $expectedUse
     * @dataProvider provideCreateClassFile
     */
    public function testCreateClassFile(
        $modelClassName,
        $expectedClassName,
        array $expectedProperties,
        array $expectedUse
    ) {
        $use = $expectedUse;
        $properties = $expectedProperties;
        $className = $expectedClassName;

        foreach ($properties as $varName => $type) {
            $properties[$varName] = " * @property $type \$$varName";
        }
        foreach ($use as $key => $value) {
            $use[$key] = "use $value;";
        }
        $license = explode(PHP_EOL, $this->getLicense(PHP_EOL));
        foreach ($license as $key => $value) {
            $license[$key] = " " . trim("* $value");
        }
        $date = self::DATE;
        $author = self::AUTHOR;
        $mail = self::MAIL;
        $expectedContent = '<?php' . PHP_EOL
            . '/*' . PHP_EOL
            . implode(PHP_EOL, $license) . PHP_EOL
            . ' */' . PHP_EOL
            . PHP_EOL
            . 'namespace Dive\TestSuite\Model;' . PHP_EOL
            . '' . PHP_EOL
            . implode(PHP_EOL, $use) . PHP_EOL
            . '' . PHP_EOL
            . '/**' . PHP_EOL
            . " * @author  {$author} <{$mail}>" . PHP_EOL
            . " * @created {$date}" . PHP_EOL
            . ' *' . PHP_EOL
            . implode(PHP_EOL, $properties) . PHP_EOL
            . ' */' . PHP_EOL
            . 'class ' . $className . ' extends Record' . PHP_EOL
            . '{' . PHP_EOL
            . '' . PHP_EOL
            . '}';

        $schema = $this->getSchema();
        $actualClassFile = $this->modelGenerator->getContent($modelClassName, $schema);

        $this->assertSame($expectedContent, $actualClassFile);
    }


    /**
     * @expectedException \Dive\Exception
     */
    public function testClassFileWithInvalidModelThrowsException()
    {
        $this->modelGenerator->getContent('notExistingModelName', $this->getSchema());
    }


    /**
     * @expectedException \Dive\Exception
     */
    public function testFormatterThrowsExceptionInClassFileWithoutClassName()
    {
        $formatter = $this->getFormatter();
        $formatter->getFileContent('NotExistingClassName');
    }


    /**
     * @return PhpClassFormatter
     */
    private function getFormatter()
    {
        return new PhpClassFormatter();
    }


    /**
     * @return array
     */
    public function provideCreateClassFile()
    {
        return array(
            array(
                '\Dive\TestSuite\Model\Article2tag',
                'Article2tag',
                array(
                    'article_id' => 'string',
                    'tag_id' => 'string',
                    'Article' => 'Article',
                    'Tag' => 'Tag',
                ),
                array('Dive\Record'),
            ),
            array(
                '\Dive\TestSuite\Model\Author',
                'Author',
                array(
                    'id' => 'string',
                    'firstname' => 'string',
                    'lastname' => 'string',
                    'email' => 'string',
                    'user_id' => 'string',
                    'editor_id' => 'string',
                    'Article' => 'Article[]|RecordCollection',
                    'Author' => 'Author[]|RecordCollection',
                    'User' => 'User',
                    'Editor' => 'Author',
                ),
                array('Dive\Record', 'Dive\Collection\RecordCollection'),
            ),
            array(
                '\Dive\TestSuite\Model\Article',
                'Article',
                array(
                    'id' => 'string',
                    'author_id' => 'string',
                    'is_published' => 'bool',
                    'title' => 'string',
                    'teaser' => 'string',
                    'text' => 'string',
                    'changed_on' => 'string',
                    'Author' => 'Author',
                    'Comment' => 'Comment[]|RecordCollection',
                    'Article2tagHasMany' => 'Article2tag[]|RecordCollection',
                ),
                array('Dive\Record', 'Dive\Collection\RecordCollection'),
            ),
            array(
                '\Dive\TestSuite\Model\Donation',
                'Donation',
                array(
                    'id' => 'string',
                    'article_id' => 'string',
                    'author_id' => 'string',
                    'comment_id' => 'string',
                    'is_cancelled' => 'bool',
                    'value' => 'float',
                ),
                array('Dive\Record'),
            ),
        );
    }


    /**
     * @return array
     */
    public function provideIteration()
    {
        $directorySeparator = DIRECTORY_SEPARATOR;
        $testBaseDirectory = __DIR__ . $directorySeparator . '..' . $directorySeparator;
        $path = realpath($testBaseDirectory . 'TestSuite' . $directorySeparator . self::MODEL_PATH);
        return array(
            array(
                array(
                    '\Dive\TestSuite\Model\Article',
                    '\Dive\TestSuite\Model\Article2tag',
                    '\Dive\TestSuite\Model\Author',
                    '\Dive\TestSuite\Model\Comment',
                    '\Dive\TestSuite\Model\Tag',
                    '\Dive\TestSuite\Model\User',
                ),
                array(
                    '\Dive\TestSuite\Model\Donation',
                ),
                $path
            )
        );
    }


    protected function setUp()
    {
        parent::setUp();

        $recordManager = $this->createDefaultRecordManager();
        $formatter = $this->getFormatter();
        $this->modelGenerator = $this->createModelGenerator($recordManager, $formatter);
    }


    /**
     * @param RecordManager      $recordManager
     * @param FormatterInterface $formatter
     * @return ModelGenerator
     */
    private function createModelGenerator(RecordManager $recordManager, FormatterInterface $formatter)
    {
        $modelGenerator = new ModelGenerator($recordManager, $formatter);
        return $modelGenerator->setLicense($this->getLicense(PHP_EOL))
            ->setEndOfLine(PHP_EOL)
            ->setAuthor(self::AUTHOR, self::MAIL)
            ->setDate(self::DATE);
    }


    /**
     * @param string $eol
     * @return string
     */
    private function getLicense($eol = PHP_EOL)
    {
        return "This file is included in the generation test of the Dive ORM framework." . $eol
            . "(c) Steffen Zeidler <sigma_z@sigma-scripts.de>" . $eol
            . $eol
            . "For the full copyright and license information, please view the LICENSE" . $eol
            . "file that was distributed with this source code.";
    }

}