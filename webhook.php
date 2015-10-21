<?php

try {

    $config = require 'src/bootstrap.php';

    $webhookapp = new Webhookapp($config);
    $webhookapp->bootstrap();

}catch (\Exception $ex){

    if($webhook instanceof Webhook){
        $webhook->handleException($ex);
    }else{
        if(class_exists("\\DreamCommerce\\Logger")) {
            \DreamCommerce\Logger::error($ex);
        }else{
            die($ex->getMessage());
        }
    }

}