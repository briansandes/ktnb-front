<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* returns json with error true and an error message */
function throwError($message) {
    output([
        'error' => 1,
        'message' => $message
    ]);
}

/* returns a ok response, with a general message */
/* ideal for post request where a confirmation message is returned */

function responseOK($message) {
    output([
        'error' => 0,
        'message' => $message
    ]);
}

/* returns a json file of any object */

function output($data) {
    header('Content-Type: application/json');
    die(json_encode($data));
}

/* checks whether user can send emails based on intervals set on config.php */

function checkLimit() {
    if(!file_exists('log/')) {
        mkdir('log', 0777);
        mkdir('log/messages', 0777);
    }
    
    $todaysLogFile = 'log/' . date('Y-m-d') . '.php';

    /* if no email has been sent today,  */
    if (!file_exists($todaysLogFile)) {
        return [
            'error' => false
        ];
    } else {
        include $todaysLogFile;

        if(count($sendings) >= DAILY_LIMIT) {
            return [
                'error' => true,
                'message' => 'You have reached the daily limit of sendings. Wait until 12AM UTC to be able to send an email again.'
            ];
        } else {
            $lastSend = json_decode(end($sendings));

            $difference = time() - ((int) $lastSend->time);

            if ($difference < MESSAGE_COOLDOWN) {
                return [
                    'error' => true,
                    'message' => 'Wait ' . (MESSAGE_COOLDOWN - $difference) . ' seconds to send another email.'
                ];
            } else {
                return [
                    'error' => false
                ];
            }
        }
    }
}

/* what the actual hell is that? */
function decodeKey($key) {
    $decoded = '';
    
    try {
        $decoded = base64_decode($key);
    } catch (Exception $e) {}
    
    return $decoded;
}

function sanitizeParams($params) {
    $sanitizedParams = ['message' => ''];
    $phpToBeRemoved = ['<?php', '<?phP', '<?pHp', '<?pHP', '<?Php', '<?Php', '<?PHp', '<?PHP', '<?', '<?=', '?>'];

    $paramsToClear = ['key', 'to', 'subject', 'requestDomain', 'referer', 'ip', 'ff', 'debug'];
    $allowedTags = [
        '!doctype', 'doctype', '!doctype ', 'doctype ',
        '!DOCTYPE', 'DOCTYPE', '!DOCTYPE ', 'DOCTYPE ',
        'html', 'head', 'meta', 'title', 'link', 'style', 'xml',
        'body',
        'div', 'img', 'p', 'table', 'thead', 'tbody', 'tr', 'td', 'th',
        'span', 'a', 'b', 'i', 'u', 'em', 'strong', 'code', 'pre',
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        'br', 'hr'
    ];

    foreach ($paramsToClear as $param) {
        $sanitizedParams[$param] = strip_tags(str_replace($phpToBeRemoved, null, $params[$param]));
    }

    /* replaces unallowed tags from the email message */
    $sanitizedParams['message'] = strip_tags(str_replace($phpToBeRemoved, null, $params['message']), '<'.join('><', $allowedTags).'>');
    $sanitizedParams['message'] = str_replace('< html>', '<!DOCTYPE html>', $sanitizedParams['message']);
    $sanitizedParams['debug'] = (bool)$sanitizedParams['debug'];
    
    return $sanitizedParams;
}

/* registers a sending on todays log file */
function registerSending($params, $status) {
    $today = date('Y-m-d');
    $todaysLogFile = 'log/' . $today . '.php';
    $todaysFolder = 'log/messages/'.$today.'/';

    if (!file_exists($todaysLogFile)) {
        file_put_contents($todaysLogFile, '<?php' . PHP_EOL . '$sendings = [];' . PHP_EOL);
    }

    $params['time'] = time();
    $params['status'] = $status;
    
    
    $sortedParams = [
        'time' => $params['time'],
        'status' => $params['status'],
        'requestDomain' => $params['requestDomain'],
        'ip' => $params['ip'],
        'ff' => $params['ff'],
        'referer' => $params['referer'],
        'to' => $params['to'],
        'subject' => $params['subject'],
        'messageMD5' => md5($params['message']),
        'debug' => $params['debug'],
        'dev_debug' => DEV_DEBUG
    ];

    $content = '$sendings[] = \'' . json_encode($sortedParams) . '\';' . PHP_EOL;

    $handle = fopen($todaysLogFile, 'a+');
    fwrite($handle, $content);
    fclose($handle);
    
    /* saves a backup of the message */
    if(!file_exists($todaysFolder)) {
        mkdir($todaysFolder, 0777);
    }
    
    file_put_contents($todaysFolder.$params['time'].'.html', $params['message']);
}

function sendMessage($params) {
    require 'PHPMailer/Exception.php';
    require 'PHPMailer/PHPMailer.php';
    require 'PHPMailer/SMTP.php';
    
    $params['to'] = str_replace(' ', '', $params['to']);
    
    $explodedContacts = explode(',', $params['to']);
    
    $mail = new PHPMailer();

    $mail->IsSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = MAIL_SMTPAUTH;
    $mail->Port = MAIL_PORT;
    $mail->SMTPSecure = MAIL_SMTPSECURE;
    $mail->SMTPAutoTLS = MAIL_SMTPAUTOTLS;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;

    // SENDER DETAILS
    $mail->Sender = MAIL_SENDER;
    $mail->From = MAIL_FROM;
    $mail->FromName = MAIL_SENDER_NAME;

    // RECIPIENTS
    foreach ($explodedContacts as $contact) {
        $mail->AddAddress($contact);
    }

    // HTML SETUP AND EMAIL ENCONDING
    $mail->IsHTML(true);
    $mail->CharSet = 'utf-8';

    // EMAIL BODY
    $mail->Subject = $params['subject'];
    $mail->Body = $params['message'];
    
    // SENDING MAIL
    $emailDidSend = $params['debug'] === true ? true : $mail->Send();

    $mail->ClearAllRecipients();

    registerSending($params, $emailDidSend);

    return $emailDidSend;
}