<?php

namespace Achetronic\LetsHaproxy\Certbot;

use Achetronic\LetsHaproxy\Haproxy\Config;
use Achetronic\LetsHaproxy\Haproxy\Service;
use Achetronic\LetsHaproxy\Console\Command;
use Achetronic\LetsHaproxy\Console\Log;

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
     * Email of certificates administrator
     * who receive the renewal advises
     *
     * @var string
     */
    private static ?string $email = null;

    /**
     * Environment
     *
     * @var string
     */
    private static ?string $environment = null;

    /**
     * Domains to be certificated
     *
     * @var array
     */
    private static array $domains = [];

    /**
     * Configure required email
     * to create Lets Encrypt certificates
     *
     * @return bool
     */
    public static function setEmail (string $email) :bool
    {
        if(!preg_match("/^[a-z0-9+_.-]+@[a-z0-9.-]+$/", $email)){
            Log::error("Failed setting email");
            return false;
        }
        self::$email = $email;
        return true;
    }

    /**
     * Configure required environment
     * to create Lets Encrypt certificates
     *
     * @return bool
     */
    public static function setEnvironment (string $environment) :bool
    {
        if(!in_array($environment, ['staging', 'production'], true)){
            Log::error("Failed setting environment");
            return false;
        }
        self::$environment = $environment;
        return true;
    }

    /**
     * Configure required domains
     * to create Lets Encrypt certificates
     *
     * @return bool
     */
    public static function setDomains () :bool
    {
        Config::parse(Config::USER_TEMPLATE_PATH);
        $domains = Config::getSecureDomains();
        if(empty($domains)){
            Log::error("Failed getting domains. No domains found");
            return false;
        }
        self::$domains = $domains;
        return true;
    }

    /**
     * Configure required parameters
     * to create Lets Encrypt certificates
     *
     * @return bool
     */
    public static function setParameters (string $email, string $environment = 'staging') :bool
    {
        if(!self::setEmail($email))
            return false;

        # Type of environment (staging | production)
        if(!self::setEnvironment($environment))
            return false;

        # Extract domains to be certified
        if(!self::setDomains())
            return false;

        return true;
    }

    /**
     * Join certificate PEM parts
     * into single PEM file per domain
     * for Haproxy
     *
     * @var bool
     */
    public static function mergePems() :bool
    {
        # Dir for joined certs
        if( !file_exists(self::HAPROXY_CERTS_PATH) ){
            mkdir(self::HAPROXY_CERTS_PATH, 0644, true);
        }

        # Loop over all live certs
        $certifiedDomains = scandir(self::CERTBOT_CERTS_PATH);
        foreach ($certifiedDomains as $domain) {

            $domainPath = self::CERTBOT_CERTS_PATH.'/'.$domain;
            if ( $domain == '.' || $domain == '..' || !is_dir($domainPath) )
                continue;

            $fullchainPem  = @file_get_contents($domainPath.'/fullchain.pem');
            $privkeyPem    = @file_get_contents($domainPath.'/privkey.pem');

            if(!$fullchainPem || !$privkeyPem){
                Log::error("Failed finding PEM files for domain ".$domain);
                return false;
            }

            $content = $fullchainPem.PHP_EOL.$privkeyPem.PHP_EOL;
            if( !file_put_contents(self::HAPROXY_CERTS_PATH.'/'.$domain.'.pem', $content) ){
                Log::error("Failed merging PEM files for ".$domain);
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
    public static function createSingles() :bool
    {
        if(empty(self::$email) || empty(self::$environment) || empty(self::$domains)){
            Log::error("Failed checking needed parameters");
            return false;
        }

        $environment = '';
        if(self::$environment === 'staging') $environment='--staging';

        if(!Service::changeMode('certbot')){
            Log::error("Failed changing Haproxy mode to certbot");
            return false;
        }

        foreach ( self::$domains as $domain ){

            $cmd  = [
                'certbot certonly',
                '--standalone',
                '-d '.$domain,
                '-m '.self::$email,
                '--cert-name '.$domain,
                '--agree-tos',
                '--keep-until-expiring',
                '--expand',
                '-n',
                '--quiet',
                '--http-01-port 8080',
                $environment
            ];
            $output = @shell_exec(implode(' ', $cmd));

            $certFiles = @scandir(self::CERTBOT_CERTS_PATH . '/' . $domain);
            if(!$certFiles) $certFiles=[];
            if (@count($certFiles) <= 2){
                Log::error("Failed creating certificates for domain ".$domain);
                return false;
            }
        }
        if(!Service::changeMode()){
            Log::error("Failed changing Haproxy mode to proxy");
            return false;
        }
        return true;
    }

    /**
     *
     *
     * @var bool
     */
    public function createMixed() : bool
    {
        // TODO: Future issue :)
        return true;
    }

    /**
     * Renew certificates
     *
     * @var bool
     */
    public static function renewAll() :bool
    {
        if(!Service::changeMode("certbot")){
            Log::error("Failed changing Haproxy mode to certbot");
            return false;
        }

        # Renew certificates
        $output = shell_exec('certbot renew --non-interactive --http-01-port 8080');

        if(empty($output)){
            Log::error("Failed executing certificates renewal");
            return false;
        }

        if(!Service::changeMode()){
            Log::error("Failed changing Haproxy mode to proxy");
            return false;
        }

        return true;
    }

    /**
     * Delete all certificates
     *
     * @var bool
     */
    public static function deleteAll() :bool
    {
        $output = shell_exec("certbot delete");
        if(empty($output)){
            Log::error("Failed executing certificates deletion");
            return false;
        }
        return true;
    }

    /**
     * Delete specified certificate
     *
     * @var bool
     */
    public static function delete(string $name) :bool
    {
        $output = shell_exec("certbot delete --non-interactive --cert-name ".$name);
        if(empty($output)){
            Log::error("Failed executing certificate deletion");
            return false;
        }
        return true;
    }
}
