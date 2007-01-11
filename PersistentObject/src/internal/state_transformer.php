<?php
/**
 * File containing the ezcPersistentFindIterator class
 *
 * @package PersistentObject
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * This internal class provides functionality to transform between
 * row and state arrays.
 *
 * @package PersistentObject
 * @access private
 */
class ezcPersistentStateTransformer
{
    /**
     * Returns the the row $row retrieved from PDO transformed into a state array
     * that can be used to set the state on a persistent object.
     *
     * $def holds the definition of the persistent object the $row maps to.
     *
     * The most basic task is to transform the database column names into
     * property names.
     *
     * @throws ezcPersistentException if a fatal error occured during the transformation
     * @param array $row
     * @param ezcPersistentDefinition $def
     * @return array
     */
    public static function rowToStateArray( array $row, ezcPersistentObjectDefinition $def )
    {
        $result = array();
        foreach ( $row as $key => $value )
        {
            // todo: everything in $row is of type string
            // should we convert to the correct PHP type?
            if ( $key == $def->idProperty->columnName )
            {
                $result[$def->idProperty->propertyName] = $value;
            }
            else
            {
                $result[$def->columns[$key]->propertyName] = $value;
            }
        }
        return $result;
    }
}

?>
