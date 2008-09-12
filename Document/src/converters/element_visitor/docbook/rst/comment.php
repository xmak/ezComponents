<?php

/**
 * File containing the ezcDocumentDocbookElementVisitorConverter class
 *
 * @package Document
 * @version //autogen//
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Visit docbook comment
 *
 * Transform docbook comments into HTML ( / XML ) comments.
 * 
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToRstCommentHandler extends ezcDocumentDocbookToRstBaseHandler
{
    /**
     * Handle a node
     *
     * Handle / transform a given node, and return the result of the
     * conversion.
     * 
     * @param ezcDocumentDocbookElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root 
     * @return mixed
     */
    public function handle( ezcDocumentDocbookElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $comment = $converter->visitChildren( $node, '' );
        $root .= '.. ' . trim( ezcDocumentDocbookToRstConverter::wordWrap( $comment, 3 ) ) . "\n\n";

        return $root;
    }
}

?>