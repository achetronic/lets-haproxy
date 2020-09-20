<?php

include_once("LogController.php");

/**
 * This class has all needed functions
 * to 
 * 
 * 
 */
class SchemaController 
{
    /**
     * The path to the template file with
     * some pre-configured Haproxy frontends for
     * HTTP and HTTPS
     * 
     * @var string
     */
    public $templateFrontendsHttpFile;



    /**
     * The path to the template file with
     * some pre-configured Haproxy global vars
     * 
     * @var string
     */
    public $templateGlobalFile;



    /**
     * The path to the template file with
     * some pre-configured Haproxy default vars 
     * 
     * @var string
     */
    public $templateDefaultsFile;



    /**
     * Path to a file for storing
     * IP port binding frontends temporary
     * 
     * @var string
     */
    public $tmpFrontendsTcpFile;



    /**
     * Path to a file for storing
     * Domain virtualhosts frontends temporary
     * 
     * @var string
     */
    public $tmpFrontendsHttpFile;



    /**
     * Path to a file for storing
     * backends temporary
     * 
     * @var string
     */
    public $tmpBackendsFile;



    /**
     * Path to the user defined schema
     * called schema.json
     * 
     * @var string
     */
    public $schemaFile;



    /**
     * Path to the final generated
     * haproxy.cfg config file
     * 
     * @var string
     */
    public $configFile;



    /**
     * Place to store parsed user schema
     * after parsing it
     * 
     * @var array
     */
    public $parsedSchema = null;



    /**
     * Set initial values to several variables
     * 
     * @return void
     */
    public function __Construct()
    {
        # Where are the templates
        $this->templateFrontendsHttpFile = "/root/templates/haproxy.http.cfg";
        $this->templateGlobalFile = "/root/templates/haproxy.global.cfg";
        $this->templateDefaultsFile = "/root/templates/haproxy.defaults.cfg";

        # Where to store temporary files
        $this->tmpFrontendsTcpFile = "/tmp/schema_frontends_tcp.cfg";
        $this->tmpFrontendsHttpFile = "/tmp/schema_frontends_http.cfg";
        $this->tmpBackendsFile = "/tmp/schema_backends.cfg";

        # Where is schema.json define by the user
        $this->schemaFile = "/root/definition/schema.json";

        # Where to store Haproxy generated config file
        $this->configFile = "/tmp/haproxy.cfg";
    }



    /**
     * This class configures the log class
     * to store in another file
     * 
     * @return void
     */
    public static function Log ( string $msg ) 
    {
        $log = new LogController('SchemaController.log');
        $log->Log($msg);
    } 



