<?php
/**
 * class.Loader.php
 *
 * @package MBSmsd
 * @author Max Boesing <max@kriegt.es>
 * @since 20120403 10:03
 */
namespace MBSmsd;

/**
 * class Loader
 *
 * @package MBSmsd
 * @author Max Boesing <max@kriegt.es>
 * @since 20120403 10:03
 */
class Loader
{
    const       DEFAULT_NAMESPACE_SEPARATOR     = '\\';
    protected   $vendors                        = array();
    protected   $fileExtension                  = '.php';
    protected   $includePath;
    protected   $registered                     = false;
    protected   $namespaceSeparator             = self::DEFAULT_NAMESPACE_SEPARATOR;
    protected   $specialFiles                   = array(
        'Abstract',
        'Interface',
    );

    /**
     * Create new AutoLoader
     *
     * @return AutoLoader
     * @author Max Boesing <info@mbcode.eu>
     * @since 20111006 16:08
     */
    public function __construct( $vendors=array(), $includePath=null, $namespaceSeparator=null, $specialFiles=array() ) 
    {
        if( !is_array($vendors) && !is_null($vendors) ) {
            $vendors = array($vendors);
        }

        if( !is_array($specialFiles) ) {
            $specialFiles = array($specialFiles);
        }

        if( !empty($specialFiles) ) {
            $this->specialFiles = $specialFiles;
        }
        
        // If the includePath isnt set and we are
        // the Loader for the namespace we are in, we know
        // the includePath must be the parent Directory
        // CAUTION - we expect that the Directory-Name
        // is named as the namespace, e.g. MBSmsd
        if( $includePath === null ) {
            if( in_array(__NAMESPACE__, $vendors) ) {
                $includePath = realpath( __DIR__ . '/../' );
            } else {
                $includePath = get_include_path();
            }
        }

        if( $namespaceSeparator !== null ) {
            $this->namespaceSeparator = $namespaceSeparator;
        }

        if( !empty($vendors) ) {
            $this->vendors = $vendors;
        }
        $this->includePath = realpath($includePath);
        $this->validate();
        $this->register();
    }

	/**
	 * Adds this Loader to the SPL autoloader stack
	 *
     * @param string[] OPTIONAL
	 * @return void
	 * @author Max Boesing <info@mbcode.eu>
	 * @since 20111006 16:43
	 */
    public function register( $vendors=array() ) 
    {
        if( !is_array($vendors) ) {
            $vendors = array($vendors);
        }

        $this->vendors = array_merge( $this->vendors, $vendors );

        $this->validate();

        if( $this->registered !== true ) {
            spl_autoload_register( array($this, 'autoload') );
            spl_autoload_extensions( $this->fileExtension );
            $this->registered = true;
        }
    }

	/**
	 * Removes this Loader from the SPL autoloader stack
	 *
     * @param string[] OPTIONAL
	 * @return void
	 * @author Max Boesing <info@mbcode.eu>
	 * @since 20111006 16:43
	 */
    public function unregister( $vendors=array() )
    {
        if( !is_array($vendors) ) {
            $vendors = array($vendors);
        }
        if( !empty($vendors) ) {
            $this->vendors = array_diff( $this->vendors, $vendors );
            if( !empty($this->vendors) ) {
                return;
            }
        }

        if( $this->registered === true ) {
            spl_autoload_unregister( array($this, 'autoload') );
            $this->registered = false;
        }
    }

	/**
	 * Loads the given class/interface
	 *
	 * @param string
	 * @return boolean
	 * @author Max Boesing <info@mbcode.eu>
	 * @since 20111006 16:43
	 */
    public function autoload( $className ) 
    {
        $className = ltrim($className, $this->namespaceSeparator);
        // Check if this Loader should handle the given stuff
        if( empty($this->vendors) ) {
            return false;
        }

        $vendor = substr($className, 0, strpos($className, $this->namespaceSeparator));
        
        if( !in_array($vendor, $this->vendors) ) {
            return false;
        }

        $fileName = '';
        $namespace = '';
        if( ($lastNsPos = strripos($className, $this->namespaceSeparator)) !== false ) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace( $this->namespaceSeparator, DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace($this->namespaceSeparator, DIRECTORY_SEPARATOR, $className) . $this->fileExtension;

        // First we check for special files
        if( ($data = $this->autoloadSpecialFiles( $fileName )) !== false ) {
            return $data;
        }
        
        $filePath = $this->includePath . DIRECTORY_SEPARATOR;

        if( file_exists($filePath . $fileName ) ) {
            return include $filePath . $fileName;
        }

        return false;
    }

	/**
	 * Okay, lets check if we got some special files in a directory called className
	 *
	 * @param string
	 * @return string|false
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120612 14:22
	 */
    protected function autoloadSpecialFiles( $fileName )
    {
        $newDirectory = $this->includePath . DIRECTORY_SEPARATOR . substr( $fileName, 0, -strlen($this->fileExtension) );
        foreach( $this->specialFiles AS $specialFile ) {
            $newFile = $newDirectory . DIRECTORY_SEPARATOR . $specialFile . $this->fileExtension;
            if( file_exists( $newFile ) ) {
                return include $newFile;
            }
        }

        return false;
    }

	/**
	 * Checks if given Vendor can be found in given includePath
	 *
	 * @return void
     * @throws \RuntimeException
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120402 10:44
	 */
    protected function validate()
    {
        foreach( $this->vendors AS $vendor ) {
            $path = $this->includePath . DIRECTORY_SEPARATOR . $vendor;
            if( !is_dir($path) || !is_readable($path) ) {
                throw new \RuntimeException("Cannot find or read vendor directory for given vendor \"$vendor\"");
            }
        }
    }

	/**
     * Unregistering the AutoLoad stuff before Destroy
     * this Object 
	 *
	 * @return void
	 * @author Max Boesing <max@kriegt.es>
	 * @since 20120402 10:44
	 */
    public function __destruct()
    {
        $this->unregister();
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 foldmethod=marker:
 */
