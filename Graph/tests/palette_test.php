<?php
/**
 * ezcGraphPaletteTest 
 * 
 * @package Graph
 * @version //autogen//
 * @subpackage Tests
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Tests for ezcGraph class.
 * 
 * @package ImageAnalysis
 * @subpackage Tests
 */
class ezcGraphPaletteTest extends ezcTestCase
{
	public static function suite()
	{
		return new ezcTestSuite( "ezcGraphPaletteTest" );
	}

    public function testDefaultPalette()
    {
        $chart = new ezcGraphLineChart();

        $this->assertTrue(
            $chart->palette instanceof ezcGraphPalette,
            'No default palette was set.'
        );
        
        $this->assertTrue(
            $chart->palette instanceof ezcGraphPaletteTango,
            'Default pallete should be tango.'
        );
    }

    public function testChangePalette()
    {
        $chart = new ezcGraphLineChart();
        $chart->palette = new ezcGraphPaletteBlack();

        $this->assertTrue(
            $chart->palette instanceof ezcGraphPalette,
            'No default palette was set.'
        );
        
        $this->assertTrue(
            $chart->palette instanceof ezcGraphPaletteBlack,
            'Default pallete should be tango.'
        );
    }

    public function testInvalidPalette()
    {
        try
        {
            $chart = new ezcGraphLineChart();
            // Silenced, because this throws an E_WARNING in devel mode,
            // caused by the non existing class ezcGraphPaletteUndefined
            @$chart->palette = 'Undefined';
        }
        catch ( ezcBaseValueException $e )
        {
            return true;
        }

        $this->fail( 'Expected ezcGraphUnknownPaletteException.' );
    }

