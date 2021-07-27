<?php

namespace Achetronic\LetsHaproxy\Certbot;

use \Achetronic\LetsHaproxy\Haproxy\Config;
use \Achetronic\LetsHaproxy\Haproxy\Service;

final class Certificate
{
    /**
     * Path to directory with
     * certificates processed
     * for Haproxy
     *
     * @var string
     */
    private const HAPROXY_CERTS_PATH = '/etc/letsencrypt/haproxy';

    /**
     * Path to directory where Certbot
     * creates certificates
     *
     * @var string
     */
    private const CERTBOT_CERTS_PATH = '/etc/letsencrypt/live';

    /**
     *
     *
     * @return Certificate
     */
    public function __construct (string $email, string $environment = '--staging')
    {
        $this->email = $email;

        # Type of environment (staging | production)
        $this->environment = $environment;
        if($environment == 'production'){
            (string)$this->environment=null;
        }

        # Extract domains to be certified
        Config::parse(Config::USER_TEMPLATE_PATH);
        $this->domains = Config::getSecureDomains();
    }

    /**
     * Join certificate PEM parts
     * into single PEM file per domain
     * for Haproxy
     *
     * @var bool
     */
    public function join() :bool
    {
        # Dir for joined certs
        if( !file_exists(self::HAPROXY_CERTS_PATH) ){
            mkdir(self::HAPROXY_CERTS_PATH, 0644, true);
        }

        # Loop over all live certs
        $certifiedDomains = scandir(self::CERTBOT_CERTS_PATH);
        foreach ($certifiedDomains as $domain) {

            $domainPath = $certsPath.'/'.$domain;
            if ( $domain == '.' || $domain == '..' || !is_dir($domainPath) )
                continue;

            $fullchainPem  = @file_get_contents($domainPath.'/fullchain.pem');
            $privkeyPem    = @file_get_contents($domainPath.'/privkey.pem');

            if(!$fullchainPem || !$privkeyPem){
                echo "Failed finding PEM files for domain '".$domain;
                return false;
            }

            $content = $fullchainPem.PHP_EOL.$privkeyPem.PHP_EOL;
            if( !file_put_contents(self::HAPROXY_CERTS_PATH.'/'.$domain.'.pem', $content) ){
                echo "Failed joining PEM files for ". $domain;
                return false;
            }
        }
        return true;
    }

    /**
     * Create one certificate per domain
     * from Let's Encrypt
     *
     * @var void
     */
    public function createSingleCerts() :bool
    {
        if(!Service::changeMode('certbot')){
            echo 'Failed changing Haproxy mode to certbot';
            return false;
        }

        foreach ( $this->domains as $domain ){

            @shell_exec('certbot certonly
              --standalone
              --domains '.$domain.'
              --email '.$this->email.'
              --cert-name '.$domain.'
              --agree-tos
              --keep-until-expiring
              --expand
              --non-interactive
              --http-01-port 8080 '.
              $this->environment
            );

            if (@count(scandir(self::CERTBOT_CERTS_PATH . '/' . $domain)) <= 2){
                echo 'Failed creating certificates for domain '.$domain;
                return false;
            }
        }
        if(!Service::changeMode()){
            echo 'Failed changing Haproxy mode to proxy';
            return false;
        }
        return true;
    }

    /**
     * Renew certificates
     *
     * @var bool
     */
    public function renewAll() :bool
    {
        if(!Service::changeMode("certbot")){
            echo 'Failed changing Haproxy mode to certbot';
            return false;
        }

        # Renew certificates
        $output = shell_exec('certbot renew
            --non-interactive
            --http-01-port 8080'
        );

        if(empty($output)){
            echo 'Failed executing certificates renewal';
            return false;
        }

        if(!Service::changeMode()){
            echo 'Failed changing Haproxy mode to proxy';
            return false;
        }

        return true;
    }

    /**
     * Delete all certificates
     *
     * @var bool
     */
    public function deleteAll() :bool
    {
        $output = shell_exec("certbot delete");
        if(empty($output)){
            echo 'Failed executing certificates deletion';
            return false;
        }
        return true;
    }

    /**
     * Delete specified certificate
     *
     * @var bool
     */
    public function delete(string $name) :bool
    {
        $output = shell_exec("certbot delete
            --non-interactive
            --cert-name ".$name
        );
        if(empty($output)){
            echo 'Failed executing certificate deletion';
            return false;
        }
        return true;
    }
}
