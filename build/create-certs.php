<?php
/**
 * Script that configures Haproxy to obtain Let's Encrypt 
 * certificates turning it into 'certbot' mode.
 * 
 * It gets the info from a file that user set as input. 
 * 
 * Moreover, the script parse certificates for Haproxy and 
 * rise the server on 'regular' mode after getting the certs
 */
include_once("controllers/Haproxy.php");



# Configuring paths
$certbotCertsDir = '/etc/letsencrypt/live';



#
try {

    echo "Checking environment vars" . PHP_EOL;

    # Get the email from ENV vars
    $email = getenv('ADMIN_MAIL');
    if ( empty($email) ){
        throw new Exception ("email can not be empty");
    }

    # Type of environment (staging | production)
    $staging = '--staging';
    $environment = getenv('ENVIRONMENT');
    if( $environment == "production" ){
        $staging = '';
    }

    # Open a parser for our config file
    echo "Parsing user haproxy.cfg file " . PHP_EOL;
    $haproxy = new Haproxy();
    $haproxy->ParseUserTemplate();

    # Start Haproxy on Certbot mode
    echo "Reconfiguring Haproxy as a proxy for Certbot" . PHP_EOL;
    $cmd = shell_exec('php change-mode.php -m certbot');

    # Delete all certificates
    echo "Deleting old certificates from Certbot" . PHP_EOL;
    $cmd = shell_exec("printf '\n' | certbot delete");

    # Try to get certificates
    echo "Getting new certificates from Certbot" . PHP_EOL;
    foreach ( $haproxy->domainsToCert as $domain ){

        # Ask Certbot for them
        $cmd = shell_exec('certbot certonly --standalone -d '.$domain.' -m '.$email.' --cert-name '.$domain.' --agree-tos --expand -n --http-01-port 8080 '.$staging);

        # Check the certificate existance on /etc/letsencrypt/live
        if ( 
            !file_exists($certbotCertsDir . '/' . $domain) 
            || count(scandir($certbotCertsDir . '/' . $domain)) <= 2 
        ){
            throw new Exception ("certificate for domain '".$domain."' was not created");
        }
    }

    exit(0);

} catch ( Exception $e ) {
    echo $e->getMessage().PHP_EOL;
    exit(1);
}