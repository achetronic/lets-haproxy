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

include_once("Controllers/HaproxyController.php");

$certsPath = '/etc/letsencrypt/live';

try {

    # Create an instance of SchemaController
    $schema = new SchemaController();

    # User want to skip this creation?
    $skip = getenv('SKIP_CREATION');
    if( $skip != "false" ){
        throw new Exception ("creation was skipped");
    }

    # Start Haproxy on Certbot mode
    echo "Reconfiguring Haproxy as a proxy for Certbot" . PHP_EOL;
    $cmd = shell_exec('php change-mode.php -m certbot');

    # Parse the schema.json file to get domains for Certbot
    if( ! $schema->Parse() ){
        throw new Exception ("schema.json file is malformed");
    }

    # Loop over parsed information looking only for domains
    foreach ( $schema->parsedSchema as $index => $value ){

        # Delete keys that are not domains
        if ( ! array_key_exists('domain', $value) ){
            unset($schema->parsedSchema[$index]);
        }
    }

    # Put all domains together (comma separated)
    $domains = array_column($schema->parsedSchema, 'domain');
    $domainsTogether = implode ( ',' , $domains );

    # Get the email from ENV vars
    $email = getenv('ADMIN_MAIL');

    # Check both fields (mandatory for Certbot)
    if ( empty($domains) || empty($email) ){
        throw new Exception ("domains or email are empty");
    }

    # Try to get certificates
    $cmd = shell_exec('certbot certonly --standalone -d '.$domainsTogether.' -m '.$email.' --agree-tos --expand -n --http-01-port 8080 --staging');

    # Check if certs where created on /etc/letsencrypt/live
    foreach ( $domains as $domain ) {
        if ( 
            !file_exists($certsPath . '/' . $domain) 
            || count(scandir($certsPath . '/' . $domain)) <= 2 
        ){
            throw new Exception ("certificate for domain '".$domain."' was not created");
        }
    }

    # Parse certs for Haproxy
    echo "Parsing certs for Haproxy" . PHP_EOL;
    $cmd = shell_exec('php join-certs.php');

    # Start Haproxy on regular mode
    echo "Reconfiguring Haproxy as a regular proxy" . PHP_EOL;
    $cmd = shell_exec('php change-mode.php');

    exit(0);

} catch ( Exception $e ) {
    echo $e->getMessage().PHP_EOL;
    exit(1);
}