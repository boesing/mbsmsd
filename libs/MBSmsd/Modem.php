<?php
/**
 * Modem.php
 *
 * @package MBSmsd
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:34
 */
namespace MBSmsd;

/**
 * class Modem
 *
 * @package MBSmsd
 * @author Max Boesing <max@kriegt.es>
 * @since 20120918 15:34
 */
class Modem
{
    /**
     * BYTES we want to read from the device
     *
     * @var int
     */
    const       BYTES               = 8192;

    /**
     * RESPONSE_TIMEOUT in seconds
     *
     * @var int
     */
    const       RESPONSE_TIMEOUT    = 30;

    /**
     * Pidfile we want to use as a lock
     * so no scripts can pass through
     *
     * @var string
     */
    const       LOCKFILE            = '/var/run/mbsmsd.pid';

    /**
     * device we want to open
     *
     * @var string
     */
    protected   $device;

    /**
     * handle
     *
     * @var resource
     */
    protected   $handle;

    /**
     * initialized
     *
     * @var boolean
     */
    protected   $initialized        = false;

    /**
     * number
     * 
     * @var string
     */
    protected   $number;

    /**
     * Create new Modem
     *
     * @return Modem
     * @author Max Boesing <max@kriegt.es>
     * @since 20120918 15:34
     */
    public function __construct( $device )
    {
        if( !extension_loaded('dio') ) {
            throw new \RuntimeException('Need pecl-extension \'dio\' - please load \'dio.so\' in php.ini or conf.d/');
        }
        $this->device = $device;
        if( !is_readable( $this->device ) || !is_writeable( $this->device ) ) {
            throw new \RuntimeException("Given device \"$device\" is not readable or not writable - please start this skript with root permissions");
        }
        
        // Ensure that we allways disconnect the serial device
        register_shutdown_function( array($this,'__destruct') );
        if( extension_loaded('pcntl') ) {
            declare( ticks=1 );
            pcntl_signal(SIGTERM, array($this,'__destruct'));
            pcntl_signal(SIGHUP, array($this,'__destruct'));
            pcntl_signal(SIGINT, array($this,'__destruct'));
        }
    }

	/**
	 * Creates a pidfile to simulate lock
     * Maximum waittime will be Modem::RESPONSE_TIMEOUT
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20130915 00:56
	 */
    protected function waitlock()
    {
        $timeout = Modem::RESPONSE_TIMEOUT;
        $start = time();
        while( file_exists(Modem::LOCKFILE) ) {
            if( time() > $start + $timeout || filemtime(Modem::LOCKFILE) < $start - $timeout) {
                if( extension_loaded( 'posix') ) {
                    $pid = file_get_contents( Modem::LOCKFILE );
                    @posix_kill( $pid, SIGKILL );
                }
                break;
            }
            sleep(1);
        }

        file_put_contents( Modem::LOCKFILE, getmypid() );
    }

	/**
	 * Returns the Number of this Modem
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121013 01:57
	 */
    public function getNumber()
    {
        if( !$this->initialized ) {
            throw new \RuntimeException("First open a connection to the device before requesting the Number!");
        }
        return $this->number;
    }

	/**
     * Opens the Modem for Access
	 *
	 * @param int OPTIONAL Default: 19200
	 * @return Modem
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:35
	 */
    public function open( $baudrate=19200 )
    {
        $this->waitlock();

        $this->handle = @dio_open( $this->device, O_RDWR | O_NOCTTY | O_NONBLOCK, O_RDWR );
        if( $this->handle === false ) {
            throw new \RuntimeException("Cannot Open {$this->device} - Permission denied or not mounted?");
        }

        dio_fcntl($this->handle, F_SETFL, O_SYNC);
        dio_tcsetattr(
            $this->handle,
            array(
                'baud'  => $baudrate,
                'block' => 0,
            )
        );

        $this->init();

        return $this;
    }

	/**
	 * Closes the Connection to our Modem 
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121122 11:04
	 */
    public function close()
    {
        if( is_resource($this->handle) ) {
            @dio_close( $this->handle );
        }
    }

