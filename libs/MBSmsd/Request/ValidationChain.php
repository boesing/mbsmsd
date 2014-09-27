<?php
/**
 * ValidationChain.php
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121121 17:00
 */
namespace MBSmsd\Request;
use Iterator;

/**
 * class ValidationChain
 *
 * @package MBSmsd
 * @subpackage Request
 * @author Max Boesing <max@kriegt.es>
 * @since 20121121 17:00
 */
class ValidationChain implements Iterator
{
    protected   $current            = 0;
    protected   $validationCommands = array();

    /**
     * Create new ValidationChain
     *
     * @return ValidationChain
     * @author Max Boesing <max@kriegt.es>
     * @since 20121121 17:00
     */
    public function __construct( $commands = array() )
    {
        $this->validationCommands = $commands;
    }

	/**
	 * Adds another command to the Chain
	 *
	 * @param string 
	 * @return ValidationChain 
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121121 17:00
	 */
    public function addCommand( $command )
    {
        if( !is_string($command) ) {
            throw new \InvalidArgumentException( "Given argument must be string!" );
        }

        if( !in_array($command, $this->validationCommands) ) {
            $this->validationCommands[] = $command;
        }

        return $this;
    }

	/**
	 * Iterator: rewind
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121121 17:00
	 */
    public function rewind()
    {
        $this->current = 0;
    } 

	/**
	 * Iterator: key
	 *
	 * @return int
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121121 17:00
	 */
    public function key()
    {
        return $this->current;
    }

	/**
	 * Iterator: next
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121121 17:00
	 */
    public function next()
    {
        $this->current++;
    }

	/**
	 * Iterator: valid
	 *
	 * @return boolean
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121121 17:00
	 */
    public function valid()
    {
        return (count($this->validationCommands) > 0 && isset($this->validationCommands[$this->current]));
    }

	/**
	 * Iterator: current
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121121 17:00
	 */
    public function current()
    {
        return $this->validationCommands[$this->current];
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
