<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Test\Schema;

use Dive\Schema\Schema;
use Dive\Schema\SchemaWriter;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 02.11.12
 */
class SchemaWriterTest extends \PHPUnit_Framework_TestCase
{

    public function testGetContent()
    {
        // prepare test
        $schemaFile = FIXTURE_DIR . '/schema.php';
        /** @noinspection PhpIncludeInspection */
        $schemaDefinition = include $schemaFile;
        $schema = new Schema($schemaDefinition);

        // execute unit
        $schemaWriter = new SchemaWriter($schema);
        $code = $schemaWriter->getContent();

        // assert expectation
        $this->assertStringStartsWith("<?php\n", $code);
        $actual = eval(str_replace("<?php\n", '', $code));
        $this->assertEquals($schemaDefinition, $actual);
    }

}