<?php
/**
 * Main flow script to obtain (or not) the 
 * new certificates for Haproxy
 */


#
try {

    echo "Checking environment vars" . PHP_EOL;
    # User want to skip this creation?
    $skip = getenv('SKIP_CREATION');
    if( $skip != "false" ){
        throw new Exception ("creation was skipped");
    }

    # Create new certs
    echo "Generating new certs" . PHP_EOL;
    $cmd = shell_exec('php create-certs.php');

    # Parse certs for Haproxy
    echo "Parsing certs for Haproxy" . PHP_EOL;
    $cmd = shell_exec('php join-certs.php');

    # Start Haproxy on regular mode
    echo "Reconfiguring Haproxy as a regular proxy" . PHP_EOL;
    $cmd = shell_exec('php change-mode.php');

    exit(0);

} catch ( Exception $e ) {

    # Start Haproxy on regular mode
    echo "Reconfiguring Haproxy as a regular proxy" . PHP_EOL;
    $cmd = shell_exec('php change-mode.php');

    echo $e->getMessage().PHP_EOL;
    exit(1);
}