<?php

require_once dirname( __FILE__ ) . '/webdav_property_test.php';

class ezcWebdavResourceTypePropertyTest extends ezcWebdavWebdavPropertyTestCase
{
    public static function suite()
    {
		return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    protected function setUp()
    {
        $this->className = 'ezcWebdavResourceTypeProperty';
        $this->propertyName = 'resourcetype';
        $this->defaultValues = array(
            'type' => null,
        );
        $this->workingValues = array(
            'type' => array(
                null,
                '',
                'Foo Bar Baz',
            ),
        );
        $this->failingValues = array(
            'type' => array(
                23,
                23.34,
                true,
                false,
                array( 23, 42 ),
                new stdClass(),
            ),
        );
    }
}

?>
