<?php
/**
 * File containing the ezcDocumentOdtTableRowStyleGenerator class.
 *
 * @package Document
 * @version //autogen//
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * Class to generate styles for <table:table-row/> elements.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentOdtTableRowStyleGenerator extends ezcDocumentOdtStyleGenerator
{
    /**
     * TableRow property generator. 
     * 
     * @var ezcDocumentOdtStyleTableRowPropertyGenerator
     */
    protected $tableRowPropertyGenerator;

    /**
     * Creates a new style genertaor.
     * 
     * @param ezcDocumentOdtPcssConverterManager $styleConverters 
     */
    public function __construct( ezcDocumentOdtPcssConverterManager $styleConverters )
    {
        $this->tableRowPropertyGenerator = new ezcDocumentOdtStyleTableRowPropertyGenerator(
            $styleConverters
        );
    }

    /**
     * Returns if the given $odtElement is handled by this generator.
     * 
     * @param DOMElement $odtElement 
     * @return bool
     */
    public function handles( DOMElement $odtElement )
    {
        return (
            $odtElement->localName === 'table-row'
        );
    }
    
    /**
     * Creates the styles with $styleAttributes for the given $odtElement.
     * 
     * @param DOMElement $odtElement 
     * @param array(string=>ezcDocumentPcssStyleValue) $styleAttributes 
     */
    public function createStyle( ezcDocumentOdtStyleInformation $styleInfo, DOMElement $odtElement, array $styleAttributes )
    {
        $styleName = $this->getUniqueStyleName( $odtElement->localName );

        $style = $styleInfo->automaticStyleSection->appendChild(
            $styleInfo->automaticStyleSection->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_STYLE,
                'style:style'
            )
        );

        $style->setAttributeNS(
            ezcDocumentOdt::NS_ODT_STYLE,
            'style:family',
            'table-row'
        );
        $style->setAttributeNS(
            ezcDocumentOdt::NS_ODT_STYLE,
            'style:name',
            $styleName
        );

        $odtElement->setAttributeNS(
            ezcDocumentOdt::NS_ODT_TABLE,
            'table:style-name',
            $styleName
        );

        $this->tableRowPropertyGenerator->createProperty(
            $style,
            $styleAttributes
        );
    }
}

?>
