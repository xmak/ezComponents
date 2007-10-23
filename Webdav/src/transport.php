<?php
/**
 * File containing the basic standard compliant transport mecanism.
 *
 * @package Webdav
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * Transport layer mainclas that handles RFC compliant client communication.
 *
 * This basis transport class is able to interact with RFC 2518 compliant
 * WebDAV clients. It can parse all request types defined in the RFC into the
 * abstraction layer of the Webdav component, defined by the base classes
 * mentioned below.
 * 
 * To adjust this base transport layer main class to the needs of
 * RFC-2518-inconform client implementations, there is the powerfull
 * possibility of extending this class and overwriting certain necessary
 * protected methods. The easier way to adjust smaller issues is to replace one
 * of the helper components during construction of via property access.
 *
 * The {@link ezcWebdavServer->xmlTool} property will be used which is
 * accessed for different XML related operations. Exchanging this one will
 * allow you to manipulate the XML handling for the transport layer in
 * general.
 *
 * The {@link ezcWebdavServer->propertyHandler} property, of type {@link
 * ezcWebdavPropertyHandler} will be used in the accordingly named property and
 * is responsible for extracting WebDAV properties from a {@link DOMElement}
 * and to serialize them back to one.
 *
 * The {@link ezcWebdavServer->pathFactory} property must be an instance of
 * {@link ezcWebdavPathFactory} and is used to convert between internal WebDAV
 * pathes (resource locations understood by the {@link ezcWebdavBackend}) and
 * URIs that reference a resource on the web.
 *
 * An instance of this class is by default capable of parsing the follwoing
 * HTTP request methods:
 * <ul>
 * <li>COPY</li>
 * <li>DELETE</li>
 * <li>GET</li>
 * <li>HEAD</li>
 * <li>LOCK</li>
 * <li>MKCOL</li>
 * <li>MOVE</li>
 * <li>OPTIONS</li>
 * <li>PROPFIND</li>
 * <li>PROPPATCH'</li>
 * <li>PUT</li>
 * <li>UNLOCK</li>
 * </ul>
 *
 * The transport implementation is capable of handling the following response
 * classes and output the to the client:
 * <ul>
 * <li>{@link ezcWebdavCopyResponse}</li>
 * <li>{@link ezcWebdavDeleteResponse}</li>
 * <li>{@link ezcWebdavErrorResponse}</li>
 * <li>{@link ezcWebdavGetCollectionResponse}</li>
 * <li>{@link ezcWebdavGetResourceResponse}</li>
 * <li>{@link ezcWebdavHeadResponse}</li>
 * <li>{@link ezcWebdavMakeCollectionResponse}</li>
 * <li>{@link ezcWebdavMoveResponse}</li>
 * <li>{@link ezcWebdavMultistatusResponse}</li>
 * <li>{@link ezcWebdavOptionsResponse}</li>
 * <li>{@link ezcWebdavPropFindResponse}</li>
 * <li>{@link ezcWebdavPropPatchResponse}</li>
 * <li>{@link ezcWebdavPutResponse}</li>
 * </ul>
 *
 * @see ezcWebdavRequest
 * @see ezcWebdavResponse
 * @see ezcWebdavProperty
 *
 * @link http://tools.ietf.org/html/rfc2518 RFC 2518
 *
 * @version //autogentag//
 * @package Webdav
 */
class ezcWebdavTransport
{
    /**
     * Used for server software string in Server header. 
     */
    const VERSION = '//autogentag//';

    /**
     * Map of HTTP methods to object method names for parsing.
     *
     * Need public access here to retrieve this in {@link
     * ezcWebdavPluginRegistry}.
     *
     * @var array(string=>string)
     * @private
     */
    static public $parsingMap = array(
        'COPY'      => 'parseCopyRequest',
        'DELETE'    => 'parseDeleteRequest',
        'GET'       => 'parseGetRequest',
        'HEAD'      => 'parseHeadRequest',
        'LOCK'      => 'parseLockRequest',
        'MKCOL'     => 'parseMakeCollectionRequest',
        'MOVE'      => 'parseMoveRequest',
        'OPTIONS'   => 'parseOptionsRequest',
        'PROPFIND'  => 'parsePropFindRequest',
        'PROPPATCH' => 'parsePropPatchRequest',
        'PUT'       => 'parsePutRequest',
        'UNLOCK'    => 'parseUnlockRequest',
    );

    /**
     * Map of response objects to handling methods.
     *
     * Need public access here to retrieve this in {@link
     * ezcWebdavPluginRegistry}.
     *
     * @array(string=>string)
     * @private
     */
    static public $handlingMap = array(
        'ezcWebdavCopyResponse'           => 'processCopyResponse',
        'ezcWebdavDeleteResponse'         => 'processDeleteResponse',
        'ezcWebdavErrorResponse'          => 'processErrorResponse',
        'ezcWebdavGetCollectionResponse'  => 'processGetCollectionResponse',
        'ezcWebdavGetResourceResponse'    => 'processGetResourceResponse',
        'ezcWebdavHeadResponse'           => 'processHeadResponse',
        'ezcWebdavMakeCollectionResponse' => 'processMakeCollectionResponse',
        'ezcWebdavMoveResponse'           => 'processMoveResponse',
        'ezcWebdavMultistatusResponse'    => 'processMultiStatusResponse',
        'ezcWebdavOptionsResponse'        => 'processOptionsResponse',
        'ezcWebdavPropFindResponse'       => 'processPropFindResponse',
        'ezcWebdavPropPatchResponse'      => 'processPropPatchResponse',
        'ezcWebdavPutResponse'            => 'processPutResponse',
    );

    /**
     * Properties.
     * 
     * @var array(string=>mixed)
     */
    protected $properties = array();

