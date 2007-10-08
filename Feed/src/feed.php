<?php
/**
 * File containing the ezcFeed class.
 *
 * @package Feed
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * ezcFeed.
 *
 * @property-read array(int=>ezcFeedItem) $items The items belonging to the feed.
 *
 * @package Feed
 * @version //autogentag//
 * @mainclass
 */
class ezcFeed implements Iterator
{
    /**
     * A list of all supported feed types
     *
     * @var array(string=>string)
     */
    static private $supportedFeedTypes = array(
        'rss1' => 'ezcFeedRss1',
        'rss2' => 'ezcFeedRss2',
        'atom' => 'ezcFeedAtom',
    );

    /**
     * A list of all supported feed modules
     *
     * @var array(string=>string)
     */
    static private $supportedModules = array(
        'Content'    => 'ezcFeedModuleContent',
        'DublinCore' => 'ezcFeedModuleDublinCore',
    );

    /**
     * The feed processor
     *
     * @var ezcFeedProcessor
     */
    private $feedProcessor;

    private $feedType;

    private $iteratorItems = array();
    private $iteratorElementCount = 0;
    private $iteratorPosition = 0;


    static public function parse( $uri )
    {
        $xml = new DomDocument;
        $retval = @$xml->load( $uri );
        if ( $retval === false )
        {
            throw new ezcBaseFileNotFoundException( $uri );
        }
        return self::dispatchXml( $xml );
    }

    static public function parseContent( $content )
    {
        $xml = new DomDocument;
        $retval = @$xml->loadXML( $content );
        if ( $retval === false )
        {
            throw new ezcFeedParseErrorException( "Content is no valid XML." );
        }
        return self::dispatchXml( $xml );
    }

    protected static function dispatchXml( DOMDocument $xml )
    {
        foreach ( self::$supportedFeedTypes as $feedType => $feedClass )
        {
            $canParse = call_user_func( array( $feedClass, 'canParse' ), $xml );
            if ( $canParse === true )
            {
                $processor = new $feedClass;
                return $processor->parse( $xml );
            }
        }

        throw new ezcFeedCanNotParseException( $xml->documentURI, 'Feed type not recognised' );
    }

    static public function getSupportedTypes()
    {
        return array_keys( self::$supportedFeedTypes );
    }

    static public function getSupportedModules()
    {
        return self::$supportedModules;
    }

    static public function getModule( $moduleName, $feedType )
    {
        return new self::$supportedModules[$moduleName]( $feedType );
    }

    /**
     * Creates a new feed
     *
     * @throws ezcFeedUnsupportedTypeException
     *         If the passed $type is an unsupported feed type
     *
     * @param string $type
     */
    public function __construct( $type )
    {
        if ( !in_array( $type, array_keys( self::$supportedFeedTypes ) ) )
        {
            throw new ezcFeedUnsupportedTypeException( $type );
        }
        $this->feedType = $type;
        $this->feedProcessor = new self::$supportedFeedTypes[$type];
    }

    public function addModule( $className )
    {
        $moduleObj = new $className( $this->feedType );
        if ( !$moduleObj instanceof ezcFeedModule )
        {
            throw new ezcFeedUnsupportedModuleException( $className );
        }
        $moduleName = $moduleObj->getModuleName();
        $this->$moduleName = $this->feedProcessor->addModule( $moduleName, $moduleObj );
    }

    public function __set( $property, $value )
    {
        switch ( $property )
        {
            case 'title': // required in RSS1, RSS2, ATOM
            case 'subtitle': // ATOM only
            case 'link': // required in RSS2, rdf:about AND link in RSS1
            case 'description': // required in RSS1, RSS2
            case 'language':
            case 'copyright': // rights in ATOM
            case 'author': // managingEditor in RSS2, required in ATOM
            case 'webMaster': // RSS2 only
            case 'published': // pubDate in RSS2
            case 'updated':   // lastBuildDate in RSS2, required in ATOM
            case 'category':
            case 'generator':
            case 'ttl':
            case 'image': // icon in ATOM
            case 'id': // ATOM only, required in ATOM
                $this->feedProcessor->setFeedElement( $property, $value );
                break;
        }

        $modules = $this->feedProcessor->getModules();
        foreach ( $modules as $moduleName => $moduleObj )
        {
            if ( $property == $moduleName )
            {
                $this->$moduleName = $value;
            }
        }
    }

    public function __get( $propertyName )
    {
        switch ( $propertyName )
        {
            case 'title': // required in RSS1, RSS2, ATOM
            case 'subtitle': // ATOM only
            case 'link': // required in RSS2, rdf:about AND link in RSS1
            case 'description': // required in RSS1, RSS2
            case 'language':
            case 'copyright': // rights in ATOM
            case 'author': // managingEditor in RSS2, required in ATOM
            case 'webMaster': // RSS2 only
            case 'published': // pubDate in RSS2
            case 'updated':   // lastBuildDate in RSS2, required in ATOM
            case 'category':
            case 'generator':
            case 'ttl':
            case 'image': // icon in ATOM
            case 'id': // ATOM only, required in ATOM
                return $this->feedProcessor->getFeedElement( $propertyName );

            case 'items':
                return (array) $this->feedProcessor->getItems();
        }
        throw new Exception( "OH OH: {$propertyName}" );
    }

    /**
     * Returns new item for this feed
     *
     * @return ezcFeedItem
     */
    public function newItem()
    {
        $item = new ezcFeedItem( $this->feedProcessor );
        $this->feedProcessor->addItem( $item );
        return $item;
    }

    public function generate()
    {
        return $this->feedProcessor->generate();
    }

    public function rewind()
    {
        $this->iteratorItems = $this->feedProcessor->getItems();
        $this->iteratorElementCount = count( $this->iteratorItems );
        $this->iteratorPosition = 0;
    }

    public function current()
    {
        return $this->iteratorItems[$this->iteratorPosition];
    }

    public function key()
    {
        return $this->iteratorPosition;
    }

    public function valid()
    {
        return $this->iteratorPosition < $this->iteratorElementCount;
    }

    public function next()
    {
        $this->iteratorPosition++;
    }
}
?>
