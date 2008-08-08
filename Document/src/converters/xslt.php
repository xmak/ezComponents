<?php

/**
 * File containing the ezcDocumentXsltConverter class
 *
 * @package Document
 * @version //autogen//
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Base class for conversions between XML documents using XSLT.
 * 
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentXsltConverter extends ezcDocumentConverter
{
    /**
     * Path to XSLT transformation file
     * 
     * @var string
     */
    protected $xslt;

    /**
     * Parameters for the XSLT
     * 
     * @var array
     */
    protected $parameters = array();

    /**
     * XSLT processor created from the defined XSLT file.
     * 
     * @var XSLTProcessor
     */
    protected $xsltProcessor = null;

    /**
     * Construct converter
     *
     * Construct converter from XSLT file, which is used for the actual
     * conversion.
     * 
     * @param string $xslt 
     * @return void
     */
    public function __construct( $xslt )
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'xsl' ) )
        {
            throw new ezcBaseExtensionNotFoundException( 'xsl' );
        }

        $this->xslt = $xslt;
    }

    /**
     * Set parameters
     *
     * Set parameters for the XSLT stylesheet. The parameters array should have
     * the following structure:
     *
     * <code>
     *  array(
     *      'namespace' => array(
     *          'option' => 'value',
     *          ...
     *      ),
     *      ...
     *  )
     * </code>
     *
     * Where namespace may be an empty string.
     * 
     * @param array $parameters 
     * @return void
     */
    public function setParameters( array $parameters )
    {
        $this->parameters = $parameters;
    }

    /**
     * Convert documents between two formats
     * 
     * Convert documents of the given type to the requested type.
     *
     * @param ezcDocumentXmlBase $doc 
     * @return ezcDocumentXmlBase
     */
    public function convert( $doc )
    {
        // Create XSLT processor, if not yet initialized
        if ( $this->xsltProcessor === null )
        {
            $stylesheet = new DOMDocument();
            $stylesheet->load( $this->xslt );

            $this->xsltProcessor = new XSLTProcessor();
            $this->xsltProcessor->importStyleSheet( $stylesheet );
        }

        // Set provided parameters.
        foreach ( $this->parameters as $namespace => $parameters )
        {
            foreach ( $parameters as $option => $value )
            {
                $this->xsltProcessor->setParameter( $namespace, $option, $value );
            }
        }

        // We want to handle the occured errors ourselves.
        $oldErrorHandling = libxml_use_internal_errors( true );

        // Transform input document
        $dom = $this->xsltProcessor->transformToDoc( $doc->getDomDocument() );

        // @TODO: Handle the ocured errors somehow.
        libxml_use_internal_errors( $oldErrorHandling );

        // Reset parameters, so they are not automatically applied to the next
        // traansformation.
        foreach ( $this->parameters as $namespace => $parameters )
        {
            foreach ( $parameters as $option => $value )
            {
                $this->xsltProcessor->removeParameter( $namespace, $option );
            }
        }

        // Build document from transformation and return that.
        return $this->buildDocument( $dom );
    }

    /**
     * Build document
     *
     * Build document of appropriate type from the DOMDocument, created by the
     * XSLT transformation.
     * 
     * @param DOMDocument $document 
     * @return ezcDocumentXmlBase
     */
    abstract protected function buildDocument( DOMDocument $document );
}

?>
