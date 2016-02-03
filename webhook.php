<?php

chdir(__DIR__);
try {

    $config = require 'src/bootstrap.php';

    $webhookapp = new Webhookapp($config);
    $webhookapp->bootstrap();

}catch (\Exception $ex){

    if($webhook instanceof Webhook){
        $webhook->handleException($ex);
    }else{
        if(class_exists("\\DreamCommerce\\Logger")) {
            $logger = new \DreamCommerce\Logger;
            $logger->error('Message: ' . $ex->getMessage() . '; code: ' . $ex->getCode() . '; stack trace: ' . $ex->getTraceAsString());
        }else{
            die($ex->getMessage());
        }
    }

}
