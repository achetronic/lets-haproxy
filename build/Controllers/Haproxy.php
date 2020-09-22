<?php
/**
 * Wrapper with functions to manage Haproxy automation for 
 * getting Let's Encrypt certificates with PHP scripts
 */

require __DIR__ . '/../vendor/autoload.php';

use Jalle19\HaPHProxy\Configuration;
use Jalle19\HaPHProxy\Parser;
use Jalle19\HaPHProxy\Writer;
use Jalle19\HaPHProxy\Parameter\Parameter;



/**
 * This class has all needed functions
 * to 
 * 
 * 
 */
class Haproxy 
{
    /**
     * Path to the haproxy.cfg
     * file suitable for certbot
     * 
     * @var string
     */
    public $templateCertbotFile = null;



    /**
     * Path to the user's haproxy.cfg
     * file to parse
     * 
     * @var string
     */
    public $templateUserFile = null;



    /**
     * Path to the haproxy.cfg file
     * 
     * @var string
     */
    public $configFile = null;



    /**
     * List of domains to get
     * certs for
     * 
     * @var array
     */
    public $domainsToCert = null;



    /**
     * 
     * 
     * 
     */
    public function __Construct () 
    {
        $this->templateCertbotFile = "/root/templates/haproxy.certbot.cfg";
        $this->templateUserFile    = "/root/templates/haproxy.user.cfg";
        $this->configFile          = "/etc/haproxy/haproxy.cfg";
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
     * run just as certbot proxy
     * 
     * @return bool
     */
    public function SetCertbotConfig () : bool
    {
        try {

            if( !copy($this->templateCertbotFile, $this->configFile) ) {
                throw new Exception ("impossible to move ".$this->templateCertbotFile." to destination");
            }

            return true;

        } catch ( Exception $e ) {
            return false;
        }
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

            if( ! $this->ParseUserTemplate () ) {
                throw new Exception ("impossible to parse ".$this->templateUserFile);
            }

            if( !copy('/tmp/haproxy.cfg', $this->configFile) ) {
                throw new Exception ("impossible to move /tmp/haproxy.cfg to destination");
            }
            
            return true;

        } catch ( Exception $e ) {
            return false;
        }
    }



    /**
     * Reconfigure Haproxy to
     * run just as certbot proxy
     * 
     * @return bool
     */
    public function ParseUserTemplate () : bool
    {
        try {

            $parser = new Parser($this->templateUserFile);

            # Parse the configuration
            $configuration = $parser->parse();

            # Store https (443 binded) frontend into a var
            $httpsFrontend = null;

            # Loop over all the frontends looking for one binded to 443
            foreach ( $configuration->getFrontendSections() as $index => &$frontendSection) {

                # Look for bindings
                $bindValue = $frontendSection->getParameterByName('bind')->getValue();

                # Get only port numbers
                $hasPort = preg_match('/([0-9]{1,5}){1}/', $bindValue, $parsedPort);
                $parsedPort = $parsedPort[0];

                # Get only frontend with ports 443
                if( !empty($parsedPort) && intval($parsedPort) === 443 ){
                    $httpsFrontend = $frontendSection;
                    unset($frontendSection);
                }
            }

            # Check for saving https section successfully
            if ( empty($httpsFrontend) ) {
                throw new Exception();
            }

            # Loop over parameters looking for domains and changing parameters
            $domainsToCert = [];
            foreach ( $httpsFrontend->getParameters() as $index => &$item) {

                # Get the parameter
                $parameterName  = $item->getName();
                $parameterValue = $item->getValue();

                # Touch bind parameter to add certificates
                if ( $item->getName() == 'bind' ){
                    $item->setValue('*:443 ssl crt /etc/letsencrypt/haproxy/');
                }

                # Look into ACL for domains
                if ( $item->getName() == 'acl' ){

                    # Extract the domain
                    $hasDomain = preg_match('/(-i\s+){1}(([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,})/', $item->getValue(), $parsedDomain);

                    # Add the domain to the list
                    if( $hasDomain ){
                        $domainsToCert[] = $parsedDomain[2];
                    }
                }
            }

            # Add modified section to the config
            $configuration->addSection( $httpsFrontend );

            # Dump the config into a temporary file and check it
            @unlink('/tmp/haproxy.cfg');
            $writer = new Writer($configuration);
            if( !file_put_contents('/tmp/haproxy.cfg', $writer->dump()) ){
                throw new Exception('temporary file could not be stored');
            }

            # Save domains to get certs
            $this->domainsToCert = $domainsToCert;

            return true;

        } catch ( Exception $e ) {
            return false;
        }
    }




}