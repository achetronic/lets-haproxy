<?php

/**
 * Script that configures Haproxy into 'certbot' mode,
 * renew certificates and parse them again. 
 * 
 * After that, it rises the Haproxy on 'regular' mode again
 */

try {

    # Start Haproxy on Certbot mode
    echo "Reconfiguring Haproxy as a proxy for Certbot" . PHP_EOL;
    $cmd = shell_exec('php change-mode.php -m certbot');

    # Try to create certificates
    echo "Certbot renewing certificates" . PHP_EOL;
    $cmd = shell_exec('certbot renew -n --http-01-port 8080');

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