    /**
     * Parses the incoming request into a fitting request abstraction object.
     *
     * This method is the main entry point of {@link ezcWebdavServer} and is
     * utilized by it to parse the incoming request into an instance of {@link
     * ezcWebdavRequest}.
     *
     * The submitted URI must be formatted in a way, that the {@link
     * ezcWebdavPathFactory} (by default this is {@link
     * ezcWebdavAutomaticPathFactory}) can convert it into a path absolute to
     * the WebDAV repository.
     *
     * The retrieval of the request body is performed by the {@link
     * retreiveBody()} method, the request method from {@link
     * $_SERVER['REQUEST_METHOD']}. The latter one is mapped through the
     * {self::$parsingMap} attribute to a local object method.
     *
     * This method is marked final and may not be overwritten, because it
     * belongs to the essential communication API with {@link ezcWebdavServer}
     * and is responsible to dispatch the {@link ezcWebdavPluginRegistry} hooks
     * of the transport layer.
     *
     * If an error occurs during request parsing, an instance of {@link
     * ezcWebdavResponse} may be returned instead of an instance of {@link
     * ezcWebdavRequest}.
     *
     * @return ezcWebdavRequest|ezcWebdavResponse
     */
    public final function parseRequest( $uri )
    {
        $body = $this->retreiveBody();
        $path = ezcWebdavServer::getInstance()->pathFactory->parseUriToPath( $uri );

        if ( isset( self::$parsingMap[$_SERVER['REQUEST_METHOD']] )  )
        {
            try
            {
                // @TODO: Plugin hook beforeParse*Request
                $request = call_user_func( array( $this, self::$parsingMap[$_SERVER['REQUEST_METHOD']] ), $path, $body );
                // @TODO: Plugin hook afterParse*Request
                $request->validateHeaders();
            }
            catch ( Exception $e )
            {
                return $this->handleException( $e, $uri );
            }
        }
        else
        {
            // Plugin hook
            $request = ezcWebdavServer::getInstance()->plugins->announceHook(
                __CLASS__,
                'parseUnkownRequest',
                new ezcWebdavPluginParameters(
                    array(
                        'uri'  => $uri,
                        'body' => $body,
                    )
                )
            );

            if ( !( $request instanceof ezcWebdavRequest ) && !( $request instanceof ezcWebdavResponse )  )
            {
                // Error code 501: Not implemented
                return new ezcWebdavErrorResponse(
                    ezcWebdavResponse::STATUS_501,
                    $uri
                );
            }
        }
        
        return $request;
    }

    /**
     * Handle a response and send it to the WebDAV client.
     *
     * This method is part of the integral communication API between the WebDAV
     * client and the {@link ezcWebdavServer}. It is declared final to ensure a
     * minimal compatibile API between the extended classes and it is
     * responsible to dispatch the {@link ezcWebdavPluginRegistry} hooks.
     *
     * It currently just maps internally to {@link processResponse()} and
     * passes the result to {@ $this->sendResponse()}. It is not recommended
     * that the {@link $this->processResponse()} method is overwritten, because
     * this one takes care about the dispatching. The {@link
     * $this->sendResponse()} may be overwritten, mainly for debugging, testing
     * and logging purposes.
     * 
     * @param ezcWebdavResponse $response
     * @return void
     */
    public final function handleResponse( ezcWebdavResponse $response )
    {
        // Set the Server header with information about eZ Components version
        // and transport implementation.
        $headers = ezcWebdavServer::getInstance()->headerHandler->parseHeaders( array( 'Server' ) );

        $response->setHeader(
            'Server',
            ( isset( $headers['Server'] ) && strlen( $headers['Server'] ) > 0 ? $headers['Server'] . '/' : '' )
                . 'eZComponents/'
                . ( self::VERSION === '//autogentag//' ? 'dev' : self::VERSION )
                . '/'
                . get_class( $this )
        );

        try
        {
            $response->validateHeaders();
            $this->sendResponse( $this->flattenResponse( $this->processResponse( $response ) ) );
        }
        catch ( Exception $e )
        {
            // Attention: Recursion!
            $this->handleResponse( $this->handleException( $e ) );
        }
    }

    /**
     * Handle a thrown exception and generate an error response from it.
     *
     * Takes the given exception $e and generates a response object from it.
     * The $uri parameter will be given to {@link
     * ezcWebdavErrorResponse::__construct()}.
     *
     * For special exceptions, special responses will be generated:
     * - ezcWebdavBadRequestException: 400 Bad Request
     * - ezcWebdavInvalidRequestMethodException: 501 Not Implemented
     * 
     * Per default, a 500 Internal Server Error response will be generated.
     *
     * Depending on where this is called, the generatedResponse hook will be
     * issued (if during request parsing), but the processErrorResponse hooks
     * will allways be called.
     * 
     * @param Exception $e 
     * @param string $uri 
     * @return ezcWebdavErrorResponse
     */
    protected function handleException( Exception $e, $uri = null )
    {
        switch ( true )
        {
            case ( $e instanceof ezcWebdavBadRequestException ):
                return new ezcWebdavErrorResponse( ezcWebdavResponse::STATUS_400, $uri, $e->getMessage() );
            case ( $e instanceof ezcWebdavInvalidRequestMethodException ):
                return new ezcWebdavErrorResponse( ezcWebdavResponse::STATUS_501, $uri, $e->getMessage() );
        }
        return new ezcWebdavErrorResponse( ezcWebdavResponse::STATUS_500, $uri, $e->getMessage() );
    }

