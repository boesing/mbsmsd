<?php
/**
 * ValidationInterface.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121012 11:09
 */
namespace MBSmsd\Request;

/**
 * Interface ValidationInterface
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121012 11:09
 */
interface ValidationInterface 
{
	/**
	 * Should return the validation Request
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121011 12:10
	 */
    public function validate();
}