    public function testChartBackgroundColor()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            ezcGraphColor::fromHex( '#EEEEEC' ),
            $chart->palette->chartBackground,
            'Background color not properly set.'
        );
    }

    public function testElementBackgroundColor()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            ezcGraphColor::fromHex( '#000000FF' ),
            $chart->palette->elementBackground,
            'Background color not properly set.'
        );
    }

    public function testAxisColor()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            ezcGraphColor::fromHex( '#2E3436' ),
            $chart->palette->axisColor,
            'Axis color not properly set.'
        );
    }

    public function testDataSetColor()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            ezcGraphColor::fromHex( '#3465A4' ),
            $chart->palette->dataSetColor,
            'DataSet color not properly set.'
        );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#4E9A06' ),
            $chart->palette->dataSetColor,
            'DataSet color not properly set.'
        );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#CC0000' ),
            $chart->palette->dataSetColor,
            'DataSet color not properly set.'
        );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#EDD400' ),
            $chart->palette->dataSetColor,
            'DataSet color not properly set.'
        );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#75505B' ),
            $chart->palette->dataSetColor,
            'DataSet color not properly set.'
        );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#F57900' ),
            $chart->palette->dataSetColor,
            'DataSet color not properly set.'
        );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#204A87' ),
            $chart->palette->dataSetColor,
            'DataSet color not properly set.'
        );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#C17D11' ),
            $chart->palette->dataSetColor,
            'DataSet color not properly set.'
        );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#3465A4' ),
            $chart->palette->dataSetColor,
            'DataSet color not properly set.'
        );
    }

    public function testDataSetSymbol()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            ezcGraph::NO_SYMBOL,
            $chart->palette->dataSetSymbol,
            'Symbol for datasets not properly set.'
        );

        $this->assertEquals(
            ezcGraph::NO_SYMBOL,
            $chart->palette->dataSetSymbol,
            'Symbol for datasets not properly set.'
        );
    }

    public function testFontName()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            'sans-serif',
            $chart->palette->fontName,
            'Font face not properly set.'
        );
    }

    public function testFontColor()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            ezcGraphColor::fromHex( '#2E3436' ),
            $chart->palette->fontColor,
            'Font color not properly set.'
        );
    }

    public function testChartBorderColor()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            ezcGraphColor::fromHex( '#000000FF' ),
            $chart->palette->chartBorderColor,
            'Border color not properly set.'
        );
    }

    public function testChartBorderWidth()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            0,
            $chart->palette->chartBorderWidth,
            'Border width not properly set.'
        );
    }

    public function testElementBorderColor()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            ezcGraphColor::fromHex( '#000000FF' ),
            $chart->palette->elementBorderColor,
            'Border color not properly set.'
        );
    }

    public function testElementBorderWidth()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            0,
            $chart->palette->elementBorderWidth,
            'Border width not properly set.'
        );
    }

    public function testPadding()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            1,
            $chart->palette->padding,
            'Padding not properly set.'
        );
    }

    public function testMargin()
    {
        $chart = new ezcGraphLineChart();

        $this->assertEquals(
            0,
            $chart->palette->margin,
            'Margin not properly set.'
        );
    }

    public function testDataSetAutomaticColorization()
    {
        $chart = new ezcGraphLineChart();
        $chart->data['income'] = new ezcGraphArrayDataSet( array( 2000 => 2345.2, 2456.3, 2567.4 ) );
        $chart->data['spending'] = new ezcGraphArrayDataSet( array( 2000 => 2347.2, 2458.3, 2569.4 ) );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#3465A4' ),
            $chart->data['income']->color->default,
            'Wrong automatic color set.'
        );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#4E9A06' ),
            $chart->data['spending']->color->default,
            'Wrong automatic color set.'
        );
    }

    public function testChartBackground()
    {
        $chart = new ezcGraphLineChart();
        $chart->data['income'] = new ezcGraphArrayDataSet( array( 2000 => 2345.2, 2456.3, 2567.4 ) );
        $chart->data['spending'] = new ezcGraphArrayDataSet( array( 2000 => 2347.2, 2458.3, 2569.4 ) );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#EEEEEC' ),
            $chart->background->background,
            'Chart background not set from pallet.'
        );
    }

    public function testChartElementBorder()
    {
        $chart = new ezcGraphLineChart();
        $chart->data['income'] = new ezcGraphArrayDataSet( array( 2000 => 2345.2, 2456.3, 2567.4 ) );
        $chart->data['spending'] = new ezcGraphArrayDataSet( array( 2000 => 2347.2, 2458.3, 2569.4 ) );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#000000FF' ),
            $chart->legend->border,
            'Chart background not set from pallet.'
        );
    }

    public function testChartElementBorderWidth()
    {
        $chart = new ezcGraphLineChart();
        $chart->data['income'] = new ezcGraphArrayDataSet( array( 2000 => 2345.2, 2456.3, 2567.4 ) );
        $chart->data['spending'] = new ezcGraphArrayDataSet( array( 2000 => 2347.2, 2458.3, 2569.4 ) );

        $this->assertEquals(
            0,
            $chart->legend->borderWidth,
            'Chart background not set from pallet.'
        );
    }

    public function testChartElementAxisColor()
    {
        $chart = new ezcGraphLineChart();
        $chart->data['income'] = new ezcGraphArrayDataSet( array( 2000 => 2345.2, 2456.3, 2567.4 ) );
        $chart->data['spending'] = new ezcGraphArrayDataSet( array( 2000 => 2347.2, 2458.3, 2569.4 ) );

        $this->assertEquals(
            ezcGraphColor::fromHex( '#2E3436' ),
            $chart->xAxis->border,
            'Chart background not set from pallet.'
        );
    }

    public function testModifyPaletteColorProperty()
    {
        $palette = new ezcGraphPaletteTango();

        $palette->axisColor = '#FFFFFF';
        $palette->majorGridColor = array( 255, 255, 255, 255 );
        $palette->minorGridColor = ezcGraphColor::fromHex( '#00000000' );

        $this->assertEquals(
            $palette->axisColor,
            ezcGraphColor::fromHex( '#FFFFFF' )
        );

        try
        {
            $palette->axisColor = false;
        }
        catch ( ezcGraphUnknownColorDefinitionException $e )
        {
            return true;
        }

        $this->fail( 'expected ezcGraphUnknownColorDefinitionException.' );
    }

    public function testModifyPaletteIntProperty()
    {
        $palette = new ezcGraphPaletteTango();

        $palette->chartBorderWidth = 0;
        $palette->padding = -1;
        $palette->margin = 3;

        $this->assertSame( $palette->chartBorderWidth, 0 );
        $this->assertSame( $palette->padding, 0 );
        $this->assertSame( $palette->margin, 3 );
    }

    public function testModifyPaletteDatasetColorArray()
    {
        $palette = new ezcGraphPaletteTango();

        $palette->dataSetColor = array(
            '#ABCDEF',
            array( 255, 255, 255 ),
        );

        $this->assertEquals(
            $palette->dataSetColor,
            ezcGraphColor::fromHex( '#ABCDEF' )
        );

        try
        {
            $palette->dataSetColor = '#FFFFFF';
        }
        catch ( ezcBaseValueException $e )
        {
            return true;
        }

        $this->fail( 'expected ezcBaseValueException.' );
    }

    public function testModifyPaletteDatasetSymbolArray()
    {
        $palette = new ezcGraphPaletteTango();

        $palette->dataSetSymbol = array(
            ezcGraph::BULLET,
            ezcGraph::CIRCLE
        );

        $this->assertSame(
            $palette->dataSetSymbol,
            ezcGraph::BULLET
        );

        try
        {
            $palette->dataSetSymbol = ezcGraph::BULLET;
        }
        catch ( ezcBaseValueException $e )
        {
            return true;
        }

        $this->fail( 'expected ezcBaseValueException.' );
    }
}

?>
