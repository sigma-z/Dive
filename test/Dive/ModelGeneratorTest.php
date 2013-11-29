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

    /**
     * @var RecordManager
     */
    private $rm;


    public function testCreatedRecordManager()
    {
        $this->assertInstanceOf('\Dive\RecordManager', $this->rm);
    }


    public function testCreatedModelGenerator()
    {
        $this->assertInstanceOf('\Dive\ModelGenerator', new ModelGenerator($this->rm));
    }


    /**
     * @param string[] $expectedModels
     * @dataProvider provideIteration
     */
    public function testGetNeededModels(array $expectedModels)
    {
        $generator = new ModelGenerator($this->rm);
        $actualNeededModels = $generator->getNeededModels($this->getSchema());
        $this->assertEquals($expectedModels, $actualNeededModels, '', 0, 10, true);
    }


    /**
     * @param string[] $expectedModels
     * @param string   $targetDirectory
     * @dataProvider provideIteration
     */
    public function testGetExistingModelClasses(array $expectedModels, $targetDirectory)
    {
        $modelGenerator = new ModelGenerator($this->rm);
        $actualModelClasses = $modelGenerator->getExistingModelClasses($targetDirectory);
        $this->assertEquals($expectedModels, $actualModelClasses, '', 0, 10, true);
    }


    /**
     * @param string $modelClassName
     * @param string $expectedContent
     * @dataProvider provideCreateClassFile
     */
    public function testCreateClassFile($modelClassName, $expectedContent)
    {
        $date = '15.11.13';
        $author = 'Steffen Zeidler <sigma_z@sigma-scripts.de>';
        $expectedContent = str_replace(array('{date}', '{author}'), array($date, $author), $expectedContent);

        $modelGenerator = new ModelGenerator($this->rm);
        $actualClassFile = $modelGenerator->createClassFile(
            $modelClassName,
            $date,
            $author,
            $this->getSchema(),
            PHP_EOL
        );

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
                    . ' * @author  {author}' . PHP_EOL
                    . ' * @created {date}' . PHP_EOL
                    . ' *' . PHP_EOL
                    . ' * @property integer $article_id' . PHP_EOL
                    . ' * @property integer $tag_id' . PHP_EOL
                    . ' * @property Article $Article' . PHP_EOL
                    . ' * @property Tag $Tag' . PHP_EOL
                    . ' */' . PHP_EOL
                    . 'class Article2tag extends Record' . PHP_EOL
                    . '{' . PHP_EOL
                    . '' . PHP_EOL
                    . '}'
            )
        );
    }


    /**
     * @return array
     */
    public function provideIteration()
    {
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
                '../TestSuite/Model'
            )
        );
    }


    protected function setUp()
    {
        parent::setUp();

        // record manager
        $this->rm = $this->createDefaultRecordManager();
    }

}