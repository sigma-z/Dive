<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test;


use Dive\ModelGenerator;
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


    public function testCreatedModelGenerator()
    {
        $this->assertInstanceOf('\Dive\ModelGenerator', $this->modelGenerator);
    }


    /**
     * @param string[] $expectedModels
     * @dataProvider provideIteration
     */
    public function testGetNeededModels(array $expectedModels)
    {
        $actualNeededModels = $this->modelGenerator->getNeededModels($this->getSchema());
        $this->assertEquals($expectedModels, $actualNeededModels, '', 0, 10, true);
    }


    /**
     * @param string[] $expectedModels
     * @param string   $targetDirectory
     * @dataProvider provideIteration
     */
    public function testGetExistingModelClasses(array $expectedModels, $targetDirectory)
    {
        $this->assertStringEndsWith(self::MODEL_PATH, $targetDirectory);
        $actualModelClasses = $this->modelGenerator->getExistingModelClasses($targetDirectory);
        $this->assertEquals($expectedModels, $actualModelClasses, '', 0, 10, true);
    }


    /**
     * @expectedException \Dive\Exception
     */
    public function testGetExistingModelClassesThrowsException()
    {
        $this->modelGenerator->getExistingModelClasses('NOT_EXISTING_FOLDER');
    }


    /**
     * @param string $modelClassName
     * @param string $expectedContent
     * @dataProvider provideCreateClassFile
     */
    public function testCreateClassFile($modelClassName, $expectedContent)
    {
        $replaces = array(
            '{date}' => self::DATE,
            '{author}' => self::AUTHOR,
            '{mail}' => self::MAIL
        );
        $expectedContent = str_replace(array_keys($replaces), array_values($replaces), $expectedContent);

        $schema = $this->getSchema();

        $actualClassFile = $this->modelGenerator->createClassFile($modelClassName, $schema);

        $this->assertSame($expectedContent, $actualClassFile);
    }


    /**
     * @return array
     */
    public function provideCreateClassFile()
    {
        return array(
            array(
                '\Dive\TestSuite\Model\Article2tag',
                '<?php' . PHP_EOL
                    . '/*' . PHP_EOL
                    . ' * This file is part of the Dive ORM framework.' . PHP_EOL
                    . ' * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>' . PHP_EOL
                    . ' *' . PHP_EOL
                    . ' * For the full copyright and license information, please view the LICENSE' . PHP_EOL
                    . ' * file that was distributed with this source code.' . PHP_EOL
                    . ' */' . PHP_EOL
                    . PHP_EOL
                    . 'namespace Dive\TestSuite\Model;' . PHP_EOL
                    . '' . PHP_EOL
                    . 'use Dive\Record;' . PHP_EOL
                    . '' . PHP_EOL
                    . '/**' . PHP_EOL
                    . ' * @author  {author} <{mail}>' . PHP_EOL
                    . ' * @created {date}' . PHP_EOL
                    . ' *' . PHP_EOL
                    . ' * @property string $article_id' . PHP_EOL
                    . ' * @property string $tag_id' . PHP_EOL
                    . ' * @property Article $Article' . PHP_EOL
                    . ' * @property Tag $Tag' . PHP_EOL
                    . ' */' . PHP_EOL
                    . 'class Article2tag extends Record' . PHP_EOL
                    . '{' . PHP_EOL
                    . '' . PHP_EOL
                    . '}'
            ),
            array(
                '\Dive\TestSuite\Model\Author',
                '<?php' . PHP_EOL
                    . '/*' . PHP_EOL
                    . ' * This file is part of the Dive ORM framework.' . PHP_EOL
                    . ' * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>' . PHP_EOL
                    . ' *' . PHP_EOL
                    . ' * For the full copyright and license information, please view the LICENSE' . PHP_EOL
                    . ' * file that was distributed with this source code.' . PHP_EOL
                    . ' */' . PHP_EOL
                    . PHP_EOL
                    . 'namespace Dive\TestSuite\Model;' . PHP_EOL
                    . '' . PHP_EOL
                    . 'use Dive\Record;' . PHP_EOL
                    . 'use Dive\Collection\RecordCollection;' . PHP_EOL
                    . '' . PHP_EOL
                    . '/**' . PHP_EOL
                    . ' * @author  {author} <{mail}>' . PHP_EOL
                    . ' * @created {date}' . PHP_EOL
                    . ' *' . PHP_EOL
                    . ' * @property string $id' . PHP_EOL
                    . ' * @property string $firstname' . PHP_EOL
                    . ' * @property string $lastname' . PHP_EOL
                    . ' * @property string $email' . PHP_EOL
                    . ' * @property string $user_id' . PHP_EOL
                    . ' * @property string $editor_id' . PHP_EOL
                    . ' * @property Article[]|RecordCollection $Article' . PHP_EOL
                    . ' * @property Author[]|RecordCollection $Author' . PHP_EOL
                    . ' * @property User $User' . PHP_EOL
                    . ' * @property Author $Editor' . PHP_EOL
                    . ' */' . PHP_EOL
                    . 'class Author extends Record' . PHP_EOL
                    . '{' . PHP_EOL
                    . '' . PHP_EOL
                    . '}'
            ),
            array(
                '\Dive\TestSuite\Model\Article',
                '<?php' . PHP_EOL
                    . '/*' . PHP_EOL
                    . ' * This file is part of the Dive ORM framework.' . PHP_EOL
                    . ' * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>' . PHP_EOL
                    . ' *' . PHP_EOL
                    . ' * For the full copyright and license information, please view the LICENSE' . PHP_EOL
                    . ' * file that was distributed with this source code.' . PHP_EOL
                    . ' */' . PHP_EOL
                    . PHP_EOL
                    . 'namespace Dive\TestSuite\Model;' . PHP_EOL
                    . '' . PHP_EOL
                    . 'use Dive\Record;' . PHP_EOL
                    . 'use Dive\Collection\RecordCollection;' . PHP_EOL
                    . '' . PHP_EOL
                    . '/**' . PHP_EOL
                    . ' * @author  {author} <{mail}>' . PHP_EOL
                    . ' * @created {date}' . PHP_EOL
                    . ' *' . PHP_EOL
                    . ' * @property string $id' . PHP_EOL
                    . ' * @property string $author_id' . PHP_EOL
                    . ' * @property bool $is_published' . PHP_EOL
                    . ' * @property string $title' . PHP_EOL
                    . ' * @property string $teaser' . PHP_EOL
                    . ' * @property string $text' . PHP_EOL
                    . ' * @property string $changed_on' . PHP_EOL
                    . ' * @property Author $Author' . PHP_EOL
                    . ' * @property Comment[]|RecordCollection $Comment' . PHP_EOL
                    . ' * @property Article2tag[]|RecordCollection $Article2tagHasMany' . PHP_EOL
                    . ' */' . PHP_EOL
                    . 'class Article extends Record' . PHP_EOL
                    . '{' . PHP_EOL
                    . '' . PHP_EOL
                    . '}'
            ),
        );
    }


    /**
     * @return array
     */
    public function provideIteration()
    {
        $testBaseDirectory = __DIR__ . DIRECTORY_SEPARATOR . '../' . DIRECTORY_SEPARATOR;
        $path = realpath($testBaseDirectory . 'TestSuite' . DIRECTORY_SEPARATOR . self::MODEL_PATH);
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
                $path
            )
        );
    }


    protected function setUp()
    {
        parent::setUp();

        $recordManager = $this->createDefaultRecordManager();
        $this->modelGenerator = $this->createModelGenerator($recordManager);
    }


    /**
     * @param RecordManager $recordManager
     * @return ModelGenerator
     */
    private function createModelGenerator(RecordManager $recordManager)
    {
        $modelGenerator = new ModelGenerator($recordManager);

        $license = "This file is part of the Dive ORM framework." . PHP_EOL
            . "(c) Steffen Zeidler <sigma_z@sigma-scripts.de>" . PHP_EOL
            . PHP_EOL
            . "For the full copyright and license information, please view the LICENSE" . PHP_EOL
            . "file that was distributed with this source code.";
        $modelGenerator->setLicense($license);
        $modelGenerator->setEndOfLine(PHP_EOL);
        $modelGenerator->setAuthor(self::AUTHOR, self::MAIL);
        $modelGenerator->setDate(self::DATE);
        return $modelGenerator;
    }

}