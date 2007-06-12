<?php
/**
 * File containing the ezcAuthenticationLdapException class.
 *
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 * @package Authentication
 * @version //autogen//
 */

/**
 * Thrown when an exceptional state occurs in the LDAP authentication.
 *
 * @package Authentication
 * @version //autogen//
 */
class ezcAuthenticationLdapException extends ezcAuthenticationException
{
    /**
     * Constructs a new ezcAuthenticationLdapException with error message
     * $message and error code $code.
     *
     * Code $code is received in decimal format and will be displayed in
     * hexadecimal format. See http://php.net/manual/en/function.ldap-errno.php
     * for all the error codes returned by ldap_errno().
     *
     * @param string $message Message to throw
     * @param mixed $code Error code returned by ldap_errno() function
     */
    public function __construct( $message, $code = false )
    {
        if ( $code === false )
        {
            parent::__construct( $message );
        }
        else
        {
            parent::__construct( $message . ' (0x' . dechex( $code ) . ')' );
        }
    }
}
?>