    /**
     * Open the schema.json file and check the format of
     * the fields. After that, save parsed array into
     * $this->userschema variable.
     * 
     * @return bool
     */
    public function Parse () : bool
    {
        try {

            if( !file_exists($this->schemaFile) ){
                throw new Exception (__METHOD__ . " says: schema.json file not found");
            }

            $schema = file_get_contents($this->schemaFile);

            # Parse and check schema file
            $schema = json_decode($schema, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception (__METHOD__ . " says: schema.json file is not a valid JSON");
            }

            # Check the format of each credential
            foreach ($schema as $index => $value ){
                if( !array_key_exists('servers', $value) || !is_array($value['servers']) ){
                    throw new Exception (__METHOD__ . " says: servers array malformed");
                }
                foreach ( $value['servers'] as $server ){
                    if( !preg_match('/^(.*)([:]([0-9]{1,5}))$/', $server, $servers) ){
                        throw new Exception (__METHOD__ . " says: servers port are mandatory");
                    }

                    if ( !filter_var( $servers[1], FILTER_VALIDATE_IP) ){
                        throw new Exception (__METHOD__ . " says: servers need an IPv4 or IPv6 to work");
                    }
                }
                if( !array_key_exists ('balance', $value ) || !preg_match('/(\broundrobin\b|\banother\b)/', $value['balance']) ){
                    throw new Exception (__METHOD__ . " says: balance malformed");
                }

                # With these parameters we only check existance
                # because they need a special check flow
                if( array_key_exists ('domain', $value ) && !preg_match('/\A([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}\Z/', $value['domain'], $domain) ){
                    throw new Exception (__METHOD__ . " says: domain malformed");
                }
                if( array_key_exists ('bind', $value ) && !preg_match('/^([0-9]{1,5})$/', $value['bind']) ){
                    throw new Exception (__METHOD__ . " says: bind malformed");
                }
                if( array_key_exists ('bind', $value ) && preg_match('/(\b443\b|\b80\b)/', $value['bind']) ){
                    throw new Exception (__METHOD__ . " says: binding port 80/443 not allowed. They are reserved for domains");
                }

                # Domain frontends can only work with 'https' mode 
                # and can not be binded to any port
                if( 
                    array_key_exists ('domain', $value ) 
                    && array_key_exists ('bind', $value )
                ){
                    throw new Exception (__METHOD__ . " says: domain frontends can not be binded");
                }

                # IP frontends can only work with 'tcp' mode
                # and need to be binded to a port
                if( 
                    !array_key_exists ('domain', $value ) 
                    && !array_key_exists('bind', $value )
                ){
                    throw new Exception (__METHOD__ . " says: IP frontends need to be binded");
                }
            }

            # Everything is OK with schema.json
            $this->parsedSchema = $schema;
            return true;

        } catch ( Exception $e ) {
            self::Log( $e->getMessage() );
            return false;
        }
    }



    /**
     * Write parsed user schema into temporary 
     * files on /tmp
     * 
     * @return bool
     */
    private function BuildTempFiles () : bool
    {
        try {
            if( empty($this->parsedSchema) ){
                throw new Exception (__METHOD__ . "says: schema.json is not parsed yet");
            }

            # Delete the traces of temporary files
            @unlink($this->tmpBackendsFile);
            @unlink($this->tmpFrontendsTcpFile);
            @unlink($this->tmpFrontendsHttpFile);

            # Create temporary files
            if( 
                !touch ($this->tmpBackendsFile) 
                || !touch ($this->tmpFrontendsTcpFile) 
                || !touch ($this->tmpFrontendsHttpFile) 
            ){
                throw new Exception (__METHOD__ . " says: temporary files could not be created");
            }


            # Throw static default template for 'http' to temporary files
            $httpTemplate  = file_get_contents($this->templateFrontendsHttpFile);
            $httpTemplate .= PHP_EOL;

            if( !file_put_contents ($this->tmpFrontendsHttpFile, $httpTemplate, FILE_APPEND) ){
                throw new Exception (__METHOD__ . " says: http frontends temporary file could not be written");
            }

            # Throw dynamic information to temporary files
            foreach ( $this->parsedSchema as $index => $item ) {

                # Build a random name for the snippet
                mt_srand ( time() + rand() );
                $name = mt_rand();

                # Build a backend snippet
                {
                    $backendData  = 'backend '.$name . '_backend' . PHP_EOL;
                    $backendData .= '  option forwardfor' . PHP_EOL;
                    $backendData .= '  balance ' . $item['balance'] . PHP_EOL;
                    foreach ( $item['servers'] as $index => $server ){
                        $backendData .= '  server ' . $name . '_server_'.$index. ' ' . $server . PHP_EOL;
                    }

                    # Write the snippet at the final of the file
                    if( !file_put_contents ($this->tmpBackendsFile, $backendData, FILE_APPEND) ){
                        throw new Exception (__METHOD__ . " says: backends temporary file could not be written");
                    }
                }

                # Build a frontend snippet for mode 'tcp'
                {              
                    if ( array_key_exists ('bind', $item ) ){
                        $frontendData  = 'frontend '.$name . '_frontend' . PHP_EOL;
                        $frontendData .= '  bind *:' . $item['bind'] . PHP_EOL;
                        $frontendData .= '  mode tcp' . PHP_EOL;
                        $frontendData .= '  use_backend ' . $name . '_backend' . PHP_EOL;
    
                        if( !file_put_contents ($this->tmpFrontendsTcpFile, $frontendData, FILE_APPEND) ){
                            throw new Exception (__METHOD__ . " says: tcp frontend temporary file could not be written");
                        }
                    }
                }
                
                # Build a frontend snippet for mode 'http'
                {
                    if ( array_key_exists ('domain', $item ) ){
                        $frontendData  = '  acl '.$name.'_host hdr(host) -i '.$item['domain'] . PHP_EOL;
                        $frontendData .= '  use_backend '.$name.'_backend if '.$name.'_host' . PHP_EOL . PHP_EOL;
    
                        if( !file_put_contents ($this->tmpFrontendsHttpFile, $frontendData, FILE_APPEND) ){
                            throw new Exception (__METHOD__ . " says: http frontend temporary file could not be written");
                        }
                    }
                }
            }

            return true;

        } catch ( Exception $e ) {
            self::Log( $e->getMessage() );
            return false;
        }
    }



