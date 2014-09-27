<?php
/**
 * AlternateRequestInterface.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121017 11:02
 */
namespace MBSmsd\Request;

/**
 * Interface AlternateRequestInterface
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121017 11:02
 */
interface AlternateRequestInterface 
{
	/**
	 * Checks or sets this alternate Request
	 *
	 * @param boolean OPTIONAL Default: null
	 * @return boolean
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121017 11:09
	 */
    public function isAlternateRequest( $status=null );
}
