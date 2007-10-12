<?php
/**
 * File containing the ezcWebdavOutputResult struct.
 *
 * @package Webdav
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Display information.
 *
 * Used by {@link ezcWebdavTransport} to transport information on displaying a
 * response to the browser.
 *
 * @version //autogentag//
 * @package Webdav
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
class ezcWebdavOutputResult
{
    /**
     * Response code
     * 
     * @var string
     */
    public $status;

    /**
     * Response headers
     * 
     * @var array
     */
    public $headers;

    /**
     * Response body
     * 
     * @var string
     */
    public $body;
    
    /**
     * Creates a new struct.
     * 
     * @param string $status 
     * @param array $header 
     * @param string $body 
     * @return void
     */
    public function __construct( $status = '', array $header = array(), $body = '' )
    {
        $this->status = $status;
        $this->header = $header;
        $this->body   = $body;
    }
}

?>
