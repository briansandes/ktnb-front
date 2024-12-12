<?php
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(key_exists('dev_debug', $_POST)) {
        if((bool)$_POST['dev_debug'] === true) {
            define('DEV_DEBUG', true);
        }
    }
}

/* not debugging, default settings, no errors shown */
if(!defined('DEV_DEBUG')) {
    define('DEV_DEBUG', false);
    
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
} else {
    /* debugging, displaying all errors */
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}


/* time set to UTC 0 */
date_default_timezone_set('UTC');


define('DAILY_LIMIT', 200); // max emails per day
define('MESSAGE_COOLDOWN', 60); // seconds
define('USERS_PER_MESSAGE', 4); // max recipients per message
define('MESSAGE_MAX_SIZE', 200000); // around 200kb max size

/* SMTP CREDENTIALS AND SENDER INFO */
define('MAIL_HOST', 'smtp.ktnb.com.br');
define('MAIL_PORT', 587);


define('MAIL_SMTPAUTH', true);
define('MAIL_SMTPSECURE', false);
define('MAIL_SMTPAUTOTLS', false);

/* MAIL_USERNAME, MAIL_SENDER & MAIL_FROM should all hold the same value to avoid spam */
define('MAIL_USERNAME', 'contato@ktnb.com.br'); // for smtp auth
define('MAIL_PASSWORD', 'kontatoBK!26'); // for smtp auth

define('MAIL_SENDER', 'contato@ktnb.com.br');
define('MAIL_FROM', 'contato@ktnb.com.br');
define('MAIL_SENDER_NAME', "KTNB");


/* your actual keys */
$config = [
    /* exclusive key for DTC */
    'keys' => [
        'chavedaktnbargh' => [
            'domains' => [
                'localhost',
                'ktnb.com.br',
            ]
        ]
    ]
];