    /**
     * Returns the body content of the request.
     *
     * This method mainly exists for unit testing purpose. It reads the request
     * body and returns the contents as a string. This method can also be
     * usefull to be overriden during inheritence to filter the body of
     * missbehaving WebDAV clients.
     * 
     * @return string The request body.
     */
    protected function retreiveBody()
    {
        $body = '';
        $in   = fopen( 'php://input', 'r' );

        while ( $data = fread( $in, 1024 ) )
        {
            $body .= $data;
        }
        return $body;
    }

    /**
     * Serializes a response object to XML.
     *
     * This method performs the internal dispatching of a given $response
     * object. It determines the method to handle the response by {@link
     * self::$handlingMap} and throws an Exception if the given class could not
     * be dispatched.
     *
     * The method internally calls one of the handle*Response() methods to get
     * the repsonse object processed and returns an instance of {@link
     * ezcWebdavDisplayInformation} to be displayed.
     *
     * @param ezcWebdavResponse $response 
     * @return ezcWebdavDisplayInformation
     * 
     * @throws RuntimeException
     *         if the class of the given object could not be dispatched.
     * @throws ezcWebdavMissingHeaderException
     *         if the generated result is an {@link
     *         ezcWebdavStringDisplayInformation} struct and the contained
     *         {@link ezcWebdavResponse} object has no Content-Type header set.
     * @throws ezcWebdavInvalidHeaderException
     *         if the generated result is an {@link
     *         ezcWebdavEmptyDisplayInformation} and the contained {@link
     *         ezcWebdavResponse} object has a Content-Type or a Content-Length
     *         header set.
     *
     * @todo Correct exception. Or better: Correct all exception mess!
     */
    private function processResponse( ezcWebdavResponse $response )
    {
        if ( isset( self::$handlingMap[( $responseClass = get_class( $response ) )] ) === false )
        {
            // Plugin hook processUnkownResponse
            $result = ezcWebdavServer::getInstance()->plugins->announceHook(
                __CLASS__,
                'processUnkownResponse',
                new ezcWebdavPluginParameters(
                    array(
                        'response'  => $response,
                    )
                )
            );
            if ( $result === null )
            {
                return processResponse( new ezcWebdavErrorResponse( ezcWebdavResponse::STATUS_500 ) );
            }
        }
        
        // @TODO Plugin hook beforeProcess*Response
        return call_user_func( array( $this, self::$handlingMap[( $responseClass = get_class( $response ) )] ), $response );
        // @TODO Plugin hook afterProcess*Response
    }

    /**
     * Flattens a processed response object to headers and body.
     *
     * Takes a given {@link ezcWebdavDisplayInformation} object and returns an
     * array containg the headers and body it represents.
     *
     * <code>
     *      array(
     *          'headers' => array(
     *              ''       => '<responsecodeandname>',
     *              '<name>' => '<value>',
     *              // ...
     *          ),
     *          'body' => '<string>'
     *      )
     * </code>
     *
     * The returned information can be processed (send out to the client) by
     * {@link ezcWebdavTransport::sendResponse()}.
     * 
     * @param ezcWebdavDisplayInformation $info 
     * @return array(string=>mixed)
     */
    protected function flattenResponse( ezcWebdavDisplayInformation $info )
    {
        $output          = new ezcWebdavOutputResult();
        $output->status  = (string) $info->response;
        $output->headers = $info->response->getHeaders();
        $output->body    = '';

        switch ( true )
        {
            case ( $info instanceof ezcWebdavXmlDisplayInformation ):
                $output->headers['Content-Type']  = ( isset( $output->headers['Content-Type'] ) ? $output->headers['Content-Type'] : 'text/xml; charset="utf-8"' );
                $info->body->formatOutput         = true;
                $output->body                     = $info->body->saveXML( $info->body );
                break;
            case ( $info instanceof ezcWebdavStringDisplayInformation ):
                if ( $info->response->getHeader( 'Content-Type' ) === null )
                {
                    throw new ezcWebdavMissingHeaderException( 'Content-Type' );
                }
                $output->body = $info->body;
                break;
            case ( $info instanceof ezcWebdavEmptyDisplayInformation ):
            default:
                if ( ( $contenTypeHeader = $info->response->getHeader( 'Content-Type' ) ) !== null  )
                {
                    throw new ezcWebdavInvalidHeaderException( 'Content-Type', $contenTypeHeader, 'null' );
                }
                $output->body = '';
                break;
        }
        
        return $output;
    }

    /**
     * Finally send out the response.
     *
     * This method is called to finally send the response to the browser. It
     * can be overwritten in test cases to change the behaviour of printing out
     * the result and sending the headers.
     *
     * @param ezcWebdavOutputResult $output
     * @return void
     */
    protected function sendResponse( ezcWebdavOutputResult $output )
    {
        // Sends HTTP headers
        foreach( $output->headers as $name => $content )
        {
            header( "{$name}: {$content}" );
        }

        // Send HTTP status code
        header( $output->status );

        // Content-Length header automatically send
        echo $output->body;
    }

    /*
     ***************************
     * Request handling follows.
     ***************************
     */

    // GET

    /**
     * Parses the GET request and returns a request object.
     *
     * This method is responsible for parsing the GET request. It retrieves the
     * current request URI in $path and the request body as $body.  The return
     * value, if no exception is thrown, is a valid {@link ezcWebdavGet}
     * object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavGetRequest
     */
    protected function parseGetRequest( $path, $body )
    {
        return new ezcWebdavGetRequest( $path );
    }

    // PUT

    /**
     * Parses the PUT request and returns a request object.
     *
     * This method is responsible for parsing the PUT request. It retrieves the
     * current request URI in $path and the request body as $body.  The return
     * value, if no exception is thrown, is a valid {@link ezcWebdavPut}
     * object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavPutRequest
     */
    protected function parsePutRequest( $path, $body )
    {
        $req = new ezcWebdavPutRequest( $path, $body );
        $req->setHeaders(
            ezcWebdavServer::getInstance()->headerHandler->parseHeaders(
                array(
                    'Content-Length', 'Content-Type'
                )
            )
        );
        return $req;
    }

