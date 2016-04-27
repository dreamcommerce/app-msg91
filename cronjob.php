<?php

$time_start = microtime(true);
chdir(__DIR__);

$lock = "cron.lock";
if(file_exists($lock) and filemtime($lock) + 86300 > time()){
    exit();
}
touch($lock);

setlocale(LC_ALL, 'en_US');
set_time_limit(0);

try {

    $cron = true;
    $config = require 'src/bootstrap.php';

    $cronApp = new CronApp($config);
    $cronApp->bootstrap();

} catch (\Exception $ex){
    file_put_contents('logs/cron.log', date("Y-m-d H:i:s") . ' ' . $ex->getMessage() . PHP_EOL, FILE_APPEND);
}
@unlink($lock);

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
if ($execution_time > 180) {
    file_put_contents('logs/cron.log', date("Y-m-d H:i:s") . ' Total execution time over 180 seconds: '.$execution_time.' seconds' . PHP_EOL, FILE_APPEND);
}
file_put_contents('logs/cron.log', date("Y-m-d H:i:s") . ' DONE' . PHP_EOL, FILE_APPEND);