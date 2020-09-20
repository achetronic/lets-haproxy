<?php

include_once("Controllers/HaproxyController.php");

$certsPath = '/etc/letsencrypt/live';

try {
    # Get the skip flag from ENV vars
    $skip = getenv('SKIP_CREATION');
    if( empty($skip) || $skip == true ){
        throw new Exception ("creation was skipped");
    }

    # Create an instance of SchemaController
    $schema = new SchemaController();

    # Parse the schema.json file to take domains
    if( ! $schema->Parse() ){
        throw new Exception ("schema.json file is malformed");
    }

    # Loop over parsed information looking for domains
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

    # Check both fields
    if ( empty($domains) || empty($email) ){
        throw new Exception ("domains or email are empty");
    }

    # Try to create certificates
    $cmd = shell_exec('certbot certonly --standalone -d '.$domainsTogether.' -m '.$email.' --agree-tos --expand -n --http-01-port 8080 --dry-run');

    # Check if certs where created on /etc/letsencrypt/live
    foreach ( $domains as $domain ) {
        if ( 
            !file_exists($certsPath . '/' . $domain) 
            || count(scan_dir($certsPath . '/' . $domain)) <= 2 
        ){
            throw new Exception ("certificate for domain '".$domain."' was not created");
        }
    }

    exit(0);

} catch ( Exception $e ) {
    echo $e->getMessage();
    exit(1);
}