    /**
     * Join temporary files into a config file
     * 
     * @return bool
     */
    public function BuildConfigFile () : bool
    {
        try {
            # Try to craft temporary config files
            if ( ! $this->BuildTempFiles() ) {
                throw new Exception (__METHOD__ . "says: error generating temporary config files");
            }

            # Check if we have temporary files to join
            if( 
                !file_exists($this->tmpBackendsFile) 
                || !file_exists($this->tmpFrontendsTcpFile)
                || !file_exists($this->tmpFrontendsHttpFile)
            ){
                throw new Exception (__METHOD__ . " says: temporary config files are not generated yet");
            }

            # Delete and recreate the haproxy.cfg config file on /temp
            @unlink($this->configFile);

            if( !touch ($this->configFile) ){
                throw new Exception (__METHOD__ . " says: final config file could not be generated");
            }

            # Join 'global' section
            $copy = file_put_contents(
                $this->configFile, 
                file_get_contents($this->templateGlobalFile) . PHP_EOL, 
                FILE_APPEND
            );
            if( ! $copy ){
                throw new Exception (__METHOD__ . " says: impossible to copy 'global' section into haproxy config file");
            }

            # Join 'defaults' section
            $copy = file_put_contents(
                $this->configFile, 
                file_get_contents($this->templateDefaultsFile) . PHP_EOL, 
                FILE_APPEND
            );
            if( ! $copy ){
                throw new Exception (__METHOD__ . " says: impossible to copy 'defaults' section into haproxy config file");
            }

            # Join 'backends' section
            $copy = file_put_contents(
                $this->configFile, 
                file_get_contents($this->tmpBackendsFile) . PHP_EOL, 
                FILE_APPEND
            );
            if( ! $copy ){
                throw new Exception (__METHOD__ . " says: impossible to copy 'backends' section into haproxy config file");
            }

            # Join 'frontends' tcp section
            $copy = file_put_contents(
                $this->configFile, 
                file_get_contents($this->tmpFrontendsTcpFile) . PHP_EOL, 
                FILE_APPEND
            );
            if( ! $copy ){
                throw new Exception (__METHOD__ . " says: impossible to copy 'frontends' tcp section into haproxy config file");
            }

            # Join 'frontends' http section
            $copy = file_put_contents(
                $this->configFile, 
                file_get_contents($this->tmpFrontendsHttpFile) . PHP_EOL, 
                FILE_APPEND
            );
            if( ! $copy ){
                throw new Exception (__METHOD__ . " says: impossible to copy 'frontends' http section into haproxy config file");
            }

            return true;

        } catch ( Exception $e ) {
            self::Log( $e->getMessage() );
            return false;
        }
    }
}