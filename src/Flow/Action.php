<?php

namespace Achetronic\LetsHaproxy\Flow;

use \Achetronic\LetsHaproxy\Haproxy\Config;
use \Achetronic\LetsHaproxy\Haproxy\Service;

final class Action
{
    /**
     *
     *
     *
     */
    public function __construct () // TODO: REFACTOR THIS HELL
    {
        $this->tmpConfigPath       = "/tmp/haproxy.cfg";
        $this->configPath          = "/etc/haproxy/haproxy.cfg";
        $this->templateCertbotFile = "/root/templates/haproxy.certbot.cfg";
        $this->templateUserFile    = "/root/templates/haproxy.user.cfg";

        $this->certsPath           = '/etc/letsencrypt/live';
        $this->proxyCertsPath      = '/etc/letsencrypt/haproxy';

        # Get the email from ENV vars
        $this->email = getenv('ADMIN_MAIL');
        if ( empty($this->email) ){}

        # Type of environment (staging | production)
        $this->environment = '--staging';
        $environment = getenv('ENVIRONMENT');
        if( $environment == "production" ){
            $this->environment = '--production';
        }

        # Parse user-given configuration
        $this->userConfig = new Config();
        $this->userConfig->parse($this->templateUserFile);
        $this->userConfig->prepare();

        # Extract domains to be certified
        $this->domains = $userConfig->getSecureDomains();
    }

    /**
     * Change Haproxy configuration to
     * handle Certbot flow
     *
     * @var bool
     */
    public function setCertbotConfig() :bool
    {
        if( !copy($this->templateCertbotFile, $this->configPath) )
            return false;
        return true;
    }

    /**
     * Change Haproxy configuration to
     * handle user requests
     *
     * @var bool
     */
    public function setRegularConfig() :bool
    {
        if(!$this->userConfig->store($this->tmpConfigPath))
            return false;
        return true;
    }

    /**
     * Change mode between certification flow
     * or production
     *
     * @var bool
     */
    public function changeMode(?string $mode=null) :bool
    {
        if($mode==="certbot"){
            if(!$this->setCertbotConfig())
                return false;
        }

        if(!$this->setRegularConfig())
            return false;

        Service::restart();
        return true;
    }

    /**
     * Join certificates for Haproxy
     *
     * @var bool
     */
    public function joinCerts() :bool
    {
        # Dir for joined certs
        if( !file_exists($this->proxyCertsPath) ){
            mkdir($this->proxyCertsPath, 0644, true);
        }

        # Loop over all live certs
        $certifiedDomains = scandir($this->certsPath);
        foreach ($certifiedDomains as $domain) {

            $domainPath = $certsPath.'/'.$domain;
            if ( $domain == '.' || $domain == '..' || !is_dir($domainPath) )
                continue;

            # Join the pieces
            $fullchainPem  = @file_get_contents($domainPath.'/fullchain.pem');
            $privkeyPem    = @file_get_contents($domainPath.'/privkey.pem');

            if(!$fullchainPem || !$privkeyPem){
                echo "Failed finding PEM files for domain '".$domain;
                return false;
            }

            $content = $fullchainPem.PHP_EOL.$privkeyPem.PHP_EOL;
            if( !file_put_contents($this->proxyCertsPath.'/'.$domain.'.pem', $content) ){
                echo "Failed joining PEM files for ". $domain;
                return false;
            }
        }
        return true;
    }

    /**
     * Create Let's Encrypt certificates
     *
     * @var void
     */
    public function createCerts() :void
    {
        $this->changeMode("certbot");
        $this->deleteCerts();

        # Get certificates
        foreach ( $this->domains as $domain ){

            shell_exec('certbot certonly
              --standalone
              -d '.$domain.'
              -m '.$this->email.'
              --cert-name '.$domain.'
              --agree-tos
              --expand
              --non-interactive
              --http-01-port 8080 '.$this->environment
            );

            if (
                !file_exists($this->certsPath . '/' . $domain)
                || count(scandir($this->certsPath . '/' . $domain)) <= 2
            ){
                echo "Failed creating certificates for domain '".$domain;
            }
        }

        $this->joinCerts();
        $this->changeMode("proxy");
    }

    /**
     * Renew Let's Encrypt certificates
     *
     * @var bool
     */
    public function renewCerts() :bool
    {
        # Start Haproxy on Certbot mode
        if(!$this->changeMode("certbot")){
            echo "information";
            return false;
        }

        # Renew certificates
        shell_exec('certbot renew -n --http-01-port 8080');

        # Prepare certs for Haproxy
        if(!$this->joinCerts()){
            echo "information";
            return false;
        }

        # Start Haproxy on proxy mode
        if(!$this->changeMode("proxy")){
            echo "information";
            return false;
        }

        return true;
    }

    /**
     * Delete all Let's Encrypt certificates
     *
     * @var bool
     */
    public function deleteCerts() :bool
    {
        shell_exec("certbot delete");
    }

}