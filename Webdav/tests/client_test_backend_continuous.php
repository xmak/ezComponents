<?php

require_once 'client_test_setup.php';

class ezcWebdavClientTestContinuousSetup extends ezcWebdavClientTestSetup
{
    protected static $pathFactory;

    protected static $backend;

    protected static $lastTestSuite;

    public static function performSetup( ezcWebdavClientTest $test, $testSetName )
    {
        if ( basename( dirname( $testSetName ) ) !== self::$lastTestSuite )
        {
            self::$lastTestSuite = basename( dirname( $testSetName ) );
            self::$pathFactory   = new ezcWebdavBasicPathFactory( 'http://webdav' );
            self::$backend       = self::setupBackend();
        }

        $test->server  = self::getServer( self::$pathFactory );
        $test->backend = self::$backend;
    }

    protected static function setupBackend()
    {
        $backend = new ezcWebdavMemoryBackend();
        $backend->options->fakeLiveProperties = true;
        $backend->addContents(
            array(
                'test_collection' => array(
                    'foo.txt'  => 'Test foo content',
                    'bar'      => 'Test bar content',
                    'baz_coll' => array(
                        'baz_1.html' => '<html></html>',
                        'baz_2.html' => '<html><body><h1>Test</h1></body></html>',
                    ),
                ),
            )
        );

        // Make GET requests work

        $backend->setProperty(
            '/test_collection/foo.txt',
            new ezcWebdavGetContentTypeProperty(
                'text/plain', 'utf-8'
            )
        );
        $backend->setProperty(
            '/test_collection/bar',
            new ezcWebdavGetContentTypeProperty(
                'text/plain', 'utf-8'
            )
        );
        $backend->setProperty(
            '/test_collection/baz_coll/baz_1.html',
            new ezcWebdavGetContentTypeProperty(
                'text/html', 'utf-8'
            )
        );
        $backend->setProperty(
            '/test_collection/baz_coll/baz_2.html',
            new ezcWebdavGetContentTypeProperty(
                'text/xhtml', 'utf-8'
            )
        );
        return $backend;
    }
}

?>
