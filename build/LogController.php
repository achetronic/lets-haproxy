<?php

/**
 * This class has all needed functions
 * to 
 * 
 * 
 */
class LogController 
{
    /**
     * The path to the logfiles folder
     * 
     * @var string
     */
    public $logsFolder;



    /**
     * The name of the file
     * where the log is stored
     * 
     * @var string
     */
    public $logFile;



    /**
     * Set initial values to several variables
     * 
     * @return void
     */
    public function __Construct( string $logFile = 'error.log' )
    {
        $this->logsFolder = __DIR__ . "/error";
        if( !file_exists($this->logsFolder)){
            mkdir($this->logsFolder, 0755, true);
        }

        $this->logFile = $logFile;
    }



    /**
     * This class log a message
     * into a file
     * 
     * @return void
     */
    public function Log ( string $msg ) 
    {
        file_put_contents($this->logsFolder . '/' . $this->logFile, "[".time()."] ". $msg . PHP_EOL, FILE_APPEND);
    } 

}