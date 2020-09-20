<?php

include_once("HaproxyController.php");

# Create an instance of HaproxyController
$haproxy = new HaproxyController();

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

# Restart Haproxy
echo "Restarting Haproxy" . PHP_EOL;
$haproxy->Restart();