	/**
	 * Initializes this Modem
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:35
	 */
    protected function init()
    {
        if( $this->initialized ) {
            return;
        }
        
        // If the latest AT-Command failed, we can reset the Modem by using this 
        // combination (CTRL + Z)
        if( extension_loaded('pcntl') && extension_loaded('posix') ) {
            declare( ticks = 1 );
            $pid = pcntl_fork();
            if( $pid == -1 ) {
                trigger_error( "Could not fork, maybe we get a timeout here", E_USER_ERROR );
            } else if( $pid > 0 ) {
                usleep(50000);
                posix_kill( $pid, SIGKILL );
            } else {
                $this->send( chr(0x1A) );
                usleep(50000);
                $this->read();
                posix_kill( getmypid(), SIGKILL );
                exit(0);
            }
        }

        $command = new Request\At( $this );
        $command->execute();

        // Disable DEBUG messages like ^BOOT, e.g.
        $this->send( "AT^CURC=0\r" );
        usleep(50000);
        $this->read();

        $command = new Request\Cnum( $this );
        $response = $command->execute();
        $this->number = (string)$response;
        
        $this->initialized = true;
    }

	/**
     * Executes the CUSD-Command and returns the
     * Text using given "code"
	 *
	 * @param string
	 * @return Response
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:57
	 */
    public function getPrepaidAmount( $code='*100#' )
    {
        $command = new Request\Cusd( $this );
        $command->setCode( $code );
        $response = $command->execute();
        return $response;
    }

	/**
	 * Returns Mailbox Messages related to given Status
	 *
	 * @param int
	 * @return Message[]
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 11:49
	 */
    public function getMailboxMessages( $status = Request\Cmgl::ST_LIST_ALL )
    {
        # First, execute Cmgf to ensure we are in PDU mode
        $request = new Request\Cmgf( $this );
        $request->execute();

        $request = new Request\Cmgl( $this );
        $request->setMessageStatus( $status );
        $messages = $request->execute();
        if(! $messages instanceOf Response\Cmgl ) {
            throw new \RuntimeException("Something went wrong while executing the AT+CMGL Command!");
        }

        $messages = $messages->toArray();

        // Will contain concatenated Messages
        // e.g. UserDataHeader
        //      050003CC0201 [ message ] 
        //      050003CC0202 [ message ]
        // array(
        //     '050003CC02' => array(
        //         '01' => Message,
        //         '02' => Message,
        //     ),
        // )
        $concatenatedMessages = array(); 

        // check for messages with UserDataHeader
        foreach( $messages AS $key => $message ) {
            $message->setModem( $this );
            if( ($udh = $message->getUserDataHeader()) !== false ) {
                $messageId = substr($udh,0,-2);
                $partNumber = hexdec( substr($udh,-2) );
                $concatenatedMessages[$messageId][$partNumber] = $message;
                unset($messages[$key]);
                continue;
            }
        }

        foreach( $concatenatedMessages AS $messageId => $data ) {
            ksort($data);
            $message = null;
            foreach( $data AS $messagePart ) {
                if( !isset($message) ) {
                    $message = $messagePart;
                    $messages[] = $message;
                    continue;
                }
                $message->addText( $messagePart );
            }
        }

        return $messages;
    }

	/**
	 * Sends the given Request to the Modem
	 *
     * @param string
     * @param int OPTIONAL Default: null
	 * @return int|false
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:35
	 */
    public function send( $command, $length=null )
    {
        if( $this->handle === null ) {
            throw new \RuntimeException('First open the connection!');
        }

        if( $length === null ) {
            $length = mb_strlen($command);
        }

#        echo __METHOD__ . ": ". trim($command) . PHP_EOL;

        return dio_write( $this->handle, $command, $length );
    }

	/**
     * Reads the response
	 *
	 * @return null|string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121009 15:14
	 */
    public function read()
    {
        if( $this->handle === null ) {
            throw new \RuntimeException('This Modem is not connected!');
        }

        $result = dio_read( $this->handle, static::BYTES );
#        echo __METHOD__ . ": ". trim($result) . PHP_EOL;
        return $result;
    }

	/**
	 * Freeing ressources
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121015 16:47
	 */
    public function __destruct()
    {
        if( file_exists( Modem::LOCKFILE ) ) {
            @unlink( Modem::LOCKFILE );
        }
        $this->close();
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
