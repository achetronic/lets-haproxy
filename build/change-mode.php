<?php
/**
 * Script that change Haproxy's configuration file 
 * between the CFG for Certbot and CFG for daily use
 */



include_once("Controllers/Haproxy.php");



try {
    
    # Create an instance of Haproxy
    $haproxy = new Haproxy();

    # Take (optional) parameter -m=certbot on CLI
    $options = getopt("m:");

    # Change haproxy.cfg 
    if( array_key_exists('m', $options) && $options['m'] == 'certbot'){
        echo "Reconfiguring Haproxy as a proxy for Certbot" . PHP_EOL;
        $haproxy->SetCertbotConfig ();    
    }else{
        echo "Reconfiguring Haproxy as a regular proxy" . PHP_EOL;
        $haproxy->SetRegularConfig ();
    }

    # Rise the server
    echo "Restarting Haproxy". PHP_EOL;
    $haproxy->Restart();

    exit(0);

} catch ( Exception $e ) {
    echo $e->getMessage().PHP_EOL;
    exit(1);
}