    // HEAD

    /**
     * Parses the HEAD request and returns a request object.
     *
     * This method is responsible for parsing the HEAD request. It retrieves
     * the current request URI in $path and the request body as $body.  The
     * return value, if no exception is thrown, is a valid {@link
     * ezcWebdavHead} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavHeadRequest
     */
    protected function parseHeadRequest( $path, $body )
    {
        return new ezcWebdavHeadRequest( $path );
    }

    // COPY

    /**
     * Parses the COPY request and returns a request object.
     *
     * This method is responsible for parsing the COPY request. It retrieves
     * the current request URI in $path and the request body as $body.  The
     * return value, if no exception is thrown, is a valid {@link
     * ezcWebdavCopyRequest} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavCopyRequest
     *
     * @throws ezcWebdavInvalidRequestBodyException
     *         if the body of the copy request is invalid (XML wise or RFC
     *         wise).
     */
    protected function parseCopyRequest( $path, $body )
    {
        $headers = ezcWebdavServer::getInstance()->headerHandler->parseHeaders(
            array( 'Destination', 'Depth', 'Overwrite' )
        );

        $request = new ezcWebdavCopyRequest( $path, $headers['Destination'] );

        $request->setHeaders( $headers );

        if ( trim( $body ) === '' )
        {
            // No body present
            return $request;
        }

        if ( ( $dom = ezcWebdavServer::getInstance()->xmlTool->createDomDocument( $body ) ) === false )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'COPY',
                "Could not open XML as DOMDocument: '{$body}'."
            );
        }
        
        if ( $dom->documentElement->localName !== 'propertybehavior' )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'COPY',
                "Expected XML element <propertybehavior />, received <{$dom->documentElement->localName} />."
            );
        }
        
        return $this->parsePropertyBehaviourContent( $dom, $request );
    }

    // MOVE

    /**
     * Parses the MOVE request and returns a request object.
     *
     * This method is responsible for parsing the MOVE request. It retrieves
     * the current request URI in $path and the request body as $body.  The
     * return value, if no exception is thrown, is a valid {@link
     * ezcWebdavMoveRequest} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavMoveRequest
     */
    protected function parseMoveRequest( $path, $body )
    {
        $headers = ezcWebdavServer::getInstance()->headerHandler->parseHeaders(
            array( 'Destination', 'Depth', 'Overwrite' )
        );

        $request = new ezcWebdavMoveRequest( $path, $headers['Destination'] );

        $request->setHeaders( $headers );

        if ( trim( $body ) === '' )
        {
            // No body present
            return $request;
        }

        if ( ( $dom = ezcWebdavServer::getInstance()->xmlTool->createDomDocument( $body ) ) === false )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'MOVE',
                "Could not open XML as DOMDocument: '{$body}'."
            );
        }
        
        if ( $dom->documentElement->localName !== 'propertybehavior' )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'MOVE',
                "Expected XML element <propertybehavior />, received <{$dom->documentElement->localName} />."
            );
        }
        
        return $this->parsePropertyBehaviourContent( $dom, $request );
    }

    /**
     * Parses the <propertybehavior /> XML element. 
     *
     * This element is part of the COPY and MOVE requests, which are handled by
     * {@link $this->parseCopyRequest()} respectivly {@link
     * $this->parseMoveRequest()}.
     *
     * The $dom parameter is the DOMDocument where the <propertybehavior />
     * content should be parsed from. The $request object submitted will get
     * the resulting {@link ezcWebdavRequestPropertyBehaviourContent} set into
     * its {@link $propertyBehavior} property.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * If you overwrite the {@link $this->processCopyResponse()} or {@link
     * $this->parseMoveRequest()} methods, you might disable this method
     * accedentally. You should explicitly use it there and overwrite it, if
     * necessary. This makes extending your extension easier.
     * 
     * @param DOMDocument $dom 
     * @param ezcWebdavCopyRequest|ezcWebdavMoveRequest $request 
     * @return ezcWebdavCopyRequest|ezcWebdavMoveRequest As submitted.
     */
    protected function parsePropertyBehaviourContent( DOMDocument $dom, ezcWebdavRequest $request )
    {
        $propertyBehaviourNode = $dom->documentElement;

        $request->propertyBehaviour = new ezcWebdavRequestPropertyBehaviourContent();
        switch ( $propertyBehaviourNode->firstChild->localName )
        {
            case 'omit':
                $request->propertyBehavior->omit = true;
                break;
            case 'keepalive':
                if ( $propertyBehaviourNode->firstChild->nodeValue === '*' )
                {
                    $request->propertyBehaviour->keepAlive = ezcWebdavRequestPropertyBehaviourContent::ALL;
                }
                else
                {
                    $keepAliveContent = array();
                    $hrefNodes        = $propertyBehaviourNode->firstChild->getElementsByTagName( 'href' );

                    for ( $i = 0; $i < $hrefNodes->length; ++$i )
                    {
                        $keepAliveContent[] = $hrefNodes->item( $i )->nodeValue;
                    }

                    $request->propertyBehaviour->keepAlive = $keepAliveContent;
                }
        }
        return $request;
    }
    
    // DELETE

    /**
     * Parses the DELETE request and returns a request object.
     *
     * This method is responsible for parsing the DELETE request. It retrieves
     * the current request URI in $path and the request body as $body.  The
     * return value, if no exception is thrown, is a valid {@link
     * ezcWebdavDeleteRequest} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavDeleteRequest
     */
    protected function parseDeleteRequest( $path, $body )
    {
        return new ezcWebdavDeleteRequest( $path );
    }
    
    // LOCK

    /**
     * Parses the LOCK request and returns a request object.
     *
     * This method is responsible for parsing the LOCK request. It retrieves
     * the current request URI in $path and the request body as $body.  The
     * return value, if no exception is thrown, is a valid {@link
     * ezcWebdavLockRequest} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavLockRequest
     *
     * @todo This should be extracted into the LOCK plugin.
     */
    protected function parseLockRequest( $path, $body )
    {
        $request = new ezcWebdavLockRequest( $path );

        $request->setHeaders(
            ezcWebdavServer::getInstance()->headerHandler->parseHeaders(
                array( 'Depth', 'Timeout' )
            )
        );

        if ( trim( $body ) === '' )
        {
            return $request;
        }

        if ( ( $dom = ezcWebdavServer::getInstance()->xmlTool->createDomDocument( $body ) ) === false )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'LOCK',
                "Could not open XML as DOMDocument: '{$body}'."
            );
        }
        
        if ( $dom->documentElement->localName !== 'lockinfo' )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'LOCK',
                "Expected XML element <lockinfo />, received <{$dom->documentElement->localName} />."
            );
        }

        $lockTypeElements  = $dom->documentElement->getElementsByTagnameNS( ezcWebdavXmlTool::XML_DEFAULT_NAMESPACE, 'locktype' );
        $lockScopeElements = $dom->documentElement->getElementsByTagnameNS( ezcWebdavXmlTool::XML_DEFAULT_NAMESPACE, 'lockscope' );
        $ownerElements     = $dom->documentElement->getElementsByTagnameNS( ezcWebdavXmlTool::XML_DEFAULT_NAMESPACE, 'owner' );

        if ( $lockTypeElements->length === 0 )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'LOCK',
                "Expected XML element <locktype /> as child of <lockinfo /> in namespace DAV: which was not found."
            );
        }
        if ( $lockScopeElements->length === 0 )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'LOCK',
                "Expected XML element <lockscope /> as child of <lockinfo /> in namespace DAV: which was not found."
            );
        }

        // @TODO is the following not restrictive enough?
        $request->lockInfo = new ezcWebdavRequestLockInfoContent(
            ( $lockScopeElements->item( 0 )->firstChild->localName === 'exclusive'
                ? ezcWebdavLockRequest::SCOPE_EXCLUSIVE
                : ezcWebdavLockRequest::SCOPE_SHARED ),
            ( $lockTypeElements->item( 0 )->firstChild->localName === 'read'
                ? ezcWebdavLockRequest::TYPE_READ
                : ezcWebdavLockRequest::TYPE_WRITE ),
            ( $ownerElements->length > 0 
                ? $ownerElements->item( 0 )->textContent
                : null )
        );

        return $request;
    }
    
    // UNLOCK

    /**
     * Parses the UNLOCK request and returns a request object.
     *
     * This method is responsible for parsing the UNLOCK request. It retrieves
     * the current request URI in $path and the request body as $body.  The
     * return value, if no exception is thrown, is a valid {@link
     * ezcWebdavUnlockRequest} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavUnlockRequest
     *
     * @todo This should be extracted into the LOCK plugin.
     */
    protected function parseUnlockRequest( $path, $body )
    {
        $request = new ezcWebdavUnlockRequest( $path );

        $request->setHeaders(
            ezcWebdavServer::getInstance()->headerHandler->parseHeaders(
                array( 'Lock-Token' )
            )
        );

        return $request;
    }

    // MKCOL

    /**
     * Parses the MKCOL request and returns a request object.
     *
     * This method is responsible for parsing the MKCOL request. It retrieves
     * the current request URI in $path and the request body as $body.  The
     * return value, if no exception is thrown, is a valid {@link
     * ezcWebdavMakeCollectionRequest} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavMakeCollectionRequest
     */
    protected function parseMakeCollectionRequest( $path, $body )
    {
        return new ezcWebdavMakeCollectionRequest( $path, ( trim( $body ) === '' ? null : $body ) );
    }
    
    // OPTIONS

    /**
     * Parses the OPTIONS request and returns a request object.
     *
     * This method is responsible for parsing the OPTIONS request. It retrieves
     * the current request URI in $path and the request body as $body.  The
     * return value, if no exception is thrown, is a valid {@link
     * ezcWebdavOptionsRequest} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavOptionsRequest
     */
    protected function parseOptionsRequest( $path, $body )
    {
        return new ezcWebdavOptionsRequest( $path, ( trim( $body ) === '' ? null : $body ) );
    }

    // PROPFIND

    /**
     * Parses the PROPFIND request and returns a request object.
     *
     * This method is responsible for parsing the PROPFIND request. It
     * retrieves the current request URI in $path and the request body as
     * $body.  The return value, if no exception is thrown, is a valid {@link
     * ezcWebdavPropFindRequest} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavPropFindRequest
     */
    protected function parsePropFindRequest( $path, $body )
    {
        $request = new ezcWebdavPropFindRequest( $path );

        $request->setHeaders(
            ezcWebdavServer::getInstance()->headerHandler->parseHeaders(
                array( 'Depth' )
            )
        );

        if ( empty( $body ) ||
             ( ( $dom = ezcWebdavServer::getInstance()->xmlTool->createDomDocument( $body ) ) === false ) )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'PROPFIND',
                "Could not open XML as DOMDocument: '{$body}'."
            );
        }

        if ( $dom->documentElement->localName !== 'propfind' )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'PROPFIND',
                "Expected XML element <propfind />, received <{$dom->documentElement->localName} />."
            );
        }
        if ( $dom->documentElement->firstChild === null )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'PROPFIND',
                "Element <propfind /> does not have a child element."
            );
        }

        switch ( $dom->documentElement->firstChild->localName )
        {
            case 'allprop':
                $request->allProp = true;
                break;
            case 'propname':
                $request->propName = true;
                break;
            case 'prop':
                $request->prop = new ezcWebdavBasicPropertyStorage();
                try
                {
                    ezcWebdavServer::getInstance()->propertyHandler->extractProperties(
                        $dom->documentElement->firstChild->childNodes,
                        $request->prop
                    );
                }
                catch ( ezcBaseValueException $e )
                {
                    throw new ezcWebdavInvalidRequestBodyException(
                        'PROPFIND',
                        "Property extraction produced value exception: '{$e->getMessage()}'."
                    );
                }
                break;
            default:
                throw new ezcWebdavInvalidRequestBodyException(
                    'PROPFIND',
                    "XML element <{$dom->documentElement->firstChild->tagName} /> is not a valid child element for <propfind />."
                );
        }
        return $request;
    }
    
    // PROPPATCH

    /**
     * Parses the PROPPATCH request and returns a request object.
     *
     * This method is responsible for parsing the PROPPATCH request. It
     * retrieves the current request URI in $path and the request body as
     * $body.  The return value, if no exception is thrown, is a valid {@link
     * ezcWebdavPropPatchRequest} object.
     *
     * This method may be overwritten to adjust it to special client behaviour.
     * 
     * @param string $path 
     * @param string $body 
     * @return ezcWebdavPropPatchRequest
     */
    protected function parsePropPatchRequest( $path, $body )
    {
        $request = new ezcWebdavPropPatchRequest( $path );

        if ( ( $dom = ezcWebdavServer::getInstance()->xmlTool->createDomDocument( $body ) ) === false )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'PROPPATCH',
                "Could not open XML as DOMDocument: '{$body}'."
            );
        }

        if ( $dom->documentElement->localName !== 'propertyupdate' )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'PROPPATCH',
                "Expected XML element <propertyupdate />, received <{$dom->documentElement->localName} />."
            );
        }

        $setElements    = $dom->documentElement->getElementsByTagNameNS( ezcWebdavXmlTool::XML_DEFAULT_NAMESPACE, 'set' );
        $removeElements = $dom->documentElement->getElementsByTagNameNS( ezcWebdavXmlTool::XML_DEFAULT_NAMESPACE, 'remove' );
 
        try
        {

            // @TODO:
            // This code destroys the original order of the properties, and only
            // preserves the property order in set or remove groups. This violates
            // the webdav RFC section "8.2 PROPPATCH". 
            //
            // @See http://tools.ietf.org/html/rfc2518#page-31
            for ( $i = 0; $i < $setElements->length; ++$i )
            {
                ezcWebdavServer::getInstance()->propertyHandler->extractProperties(
                    $setElements->item( $i )->firstChild->childNodes,
                    $request->updates,
                    ezcWebdavPropPatchRequest::SET
                );
            }
            
            for ( $i = 0; $i < $removeElements->length; ++$i )
            {
                ezcWebdavServer::getInstance()->propertyHandler->extractProperties(
                    $removeElements->item( $i )->firstChild->childNodes,
                    $request->updates,
                    ezcWebdavPropPatchRequest::REMOVE
                );
            }
        }
        catch ( ezcBaseValueException $e )
        {
            throw new ezcWebdavInvalidRequestBodyException(
                'PROPPATCH',
                "Property extraction produced value exception: '{$e->getMessage()}'."
            );
        }

        return $request;
    }

    /*
     ****************************
     * Response handling follows.
     ****************************
     */

    // ezcWebdavMultiStatusResponse

    /**
     * Returns display information for a multistatus response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavMultiStatusResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information generated by this response contains the post
     * processed $response and a {@link DOMDocument} representing the XML
     * response body.
     *
     * This method utilizes {@link ezcWebdavServer::getInstance()->xmlTool} to perform basic XML operations,
     * so this is the place to perform such changeds. You should overwrite this
     * method, if your client has problems specifically with the {@link
     * ezcWebdavMultiStatusResponse} response.
     *
     * @param ezcWebdavMultiStatusResponse $response 
     * @return ezcWebdavXmlDisplayInformation
     */
    protected function processMultiStatusResponse( ezcWebdavMultiStatusResponse $response )
    {
        $dom = ezcWebdavServer::getInstance()->xmlTool->createDomDocument();

        $multistatusElement = $dom->appendChild(
            ezcWebdavServer::getInstance()->xmlTool->createDomElement( $dom, 'multistatus' )
        );

        foreach ( $response->responses as $subResponse )
        {
            $multistatusElement->appendChild(
                ( $subResponse instanceof ezcWebdavErrorResponse 
                    ? $dom->importNode( $this->processErrorResponse( $subResponse, true )->body->documentElement, true )
                    : $dom->importNode( $this->processResponse( $subResponse )->body->documentElement, true )
                )
            );
        }
        
        return new ezcWebdavXmlDisplayInformation( $response, $dom );
    }

    // ezcWebdavPropFindResponse

    /**
     * Returns display information for a prop find response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavPropFindResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information generated by this response contains the post
     * processed $response and a {@link DOMDocument} representing the XML
     * response body.
     *
     * This method utilizes {@link ezcWebdavServer::getInstance()->xmlTool} to perform basic XML operations,
     * so this is the place to perform such changeds. You should overwrite this
     * method, if your client has problems specifically with the {@link
     * ezcWebdavPropFindResponse} response.
     *
     * @param ezcWebdavPropFindResponse $response 
     * @return ezcWebdavXmlDisplayInformation
     */
    protected function processPropFindResponse( ezcWebdavPropFindResponse $response )
    {
        $dom = ezcWebdavServer::getInstance()->xmlTool->createDomDocument();

        $responseElement = $dom->appendChild(
            ezcWebdavServer::getInstance()->xmlTool->createDomElement( $dom, 'response' )
        );

        $responseElement->appendChild(
            ezcWebdavServer::getInstance()->xmlTool->createDomElement( $dom, 'href' )
        )->nodeValue = ezcWebdavServer::getInstance()->pathFactory->generateUriFromPath( $response->node->path );

        foreach ( $response->responses as $propStat )
        {
            $responseElement->appendChild(
                $dom->importNode( $this->processPropStatResponse( $propStat )->body->documentElement, true )
            );
        }
        return new ezcWebdavXmlDisplayInformation( $response, $dom );
    }

    // ezcWebdavPropPatchResponse

    /**
     * Returns display information for a prop patch response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavPropPatchResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information returned by this method indicates, that only
     * headers, but no response body, should be send.
     *
     * @param ezcWebdavPropPatchResponse $response 
     * @return ezcWebdavEmptyDisplayInformation
     */
    protected function processPropPatchResponse( ezcWebdavPropPatchResponse $response )
    {
        return new ezcWebdavEmptyDisplayInformation( $response );
    }

    // ezcWebdavCopyResponse

    /**
     * Returns display information for a copy response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavCopyResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information returned by this method indicates, that only
     * headers, but no response body, should be send.
     *
     * @param ezcWebdavCopyResponse $response 
     * @return ezcWebdavEmptyDisplayInformation
     */
    protected function processCopyResponse( ezcWebdavCopyResponse $response )
    {
        return new ezcWebdavEmptyDisplayInformation( $response );
    }

    // ezcWebdavMoveResponse

    /**
     * Returns display information for a move response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavMoveResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information returned by this method indicates, that only
     * headers, but no response body, should be send.
     *
     * @param ezcWebdavMoveResponse $response 
     * @return ezcWebdavEmptyDisplayInformation
     */
    protected function processMoveResponse( ezcWebdavMoveResponse $response )
    {
        return new ezcWebdavEmptyDisplayInformation( $response );
    }


    // ezcWebdavDeleteResponse

    /**
     * Returns display information for a delete response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavDeleteResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information returned by this method indicates, that only
     * headers, but no response body, should be send.
     *
     * @param ezcWebdavDeleteResponse $response 
     * @return ezcWebdavEmptyDisplayInformation
     */
    protected function processDeleteResponse( ezcWebdavDeleteResponse $response )
    {
        return new ezcWebdavEmptyDisplayInformation( $response );
    }

    // ezcWebdavErrorResponse

    /**
     * Returns display information for a error response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavErrorResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The $xml parameter defines, if an XML representation should be
     * generated, too (for use in {@link $this->processMultiStatusResponse()}),
     * or if only the headers should be manipulated and an empty response body
     * should be used.
     *
     * The display information generated by this response contains the post
     * processed $response and a {@link DOMDocument} representing the XML
     * response body. If the $xml parameter is set to false, an empty display
     * information is generated, to indicate that only headers should be send. 
     *
     * This method utilizes {@link ezcWebdavServer::getInstance()->xmlTool} to perform basic XML operations,
     * so this is the place to perform such changeds. You should overwrite this
     * method, if your client has problems specifically with the {@link
     * ezcWebdavErrorResponse} response.
     *
     * @param ezcWebdavErrorResponse $response 
     * @param bool $xml DOMDocument in result only generated of true.
     * @return ezcWebdavXmlDisplayInformation|ezcWebdavEmptyDisplayInformation
     */
    protected function processErrorResponse( ezcWebdavErrorResponse $response, $xml = false )
    {
        $res = new ezcWebdavEmptyDisplayInformation( $response );
        if ( $xml === true )
        {
            $dom = ezcWebdavServer::getInstance()->xmlTool->createDomDocument();
            $responseElement = $dom->appendChild(
                ezcWebdavServer::getInstance()->xmlTool->createDomElement( $dom, 'response' )
            );
            
            $responseElement->appendChild(
                ezcWebdavServer::getInstance()->xmlTool->createDomElement( $dom, 'href' )
            )->nodeValue = ezcWebdavServer::getInstance()->pathFactory->generateUriFromPath( $response->requestUri );
            
            $responseElement->appendChild(
                ezcWebdavServer::getInstance()->xmlTool->createDomElement( $dom, 'status' )
            )->nodeValue = (string) $response;

            if ( !empty( $response->responseDescription ) )
            {
                $responseElement->appendChild(
                    ezcWebdavServer::getInstance()->xmlTool->createDomElement( $dom, 'responsedescription' )
                )->nodeValue = $response->responseDescription;
            }
            $res = new ezcWebdavXmlDisplayInformation( $response, $dom );
        }
        return $res;
    }

    // ezcWebdavGetCollectionResponse

    /**
     * Returns display information for a get response object for a collection.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavGetCollectionResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information returned by this method indicates, that only
     * headers, but no response body, should be send.
     *
     * @param ezcWebdavGetCollectionResponse $response 
     * @return ezcWebdavEmptyDisplayInformation
     *
     * @todo We should possibly offer an ezcWebdavTemplateTiein, which brings
     * an extension that adds a directory listing body here (possibly in
     * selectable formats like XHTML, HTML, Apache style, ...).
     */
    protected function processGetCollectionResponse( ezcWebdavGetCollectionResponse $response )
    {
        return new ezcWebdavEmptyDisplayInformation( $response );
    }

    // ezcWebdavGetCollectionResponse

    /**
     * Returns display information for a get response object for a non-collection resource.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavGetResoucreResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * This response returns a very seldom (for this component) string
     * response, since it returns the raw content of the requested resource.
     *
     * @param ezcWebdavGetResourceResponse $response 
     * @return ezcWebdavStringDisplayInformation
     * @todo Do we need to set more headers here?
     */
    protected function processGetResourceResponse( ezcWebdavGetResourceResponse $response )
    {
        // Generate Content-Type header if necessary
        if ( $response->getHeader( 'Content-Type' ) === null )
        {
            $contentTypeProperty = $response->resource->liveProperties->get( 'getcontenttype' );
            $contentTypeHeader = ( $contentTypeProperty->mime    !== null ? $contentTypeProperty->mime    : 'application/octet-stream' ) .
                '; charset="' .   ( $contentTypeProperty->charset !== null ? $contentTypeProperty->charset : 'utf-8' ) . '"';
            $response->setHeader( 'Content-Type', $contentTypeHeader );
        }
        // Content-Length automatically send by web server
        return new ezcWebdavStringDisplayInformation( $response, $response->resource->content );
    }

    // ezcWebdavPutResponse

    /**
     * Returns display information for a put response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavPutResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information returned by this method indicates, that only
     * headers, but no response body, should be send.
     *
     * @param ezcWebdavPutResponse $response 
     * @return ezcWebdavEmptyDisplayInformation
     */
    protected function processPutResponse( ezcWebdavPutResponse $response )
    {
        return new ezcWebdavEmptyDisplayInformation( $response );
    }

    // ezcWebdavHeadResponse

    /**
     * Returns display information for a head response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavHeadResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information returned by this method indicates, that only
     * headers, but no response body, should be send.
     *
     * This method always must be structured quite similar to {@link
     * $this->processGetCollectionResponse} or {@link
     * $this->processGetResourceResponse()}, since HEAD is more or less GET
     * without a body.
     *
     * @param ezcWebdavHeadResponse $response 
     * @return ezcWebdavEmptyDisplayInformation
     * @todo Do we need to set more headers here?
     */
    protected function processHeadResponse( ezcWebdavHeadResponse $response )
    {
        // Generate Content-Type header if necessary
        if ( $response->getHeader( 'Content-Type' ) === null )
        {
            $contentTypeProperty = $response->resource->liveProperties->get( 'getcontenttype' );
            $contentTypeHeader = ( $contentTypeProperty->mime    !== null ? $contentTypeProperty->mime    : 'application/octet-stream' ) .
                '; charset="' .   ( $contentTypeProperty->charset !== null ? $contentTypeProperty->charset : 'utf-8' ) . '"';
            $response->setHeader( 'Content-Type', $contentTypeHeader );
        }
        // Generate Content-Length header if necessary
        if ( $response->getHeader( 'Content-Length' ) === null )
        {
            $response->setHeader( 'Content-Length', ( strlen( $response->resource->content ) + 1 ) );
        }
        return new ezcWebdavStringDisplayInformation( $response, '' );
    }

    // ezcWebdavMakeCollectionResponse

    /**
     * Returns display information for a make collection response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavMakeCollectionResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information returned by this method indicates, that only
     * headers, but no response body, should be send.
     *
     * @param ezcWebdavMakeCollectionResponse $response 
     * @return ezcWebdavEmptyDisplayInformation
     */
    protected function processMakeCollectionResponse( ezcWebdavMakeCollectionResponse $response )
    {
        return new ezcWebdavEmptyDisplayInformation( $response );
    }

    // ezcWebdavOptionsResponse

    /**
     * Returns display information for a options response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavOptionsResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information returned by this method indicates, that only
     * headers, but no response body, should be send.
     *
     * @param ezcWebdavOptionsResponse $response 
     * @return ezcWebdavEmptyDisplayInformation
     */
    protected function processOptionsResponse( ezcWebdavOptionsResponse $response )
    {
        return new ezcWebdavEmptyDisplayInformation( $response );
    }

    // ezcWebdavPropStatResponse

    /**
     * Returns display information for a prop stat response object.
     *
     * This method returns the display information generated for a $response
     * object of type {@link ezcWebdavPropStatResponse}. It returns an
     * instance of {@link ezcWebdavDisplayInformation} containing the
     * post-processed response object and the appropriate body.
     *
     * The display information generated by this response contains the post
     * processed $response and a {@link DOMDocument} representing the XML
     * response body.
     *
     * This method utilizes {@link ezcWebdavServer::getInstance()->xmlTool} to perform basic XML operations,
     * so this is the place to perform such changeds. You should overwrite this
     * method, if your client has problems specifically with the {@link
     * ezcWebdavErrorResponse} response.
     *
     * @param ezcWebdavPropStatResponse $response 
     * @return ezcWebdavXmlDisplayInformation
     */
    protected function processPropStatResponse( ezcWebdavPropStatResponse $response )
    {
        $dom = ezcWebdavServer::getInstance()->xmlTool->createDomDocument();

        $propstatElement = $dom->appendChild(
            ezcWebdavServer::getInstance()->xmlTool->createDomElement( $dom, 'propstat' )
        );
        
        ezcWebdavServer::getInstance()->propertyHandler->serializeProperties(
            $response->storage,
            $propstatElement->appendChild( ezcWebdavServer::getInstance()->xmlTool->createDomElement( $dom, 'prop' ) )
        );

        $propstatElement->appendChild(
            ezcWebdavServer::getInstance()->xmlTool->createDomElement(
                $dom,
                'status'
            )
        )->nodeValue = (string) $response;

        return new ezcWebdavXmlDisplayInformation( $response, $dom );
    }
}

?>
