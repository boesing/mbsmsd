<?php
/**
 * Cnum.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121013 01:55
 */
namespace MBSmsd\Request;
use MBSmsd\Request\AbstractRequest AS Request;

/**
 * class Cnum
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121013 01:55
 */
class Cnum extends Request implements ValidationInterface
{
	/**
     * Returns true because this Command has
     * Response
	 *
	 * @return true
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:54
	 */
    public function hasResponse()
    {
        return true;
    }

	/**
	 * Returns the validation string
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121017 10:28
	 */
    public function validate()
    {
        return "AT+CNUM=?\r";
    }

	/**
	 * Returns the AT-Command for CNUM
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:54
	 */
    public function __tostring()
    {
        return "AT+CNUM\r";
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
