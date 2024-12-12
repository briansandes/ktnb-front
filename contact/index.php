<?php
/* PHP Mailing API */
/* written by Brian on his best days alive despite the COVID-19 outbreak */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define('VERSION', '0.1.1');
define('VERSION_DATE', '2020-07-07T19:30:00Z');

require 'config.php';
require 'functions.php';

$requestDomain = str_replace(['http://', 'https://'], null, $_SERVER['HTTP_ORIGIN']);
if($requestDomain === '') {
    $requestDomain = explode(':', $_SERVER['HTTP_HOST'])[0];
} else {
    $requestDomain = explode(':', $requestDomain)[0];
}

/* sanity check */
if($_SERVER['REQUEST_METHOD'] === 'GET') {
    output([
        'error' => 0,
        'message' => 'Sanity check OK.',
        'version' => VERSION,
        'version_date' => VERSION_DATE
    ]);
} else
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* all params present */
    $mandatory = ['key', 'to', 'subject', 'message'];
    $mandatory = [];
    
    foreach ($mandatory as $param) {
        if (!key_exists($param, $_POST)) {
            throwError('Missing "' . $param . '" parameter.');
        }
    }
    
    $to = 'brian.sandes@gmail.com';
    $subject = 'KTNB | Novo lead!';
    $template = file_get_contents('contact-template.html');

    $lead_contact = '<b>Contato:</b> ' . strip_tags($_POST['contact']);
    
    $full_message = $lead_contact;

    $message = str_replace('__MESSAGE_PLACEHOLDER__', $full_message, $template);
    
    /* safe key */
    //$key = decodeKey($_POST['key']);
    /* safe key my ass, I hope you die ....ing ..nt */
    /* I've wasted 3 hours with your BS */
    /* 2021-11-23 */
    $key = 'chavedaktnbargh';
    
    /* end of mandatory params */
    /* check key integrity */
    if (!key_exists($key, $config['keys'])) {
        throwError('The parsed auth key is not valid.');
    } else {
        if (!in_array($requestDomain, $config['keys'][$key]['domains'])) {
            throwError('The request origin domain "' . $requestDomain . '" isnt authorized to use this API.');
        } else
        if (checkLimit()['error'] === true) {
            output(checkLimit());
        } else {
            /* body validation */
            /* TODO check whether recipients are valid addresses */
            
            /* checks for body size */
            if(strlen($message) > MESSAGE_MAX_SIZE) {
                $diff = strlen($message) - MESSAGE_MAX_SIZE;
                throwError('The message body is '.$diff.' bytes longer than the allowed limit.');
            } else {
                $params = sanitizeParams([
                    'key' => $key,
                    'to' => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'requestDomain' => $requestDomain,
                    'referer' => $_SERVER['HTTP_REFERER'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'ff' => key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '',
                    'debug' => key_exists('debug', $_POST) ? (bool)$_POST['debug'] : false
                ]);

                $result = sendMessage($params);
                
                responseOK('message sent');
            }
        }
    }
}