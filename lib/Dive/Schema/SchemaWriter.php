<?php
/*
 * This file is part of the Dive ORM framework.
 * (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dive\Schema;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 02.11.12
 */
class SchemaWriter
{

    /**
     * @var Schema
     */
    private $schema = null;


    /**
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }


    /**
     * writes schema
     *
     * @param  string $file
     * @return bool
     * @throws SchemaException
     */
    public function write($file)
    {
        $code = $this->getContent();
        $bytesWritten = file_put_contents($file, $code);
        return $bytesWritten > 0;
    }


    /**
     * gets schema formatted content
     *
     * @return string
     */
    public function getContent()
    {
        $schemaDefinition = $this->schema->toArray();
        $content = \Dive\Util\VarExport::export($schemaDefinition, array('removeLastComma' => true));
        $content = "<?php\n\nreturn $content;";
        return $content;
    }

}
