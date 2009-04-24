<?php
/**
 * File containing the ezcDocumentPdfParagraphRenderer class
 *
 * @package Document
 * @version //autogen//
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * Paragraph renderer
 *
 * Renders a single paragraph including its inline markup.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPdfParagraphRenderer extends ezcDocumentPdfTextBoxRenderer
{
    /**
     * Render a single paragraph
     *
     * All markup inside of the given string is considered inline markup (in
     * CSS terms). Inline markup should be given as common docbook inline
     * markup, like <emphasis>.
     *
     * Returns a boolean indicator whether the rendering of the full paragraph
     * in the available space succeeded or not.
     *
     * @param ezcDocumentPdfPage $page 
     * @param ezcDocumentPdfHyphenator $hyphenator 
     * @param ezcDocumentPdfInferencableDomElement $paragraph 
     * @return bool
     */
    public function render( ezcDocumentPdfPage $page, ezcDocumentPdfHyphenator $hyphenator, ezcDocumentPdfInferencableDomElement $paragraph )
    {
        // Inference page styles
        $styles = $this->styles->inferenceFormattingRules( $paragraph );
        $width  = $page->innerWidth / $styles['text-columns']->value -
            ( $styles['text-column-spacing']->value * ( $styles['text-columns']->value - 1 ) );

        // Evaluate available space
        if ( ( $space = $this->evaluateAvailableBoundingBox( $page, $styles, $width ) ) === false )
        {
            return false;
        }

        // Iterate over tokens and try to fit them in the current line, use
        // hyphenator to split words.
        $tokens = $this->tokenize( $paragraph );
        $lines  = $this->fitTokensInLines( $tokens, $hyphenator, $space->width );

        // Try to render text into evaluated box
        if ( ( $covered = $this->renderTextBox( $lines, $space, $styles ) ) === false )
        {
            return false;
        }

        // Mark used space covered and exit with success return code
        $page->setCovered(
            new ezcDocumentPdfBoundingBox( $space->x, $space->y, $space->width, $covered )
        );
        $page->y += $covered + $styles['margin']->value['bottom'];
        return true;
    }

    /**
     * Calculate paragraph width
     *
     * Calculate the available horizontal space for paragraphs depending on the
     * page layout settings.
     */
    protected function calculateParagraphWidth( ezcDocumentPdfPage $page, ezcDocumentPdfInferencableDomElement $paragraph )
    {
        // Inference page styles
        $rules = $this->styles->inferenceFormattingRules( $paragraph );

        return $page->innerWidth / $rules['text-columns']->value -
            ( $rules['text-column-spacing']->value * ( $rules['text-columns']->value - 1 ) );
    }
}
?>
