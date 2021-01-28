<?php
/**
 * Script that join several parts of Let's Encrypt
 * certificates into one PEM file for Haproxy
 */



# Configuring paths
$certbotCertsDir = '/etc/letsencrypt/live';
$haproxyCertsDir = '/etc/letsencrypt/haproxy';



try {

    # Create dir for joined certs
    if( !file_exists($haproxyCertsDir) ){
        mkdir($haproxyCertsDir, 0777, true);
    }

    # Loop over all live certs
    $domains = scandir($certbotCertsDir);
    foreach ($domains as $domain) {
        # Craft the real path
        $thisCertDir = $certbotCertsDir . '/' . $domain;

        # Check for strange things
        if ( $domain == '.' || $domain == '..' || !is_dir($thisCertDir) ) 
            continue;
        
        # Check for PEM pieces
        if ( 
            !file_exists($thisCertDir.'/fullchain.pem') 
            || !file_exists($thisCertDir.'/privkey.pem') 
        ){
            throw new Exception ("certificate for domain '".$domain."' is malformed");
        }

        # Join the pieces
        $content  = file_get_contents($thisCertDir.'/fullchain.pem').PHP_EOL;
        $content .= file_get_contents($thisCertDir.'/privkey.pem').PHP_EOL;

        if( !file_put_contents($haproxyCertsDir.'/'.$domain.'.pem', $content) ){
            throw new Exception ("impossible to build haproxy-ready certificate for domain '".$domain."'");
        }
    }

    exit(0);

} catch ( Exception $e ) {
    echo $e->getMessage().PHP_EOL;
    exit(1);
}