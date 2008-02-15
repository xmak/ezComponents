<?php
/**
 * File containing the ezcDebugPhpStacktraceIterator class.
 *
 * @package Debug
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Iterator class to wrap around debug_backtrace() stack traces.
 *
 * This iterator class receives a stack trace generated by debug_backtrace()
 * and unifies it as described in the {@link ezcDebugStacktraceIterator}
 * interface.
 * 
 * @package Debug
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
class ezcDebugPhpStacktraceIterator
{
    const MAX_DATA = 512;

    const MAX_CHILDREN = 128;

    protected function prepare( $stackTrace )
    {
        return parent::prepare(
            // Pop first 2 elements to ignore log() and getStackTrace() calls
            array_slice( $stackTrace, 2 )
        );
    }

    /**
     * Unifies a stack element for being returned to the formatter.
     *
     * This method ensures that an element of the stack trace conforms to the
     * format expected by a {@link ezcDebugOutputFormatter}. The format is
     * defined as follows:
     *
     * <code>
     * array(
     *      'file'      => '<fullpathtofile>',
     *      'line'      => <lineno>,
     *      'function'  => '<functionname>',
     *      'class'     => '<classname>',
     *      'params'    => array(
     *          <param_no> => '<paramvalueinfo>',
     *          <param_no> => '<paramvalueinfo>',
     *          <param_no> => '<paramvalueinfo>',
     *          ...
     *      )
     * )
     * </code>
     * 
     * @param mixed $stackElement 
     * @return array As described above.
     */
    protected function unifyStackElement( $stackElement )
    {
        // Not to be set in the unified version
        unset( $stackElement['type'] );

        // Unify args -> params
        $stackElement['params'] = self::dumpVariables( $stackElement['args'] );
        unset( $stackElement['args'] );
        
        return $stackElement;
    }

    /**
     * Returns the arguments of a stack element as string dumps.
     *
     * Returns an array corresponding to the 'params' key of a unified stack
     * element, created from the 'args' ($args) element from an unified one.
     * 
     * @param array $args 
     * @return array
     */
    private function convertArgsToParams( $args )
    {
        $params = array();
        foreach ( $args as $arg )
        {
            $params[] = self::dumpVariable( $arg );
        }
        return $params;
    }

    /**
     * Returns the string representation of an variable.
     *
     * Returns the dump of the given variable, respecting the $maxData and
     * $maxChildren paramaters when arrays or objects are dumped.
     * 
     * @param mixed $arg 
     * @param int $maxData 
     * @param int $maxChildren 
     * @return string
     */
    public static function dumpVariable( $arg, $maxData = self::MAX_DATA, $maxChildren = self::MAX_CHILDREN )
    {
        switch ( gettype( $arg ) )
        {
            case 'boolean':
                return self::cutString( ( $arg ? 'TRUE' : 'FALSE' ), $maxData );
            case 'integer':
            case 'double':
                return self::cutString( (string) $arg, $maxData );
            case 'string':
                return sprintf(
                    "'%s'",
                    self::cutString( (string) $arg, $maxData )
                );
            case 'array':
                return self::dumpArray( $arg, $maxData, $maxChildren );
            case 'object':
                return self::dumpObject( $arg, $maxData, $maxChildren );
            case 'resource':
                return self::dumpResource( $arg, $maxData );
            case 'NULL':
                return 'NULL';
            default:
                return 'unknown type';
        }
    }

    /**
     * Returns the string representation of an array.
     *
     * Returns the dump of the given array, respecting the $maxData and
     * $maxChildren paramaters.
     * 
     * @param array $arg 
     * @param int $maxData 
     * @param int $maxChildren 
     * @return string
     */
    private static function dumpArray( array $arg, $maxData, $maxChildren )
    {
        $max = min( count( $arg ), $maxChildren );
    
        $results = array();
        reset( $arg );
        for ( $i = 0; $i < $max; ++$i )
        {
            $results[] =
                self::dumpVariable( key( $arg ), $maxData, $maxChildren )
                . ' => '
                . self::dumpVariable( current( $arg ), $maxData, $maxChildren );
            next( $arg );
        }

        if ( $max < count( $arg ) )
        {
            $results[] = '...';
        }
        
        return sprintf(
            'array (%s)', implode( ', ', $results )
        );
    }

    /**
     * Returns the string representation of an object.
     *
     * Returns the dump of the given object, respecting the $maxData and
     * $maxChildren paramaters.
     * 
     * @param object $arg 
     * @param int $maxData 
     * @param int $maxChildren 
     * @return string
     */
    private static function dumpObject( $arg, $maxData, $maxChildren )
    {
        $refObj   = new ReflectionObject( $arg );
        $refProps = $refObj->getProperties();

        $max = min(
            count( $refProps ),
            $maxChildren
        );
        
        $results = array();
        reset( $refProps );
        for( $i = 0; $i < $max; $i++ )
        {
            $refProp = current( $refProps );
            $results[] = sprintf(
                '%s $%s = %s',
                self::getPropertyVisibility( $refProp ),
                $refProp->getName(),
                self::getPropertyValue( $refProp, $arg )
            );
            next( $refProps );
        }
        
        return sprintf(
            'class %s { %s }',
            $refObj->getName(),
            implode( '; ', $results )
        );
    }

    private static function dumpResource( $res, $maxData )
    {
        // resource(5) of type (stream)
        preg_match( '(Resource id #(?P<id>\d+))', (string) $res, $matches );
        return sprintf(
            'resource(%s) of type (%s)',
            $matches['id'],
            get_resource_type( $res )
        );
    }

    /**
     * Returns the $value cut to $length and padded with '...'.
     *
     * @param string $value 
     * @param int $length 
     * @return string
     */
    private static function cutString( $value, $length )
    {
        if ( strlen( $value ) > ( $length - 3 ) )
        {
            return substr( $value, 0, ( $length - 3 ) ) . '...';
        }
        return $value;
    }

    /**
     * Returns private, protected or public.
     *
     * Returns the visibility of the given relfected property $refProp as a
     * readable string.
     * 
     * @param ReflectionProperty $refProp 
     * @return string
     */
    private static function getPropertyVisibility( ReflectionProperty $refProp )
    {
        $info = '%s %s = %s';
        switch ( true )
        {
            case $refProp->isPrivate():
                return 'private';
            case $refProp->isProtected():
                return 'protected';
            case $refProp->isPublic():
            default:
                return 'public';
        }
    }

    /**
     * Returns the dumped property value.
     *
     * Returns the dumped value for the given reflected property ($refProp) on
     * the given $obj. Makes use of the ugly array casting hack to determine
     * values of private and protected properties.
     * 
     * @param ReflectionProperty $refProp 
     * @param object $obj 
     * @return string
     */
    private static function getPropertyValue( ReflectionProperty $refProp, $obj )
    {
        $value = null;
        // @TODO: If we switch to a PHP version where Reflection can access
        // protected/private property values, we should change this to the
        // correct way.
        if ( !$refProp->isPublic() )
        {
            $objArr    = (array) $obj;
            $className = ( $refProp->isProtected() ? '*' : $refProp->getDeclaringClass()->getName() );
            $propName  = $refProp->getName();
            $value     = $objArr["\0{$className}\0{$propName}"];
        }
        else
        {
            $value = $refProp->getValue( $obj );
        }
        return self::dumpVariable( $value );
    }
}

?>
