<?php
/**
 * BackendInterface.php
 *
 * @package MBSmsd
 * @author Max Boesing <max@kriegt.es>
 * @since 20121018 15:10
 */
namespace MBSmsd;

/**
 * interface BackendInterface
 *
 * @package MBSmsd
 * @author Max Boesing <max@kriegt.es>
 * @since 20121018 15:10
 */
interface BackendInterface
{
	/**
	 * Should store the Message into Backend
	 *
	 * @param Message
	 * @return boolean
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121018 15:11
	 */
    public function store( Message $m );
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
