<?php
/**
 * Cops.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121120 16:22
 */
namespace MBSmsd\Request;
use MBSmsd\Request\AbstractRequest AS Request;

/**
 * class Cops
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121120 16:22
 */
class Cops extends Request implements IgnoreResponseInterface
{
	/**
	 * Returns the AT+COPS command
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121120 16:22
	 */
    public function __toString()
    {
        return "AT+COPS=0\r";
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
