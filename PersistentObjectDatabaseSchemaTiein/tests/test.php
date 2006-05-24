<?php
/**
 * ezcPersistentObjectDatabaseSchemaTieinTest 
 * 
 * @package PersistentObjectDatabaseSchemaTiein
 * @subpackage Tests
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Test suite for ezcConsoleOutput class.
 * 
 * @package PersistentObjectDatabaseSchemaTiein
 * @subpackage Tests
 */
class ezcPersistentObjectDatabaseSchemaTieinTest extends ezcTestCase
{
    private $results;

	public static function suite()
	{
		return new ezcTestSuite( "ezcPersistentObjectDatabaseSchemaTieinTest" );
	}

    /**
     * setUp 
     * 
     * @access public
     */
    public function setUp()
    {
        $this->results = require dirname( __FILE__ ) . "/data.php";
    }

    /**
     * tearDown 
     * 
     * @access public
     */
    public function tearDown()
    {
    }

    public function testNoParameters()
    {
        $res = `php PersistentObjectDatabaseSchemaTiein/src/rungenerator.php`;
        $this->assertEquals( $this->results[__FUNCTION__], $res, "Error output incorrect with no parameters." );
    }

    public function testOnlySourceParameter()
    {
        $res = `php PersistentObjectDatabaseSchemaTiein/src/rungenerator.php -s test`;
        // file_put_contents( __FUNCTION__, $res );
        $this->assertEquals( $this->results[__FUNCTION__], $res, "Error output incorrect with no parameters." );
    }

    public function testOnlyFormatParameter()
    {
        $res = `php PersistentObjectDatabaseSchemaTiein/src/rungenerator.php -f test`;
        // file_put_contents( __FUNCTION__, $res );
        $this->assertEquals( $this->results[__FUNCTION__], $res, "Error output incorrect with no parameters." );
    }

    public function testFormatSourceParameter()
    {
        $res = `php PersistentObjectDatabaseSchemaTiein/src/rungenerator.php -f test -s test`;
        // file_put_contents( __FUNCTION__, $res );
        $this->assertEquals( $this->results[__FUNCTION__], $res, "Error output incorrect with no parameters." );
    }

    public function testInvalidFormat()
    {
        $res = `php PersistentObjectDatabaseSchemaTiein/src/rungenerator.php -f test -s test test`;
        // file_put_contents( __FUNCTION__, $res );
        $this->assertEquals( $this->results[__FUNCTION__], $res, "Error output incorrect with no parameters." );
    }

    public function testInvalidSource()
    {
        $res = `php PersistentObjectDatabaseSchemaTiein/src/rungenerator.php -f xml -s test test`;
        // file_put_contents( __FUNCTION__, $res );
        $this->assertEquals( $this->results[__FUNCTION__], $res, "Error output incorrect with no parameters." );
    }

    public function testInvalidDestination()
    {
        $source = dirname( __FILE__ ) . "/data/webbuilder.schema.xml";
        $res = `php PersistentObjectDatabaseSchemaTiein/src/rungenerator.php -f xml -s $source test`;
        // file_put_contents( __FUNCTION__, $res );
        $this->assertEquals( $this->results[__FUNCTION__], $res, "Error output incorrect with no parameters." );
    }

    public function testValid()
    {
        $source = dirname( __FILE__ ) . "/data/webbuilder.schema.xml";
        $destination = $this->createTempDir( "PersObjDatSchem" );
        $res = `php PersistentObjectDatabaseSchemaTiein/src/rungenerator.php -f xml -s $source $destination`;
        // file_put_contents( __FUNCTION__, $res );

        // Sanitize because of temp dir name
        $res = explode( "\n", $res );
        unset( $res[3], $res[4] );
        $res = implode( "\n", $res );
        
        $this->assertEquals( $this->results[__FUNCTION__], $res, "Error output incorrect with no parameters." );

        foreach ( glob( dirname( __FILE__ ) . "/data/*.php" ) as $file )
        {
            $this->assertEquals(
                file_get_contents( $file ),
                file_get_contents( $destination . "/" . basename( $file ) ),
                "Geneator generated an invalid persistent object definition file."
            );
        }

        $this->removeTempDir();
    }
}
?>
