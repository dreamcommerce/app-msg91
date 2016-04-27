<?php

chdir(__DIR__);
try {

    $config = require 'src/bootstrap.php';

    $webhookapp = new Webhookapp($config);
    $webhookapp->bootstrap();

}catch (\Exception $ex){
    @header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    if(class_exists("\\DreamCommerce\\Logger")) {
        $logger = new \DreamCommerce\Logger;
        $logger->error('Message: ' . $ex->getMessage() . '; code: ' . $ex->getCode() . '; stack trace: ' . $ex->getTraceAsString());
    }else{
        die($ex->getMessage());
    }
}
