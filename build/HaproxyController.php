<?php

include_once("LogController.php");
include_once("SchemaController.php");

/**
 * This class has all needed functions
 * to 
 * 
 * 
 */
class HaproxyController 
{
    /**
     * Path to the haproxy.cfg
     * file suitable for certbot
     * 
     * @var string
     */
    public $templateCertbotFile;



    /**
     * Path to haproxy.cfg
     * 
     * @var string
     */
    public $configFile;



    /**
     * 
     * 
     * 
     */
    public function __Construct () 
    {
        $this->templateCertbotFile = "/root/templates/haproxy.certbot.cfg";
        $this->configFile = "/etc/haproxy/haproxy.cfg";
    }



    /**
     * This class configures the log class
     * to store in another file
     * 
     * @return void
     */
    public static function Log ( string $msg ) 
    {
        $log = new LogController('HaproxyController.log');
        $log->Log($msg);
    } 



    /**
     * Restart Haproxy service
     * 
     * @return void
     */
    public static function Restart ( ) 
    {
        # Restart Haproxy
        $cmd = shell_exec('service haproxy stop');
        $cmd = shell_exec('service haproxy start');
    } 



    /**
     * Stop Haproxy service
     * 
     * @return void
     */
    public static function Stop ( ) 
    {
        $cmd = shell_exec('service haproxy stop');
    } 



    /**
     * Stop Haproxy service
     * 
     * @return void
     */
    public static function Start ( ) 
    {
        $cmd = shell_exec('service haproxy start');
    } 

    

    /**
     * Reconfigure Haproxy to 
     * work as a production proxy
     * 
     * @return bool
     */
    public function SetRegularConfig () : bool
    {
        try {
            $schema = new SchemaController;

            if( ! $schema->Parse() ){
                throw new Exception (__METHOD__ . " says: schema.json is malformed");
            }
            
            if( ! $schema->BuildConfigFile() ){
                throw new Exception (__METHOD__ . " says: config file not created");
            }

            if( !copy($schema->configFile, $this->configFile) ) {
                throw new Exception (__METHOD__ . " says: impossible to move ".$schema->configFile." to destination");
            }

            return true;

        } catch ( Exception $e ) {
            self::Log( $e->getMessage() );
            return false;
        }
    }



    /**
     * Reconfigure Haproxy to
     * run just as certbot proxy
     * 
     * @return bool
     */
    public function SetCertbotConfig () : bool
    {
        try {

            if( !copy($this->templateCertbotFile, $this->configFile) ) {
                throw new Exception (__METHOD__ . " says: impossible to move ".$this->templateCertbotFile." to destination");
            }

            return true;

        } catch ( Exception $e ) {
            self::Log( $e->getMessage() );
            return false;
        }
    }
}


/**
 * 
 * 
 */


