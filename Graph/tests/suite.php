<?php
/**
* ezcGraphSuite
*
* @package Graph
* @subpackage Tests
* @version //autogentag//
* @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
* @license LGPL {@link http://www.gnu.org/copyleft/lesser.html}
*/

/**
* Require test suites.
*/
require_once 'axis_centered_renderer_test.php';
require_once 'axis_exact_renderer_test.php';
require_once 'background_test.php';
require_once 'boundings_test.php';
require_once 'chart_test.php';
require_once 'color_test.php';
require_once 'dataset_average_test.php';
require_once 'dataset_numeric_test.php';
require_once 'dataset_pdo_test.php';
require_once 'dataset_test.php';
require_once 'date_axis_test.php';
require_once 'driver_flash_test.php';
require_once 'driver_gd_test.php';
require_once 'driver_options_test.php';
require_once 'driver_svg_test.php';
require_once 'element_options_test.php';
require_once 'font_test.php';
require_once 'image_map_test.php';
require_once 'labeled_axis_test.php';
require_once 'legend_test.php';
require_once 'line_test.php';
require_once 'logarithmical_axis_test.php';
require_once 'matrix_test.php';
require_once 'numeric_axis_test.php';
require_once 'palette_test.php';
require_once 'pie_test.php';
require_once 'polynom_test.php';
require_once 'renderer_2d_test.php';
require_once 'renderer_3d_test.php';
require_once 'struct_test.php';
require_once 'text_test.php';
require_once 'tools_test.php';
require_once 'vector_test.php';

/**
* Test suite for ImageAnalysis package.
*
* @package ImageAnalysis
* @subpackage Tests
*/
class ezcGraphSuite extends PHPUnit_Framework_TestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->setName( "Graph" );

        $this->addTest( ezcGraphAxisCenteredRendererTest::suite() );
        $this->addTest( ezcGraphAxisExactRendererTest::suite() );
        $this->addTest( ezcGraphBackgroundTest::suite() );
        $this->addTest( ezcGraphBoundingsTest::suite() );
        $this->addTest( ezcGraphChartTest::suite() );
        $this->addTest( ezcGraphColorTest::suite() );
        $this->addTest( ezcGraphDataSetAverageTest::suite() );
        $this->addTest( ezcGraphDataSetTest::suite() );
        $this->addTest( ezcGraphDateAxisTest::suite() );
        $this->addTest( ezcGraphDriverOptionsTest::suite() );
        $this->addTest( ezcGraphElementOptionsTest::suite() );
        $this->addTest( ezcGraphFlashDriverTest::suite() );
        $this->addTest( ezcGraphFontTest::suite() );
        $this->addTest( ezcGraphGdDriverTest::suite() );
        $this->addTest( ezcGraphImageMapTest::suite() );
        $this->addTest( ezcGraphLabeledAxisTest::suite() );
        $this->addTest( ezcGraphLegendTest::suite() );
        $this->addTest( ezcGraphLineChartTest::suite() );
        $this->addTest( ezcGraphLogarithmicalAxisTest::suite() );
        $this->addTest( ezcGraphMatrixTest::suite() );
        $this->addTest( ezcGraphNumericAxisTest::suite() );
        $this->addTest( ezcGraphNumericDataSetTest::suite() );
        $this->addTest( ezcGraphPaletteTest::suite() );
        $this->addTest( ezcGraphPdoDataSetTest::suite() );
        $this->addTest( ezcGraphPieChartTest::suite() );
        $this->addTest( ezcGraphPolynomTest::suite() );
        $this->addTest( ezcGraphRenderer2dTest::suite() );
        $this->addTest( ezcGraphRenderer3dTest::suite() );
        $this->addTest( ezcGraphStructTest::suite() );
        $this->addTest( ezcGraphSvgDriverTest::suite() );
        $this->addTest( ezcGraphTextTest::suite() );
        $this->addTest( ezcGraphToolsTest::suite() );
        $this->addTest( ezcGraphVectorTest::suite() );
    }

    public static function suite()
    {
        return new ezcGraphSuite( "ezcGraphSuite" );
    }
}
?>
