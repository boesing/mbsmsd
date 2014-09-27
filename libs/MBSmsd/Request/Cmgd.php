<?php
/**
 * Cmgd.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121018 16:33
 */
namespace MBSmsd\Request;
use MBSmsd\Request\AbstractRequest AS Request;

/**
 * class Cmgd
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121018 16:33
 */
class Cmgd extends Request
{
    const       F_DELETE_READ_MESSAGES          = 1;
    const       F_DELETE_READ_SENT_MESSAGES     = 2;
    const       F_DELETE_READ_OUT_MESSAGES      = 3;
    const       F_DELETE_ALL                    = 4;

    protected   $index  = -1;
    protected   $flag   = null;

	/**
	 * Sets the Message Id
	 *
	 * @param int 
	 * @return Cmgd
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 16:32
	 */
    public function setMessageId( $id )
    {
        $this->index = (int)$id;
        return $this;
    }

	/**
	 * Sets the flag 
	 *
	 * @param int
	 * @return Cmgd
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20130916 11:38
	 */
    public function setFlag( $flag ) 
    {
        $this->flag = (int)$flag;
        return $this;
    }

	/**
	 * Returns the AT+CMGD-String
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 16:32
	 */
    public function __toString()
    {
        $command = sprintf('AT+CMGD=%d', $this->index);
        if( $this->flag !== null ) {
            $command .= sprintf(',%d', $this->flag);
        }
        return "$command\r";
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
