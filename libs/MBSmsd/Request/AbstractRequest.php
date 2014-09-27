<?php
/**
 * Request.php
 *
 * @package MBSmsd
 * @subpackage 
 * @author Max Boesing <max@kriegt.es>
 * @since 20121016 13:14
 */
namespace MBSmsd;
use MBSmsd\Modem;

/**
 * class Request
 *
 * @package MBSmsd
 * @subpackage 
 * @author Max Boesing <max@kriegt.es>
 * @since 20121016 13:14
 */
abstract class AbstractRequest
{
    protected   $modem;
    protected   $validated  = false;

    /**
     * Create new Request
     *
     * @return Request
     * @author Max Boesing <max@kriegt.es>
     * @since 20121016 13:14
     */
    public function __construct( Modem $modem )
    {
        $this->setModem($modem);
    }

	/**
	 * Sets the Modem we want to use for the Request
	 *
	 * @param Modem
	 * @return Request
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121120 15:04
	 */
    public function setModem( Modem $m )
    {
        $this->modem = $m;
        return $this;
    }
    
    /**
     * Magic Method __toString
	 *
	 * @return string
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120917 17:21
	 */
    abstract public function __toString();

	/**
     * Should return true if we expecting
     * a response
     * Default: false
	 *
	 * @return boolean
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120918 15:56
	 */
    public function hasResponse() {
        return false;
    }

	/**
	 * Executes this Request
	 *
	 * @return Response
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121120 15:04
	 */
    public function execute()
    {
        $command = (string)$this;
        return $this->_execute($command);
    }

	/**
	 * Executes this Request on given Modem
	 *
	 * @return Response|null
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121016 13:23
	 */
    protected function _execute($command)
    {
        $this->_validate();

        $this->modem->send($command);
        usleep(50000);

        if( $this instanceOf Request\IgnoreResponseInterface ) {
            $this->modem->read(); // clear at least the response if there is any
            return null;
        }

        $response = null;

        $className = get_class($this);
        $className = ucfirst( strtolower( substr($className,strrpos($className,'\\')+1) ) );
        if( $this->hasResponse() ) {
            $responseClass = sprintf( '\\%s\\Response\\%s', __NAMESPACE__, $className );

            if( !class_exists($responseClass) ) {
                throw new \RuntimeException("Could not find valid Response class for \"$className\"");
            }
            $response = new $responseClass;
        }
        $command = rtrim($command);

        $start = time();
        $loops = 0;
        $result = "";
        $checkResponse = false;
        // Skip checkResponse if this is a Cmgs Request!
        if( get_class($this) == 'MBSmsd\Request\Cmgs' ) {
            $checkResponse = true;
        }
        do {
            $responseIdentificator = sprintf('+%s',strtoupper($className));
            $bytes = $this->modem->read();
            $result .= rtrim($bytes); 
            if( $checkResponse === false ) {
                if( mb_substr( $result, 0, mb_strlen($command) ) != $command ) {
                    throw new \RuntimeException( "This is not the response for the command sent. Request: $command, Response: $result" );
                }
                $result = trim( mb_substr( $result, mb_strlen($command)+1 ) );
            }
            $checkResponse = true;

//            print "_execute: ". get_class($this) . " - result: $result\n";

            // First we check for errors
            if( strpos($result,'ERROR') !== false ) {
                if( $this instanceOf Request\AlternateRequestInterface && !$this->isAlternateRequest() ) {
                    $this->isAlternateRequest(true);
                    return $this->execute();
                }
                if( strpos($result,'+CMS') !== false ) {
                    $response = new Response\Cms;
                    $response->setRawResponse($result);
                    throw new \RuntimeException(
                        $response
                    );
                }
                trigger_error("The command \"$command\" is not supported or miss-spelled!", E_USER_ERROR);
                return null;
            }

            // If there is no error, we assume that the command was
            // successfully executed
            if(! $response instanceof Response\ResponseInterface ) {
                return null;
            } 

            if( ++$loops >= 1 ) {
                if( $this instanceOf Request\DelayedResponseInterface ) {
                    if( $loops == 1 ) {
                        $result = "";
                        continue;
                    }
                } else if( mb_substr($result,-2) != 'OK' ) {
                    if( (time() >= $start + Modem::RESPONSE_TIMEOUT) ) {
                        throw new \RuntimeException("Command \"$command\": Response timeout.");
                    }
                    continue;
                }

                if( strpos($result, $responseIdentificator) === false ) {
                    if( (time() >= $start + Modem::RESPONSE_TIMEOUT) ) {
                        throw new \RuntimeException("Command \"$command\": Response timeout.");
                    }
                    if( mb_substr($result,-2) != 'OK' && $this instanceOf Request\DelayedResponseInterface ) {
                        continue;
                    }
                }

                $response->setRawResponse( $result );
                break;
            }

            $response->setRawResponse( $result );
            break;
        } while( (time() < $start + Modem::RESPONSE_TIMEOUT));

        return $response;
    }

	/**
	 * Klassendefinition
	 *
	 * @param 
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20121120 14:54
	 */
    protected function _validate()
    {
        if(! $this instanceOf Request\ValidationInterface || $this->validated ) {
            return;
        }

        $requestClass = get_class($this);

        $command = strtoupper( $requestClass );

        $request = $this->validate();

        if( $request instanceOf Request\ValidationChain ) {
            throw new \RuntimeException("What the hell is this? This will NEVER EVER FUCKIN WORK");
            foreach( $request AS $validationCommand ) {
                $this->_validate( $validationCommand );
            }
            return;
        }

        $requestLength = mb_strlen($request);
        $written = $this->modem->send( $request, $requestLength );
        if( $written != $requestLength ) {
            throw new \RuntimeException("Could not pass the whole request to device, only sent {$written}/$requestLength bytes");
        }
        usleep( 50000 );
        
        // Remove the \r from the Request to compare with trimmed response
        $request = rtrim( $request );
        $response = null;
        $start = time();

        $result = $this->modem->read();
        $result = str_replace("\r","",$result);
        if( substr($result, 0, mb_strlen($request)) != $request ) {
            throw new \RuntimeException("This is not the response for our request! Request: $request, Response: $result");
        }

        $result = substr( $result, mb_strlen($request) );

        if( strpos($result,"ERROR") !== false ) {
            $className = $requestClass;
            throw new \RuntimeException(
                sprintf(
                    'Command "%s" is not supported by this Modem!',
                    strtoupper( substr($className,strrpos($className,'\\')+1) )
                )
            );
        } else if( strpos($result,'NO CARRIER') !== false ) {
            $request = new Request\Cops( $this->modem );
            $request->execute();
            usleep(10);
            continue;
        }

        if( strpos($result,"OK") !== false ) {
            $this->validated = true;
            return;
        }


        throw new \RuntimeException( "Something went wrong during validation of \"". get_class($this) . "\"" );
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 textwidth=80 foldmethod=marker:
 */
