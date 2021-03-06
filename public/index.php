<?php

// Delegate static file requests back to the PHP built-in webserver
use Interop\Container\Exception\ContainerException;

if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
) {
    return false;
}
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
require_once 'config/env_configurator.php';
/**
 * Self-called anonymous function that creates its own scope and keep the global namespace clean.
 */
call_user_func(function () {
    /** @var \Interop\Container\ContainerInterface $container */
    $container = require "config/container.php";
    //init lifecycle token
    $lifeCycleToken = \rollun\logger\LifeCycleToken::generateToken();
    if(apache_request_headers() && array_key_exists("LifeCycleToken", apache_request_headers())) {
        $lifeCycleToken->unserialize(apache_request_headers()["LifeCycleToken"]);
    }
    /** use container method to set service.*/
    $container->setService(\rollun\logger\LifeCycleToken::class, $lifeCycleToken);

    try {
        $logger = $container->get(\Psr\Log\LoggerInterface::class);
    } catch (ContainerException $containerException) {
        $logger = new \rollun\logger\SimpleLogger();
        $logger->error($containerException);
        $container->setService(\Psr\Log\LoggerInterface::class, $logger);
    }



    $logger = $container->get(\Psr\Log\LoggerInterface::class);
    $logger->notice("Test notice. %request_time", ["request_time" => $_SERVER["REQUEST_TIME"]]);
});
