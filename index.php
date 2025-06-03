<?php

// maintenance
//http_response_code(503); exit;

// include
require_once('composer/vendor/autoload.php');

use \Firebase\JWT\JWT;
use \Phalcon\Config;
use \Phalcon\Mvc\Micro;
use \Prado\Util\TVarDumper;

/**
 * set constant
 */
define('CONFIGPATH', 'app/config/');

/**
 * load config file
 */
$Config = new Config(require_once(CONFIGPATH . 'main.php'));

/**
 * start micro app
 */
$Micro = new Micro();

/**
 * autoloader
 */
$Loader = require_once(CONFIGPATH . 'loader.php');

/**
 * dependency injector
 */
$FactoryDefault = require_once(CONFIGPATH . 'service.php');
$Micro->setDI($FactoryDefault);

/**
 * load routemap
 */
$Collection_ = require_once(CONFIGPATH . 'routemap.php');
foreach ($Collection_ as $Collection) {
    $Micro->mount($Collection);
}

/**
 * error handler
 */
$errorhandler = function ($exception) use ($Micro) {
    $config      = $Micro->getSharedService('config');
    $firewall    = $Micro->getSharedService('firewall');
    $registry    = $Micro->getSharedService('registry');
    $escaper     = $Micro->getSharedService('escaper');
    $code        = $exception->getCode();
    $myException = TVarDumper::dump($exception, 2);
    if (in_array($code, (array) $config->firewall->code)) {
        $firewall->increaseHeat();
    }
    if (!in_array($code, (array) $config->log->exclude)) {
        $ip         = $Micro->request->getClientAddress();
        $agent      = $Micro->request->getUserAgent();
        $basicAuth  = $Micro->request->getBasicAuth();
        $basicAuth  = empty($basicAuth) ? '' : TVarDumper::dump($basicAuth);
        $bearer     = $Micro->getSharedService('bearer');
        $url        = $Micro->router->getRewriteUri();
        $method     = $Micro->request->getMethod();
        $header     = $Micro->request->getHeaders();
        $header     = empty($header) ? '' : TVarDumper::dump($header);
        $body       = $Micro->request->getRawBody();
        $myRegistry = $registry;
        $myRegistry = empty($myRegistry) ? '' : TVarDumper::dump($myRegistry);
        switch ($config->log->handler) {
            case 'DB':
                $LogRest            = new LogRest();
                $LogRest->ip        = $ip;
                $LogRest->agent     = $agent;
                $LogRest->url       = $url;
                $LogRest->method    = $method;
                $LogRest->header    = $header;
                $LogRest->body      = $body;
                $LogRest->registry  = $myRegistry;
                $LogRest->exception = $myException;
                $LogRest->created   = date('Y-m-d H:i:s');
                $LogRest->save();
                break;
            case 'FILE':
                $logger = $Micro->getSharedService('logger');
                $logger->error(
                    PHP_EOL .
                    'ip         :' . $ip . PHP_EOL .
                    'agent      :' . $agent . PHP_EOL .
                    'basicauth  :' . $basicAuth . PHP_EOL .
                    'bearer     :' . $bearer . PHP_EOL .
                    'url        :' . $url . PHP_EOL .
                    'method     :' . $method . PHP_EOL .
                    'header     :' . $header . PHP_EOL .
                    'body       :' . $body . PHP_EOL .
                    'registry   :' . $myRegistry . PHP_EOL .
                    'exception  :' . $myException . PHP_EOL
                );
                break;
        }
    }
    if ($code === 0 || substr($exception->getMessage(), 0, 8) === 'SQLSTATE') {
        $code = 500;
    }
    $message       = $code === 500 ?
        'Oops, an error has been occurred' : $exception->getMessage();
    $message       = str_replace(array("\r", "\n"), '; ', $message);
    $message       = $escaper->escapeHtml($message);
    $headerMessage = strlen($message) > 47 ?
        substr($message, 0, 47) . '...' : $message;
    $relogin       = isset($registry->relogin) ? $registry->relogin : false;
    $Micro->response->setHeader('X-Content-Type-Options', 'nosniff');
    $Micro->response->setHeader('X-Frame-Options', 'deny');
    $Micro->response->setStatusCode($code, $headerMessage);
    $Micro->response->sendHeaders();
    if ($config->status === 'development') {
        $message       = $myException;
    }
    $Micro->response->setJsonContent([
        $config->rest->statuskey  => $config->rest->statuserror,
        $config->rest->messagekey => $message,
        $config->rest->reloginkey => $relogin,
        $config->rest->resultkey  => null,
    ]);
    $Micro->response->send();
    exit();
};
$Micro->error($errorhandler);

/**
 * handle warning as error
 */
function warningAsError($errNo, $errStr, $errFile, $errLine)
{
    if (error_reporting() == 0) {
        // @ suppression used, don't worry about it
        return;
    }
    $Exception = new Exception(
        $errFile . ', ' . $errLine . ', ' . $errNo . ', ' . $errStr
    );
    global $errorhandler;
    $errorhandler($Exception);
}
set_error_handler('warningAsError');

/**
 * handle fatal error
 * https://stackoverflow.com/questions/277224/how-do-i-catch-a-php-fatal-error?rq=1
 */
function shutDownFunction()
{
    $error = error_get_last();
    if (!is_null($error) && $error['type'] === E_ERROR) {
        $Exception = new Exception(
            $error['type'] . ', ' . $error['message'] . ', ' . $error['file']
            . ', ' . $error['line']
        );
        global $errorhandler;
        $errorhandler($Exception);
    }
}
register_shutdown_function('shutDownFunction');

/**
 * not found handler
 * HTTP CODE 404
 */
$Micro->notFound(function () use ($errorhandler) {
    $errorhandler(new Exception('Not Found', 404));
});

/**
 * authorization
 */
$Micro->before(function () use ($Micro) {
    $registry          = $Micro->getSharedService('registry');
    $registry->relogin = false;
    $firewall          = $Micro->getSharedService('firewall');
    if ($firewall->isBlock()) {
        $registry->relogin = true;
        throw new Exception('Block by Firewall', 423);
    }
    $config   = $Micro->getSharedService('config');
    $acl      = $Micro->getSharedService('acl');
    $security = $Micro->getSharedService('security');
    $handler  = $Micro->getActiveHandler();
    $backdoor = $Micro->request->get('bd');
    if ($security->checkHash($config->admin->backdoor, $backdoor)) {
        $allowed = $acl->isAllowed(
            'BACKDOOR',
            $handler[0]->myClassName(),
            $handler[1]
        );
        if ($allowed) {
            return true;
        }
    }
    $deviceId   = $Micro->request->getHeader('device-id');
    $deviceType = $Micro->request->getHeader('device-type');
    $deviceApp  = $Micro->request->getHeader('device-app');
    if (empty($deviceId) || empty($deviceType) || empty($deviceApp)) {
        throw new Exception('Bad Request', 400);
    }
    $registry->deviceId   = $deviceId;
    $registry->deviceType = $deviceType;
    $registry->deviceApp  = $deviceApp;
    $jwt                  = $Micro->getSharedService('bearer');
    try {
        if (empty($jwt)) {
            $allowed = $acl->isAllowed(
                'GUEST',
                $handler[0]->myClassName(),
                $handler[1]
            );
            if ($allowed) {
                return true;
            }
            throw new Exception('Invalid Token', 401);
        }
        $publicKey     = $config->status === 'development' ?
            $config->JWT->developmentpublickey : $config->JWT->publickey;
        $token         = (array) JWT::decode(
            $jwt,
            $publicKey,
            [$config->JWT->alogaritm]
        );
        if ($token['iss'] !== $config->application->servername) {
            throw new Exception('Invalid Token', 401);
        }
        $token['data'] = (array) $token['data'];
        if (!isset($token['data']['dbUserId'],
                $token['data']['dbUsername'],
                $token['data']['dbUserType'],
                $token['data']['dbCompanyId'],
                $token['data']['dbCompanyName'],
                $token['data']['dbBranchId'],
                $token['data']['dbBranchName'],
                $token['data']['dbDeviceId'],
                $token['data']['dbDeviceType'])) {
            throw new Exception('Invalid Token', 401);
        }
        foreach ($token['data'] as $key => $value) {
            $registry[$key] = $value;
        }
        if (UserLogout::exist($registry['dbUserId'], $token['iat'])>0) {
            $registry->relogin = true;
            throw new Exception('Please Login Again', 498);
        }
        if (@$token['aud'] !== 'ACCESS') {
            if (@$token['aud'] === 'REFRESH') {
                $allowed = $acl->isAllowed(
                    'GUEST',
                    $handler[0]->myClassName(),
                    $handler[1]
                );
                if ($allowed) {
                    return true;
                }
            }
            throw new Exception('Invalid Token', 401);
        }
        $pin = $Micro->request->getHeader('authority');
        if (!empty($pin)) {
            $User = User::checkVoidAuthority($pin, $token['data']['dbBranchId']);
            if ($User) {
                $type = $User->type;
                if ($User->void_pin===$pin) {
                    $type = 'VOID';
                }
                if ($acl->isAllowed(
                    $type,
                    $handler[0]->myClassName(),
                    $handler[1]
                )) {
                    $registry['dbAuthorityId'] = $User->id;
                    $registry['dbAuthorityType'] = $User->type;
                }
            }
        }
        $allowed = $acl->isAllowed(
            $token['data']['dbUserType'],
            $handler[0]->myClassName(),
            $handler[1]
        );
        if (!$allowed && !isset($registry['dbAuthorityId'])) {
            throw new Exception('Higher Authority is Required', 403);
        }
        return true;
    } catch (\Firebase\JWT\SignatureInvalidException $ex) {
        throw new Exception($ex->getMessage(), 401);
    } catch (\Firebase\JWT\BeforeValidException $ex) {
        throw new Exception($ex->getMessage(), 401);
    } catch (\Firebase\JWT\ExpiredException $ex) {
        throw new Exception($ex->getMessage(), 498);
    } catch (\Exception $ex) {
        throw $ex;
    }
});

/**
 * after controller done, sending result as json
 */
$Micro->after(function () use ($Micro) {
    $config   = $Micro->getSharedService('config');
    $registry = $Micro->getSharedService('registry');
    $escaper  = $Micro->getSharedService('escaper');
    $Micro->response->setHeader('X-Content-Type-Options', 'nosniff');
    $Micro->response->setHeader('X-Frame-Options', 'deny');
    $Micro->response->setStatusCode(200, $config->rest->statusok);
    $Micro->response->sendHeaders();
    $result   = $Micro->getReturnedValue();
    if (is_array($result)) {
        array_walk_recursive($result, function (&$item, &$key) use ($escaper) {
            if (is_string($key) && !empty($key)) {
                $key  = $escaper->escapeHtml($key);
            }
            if (is_string($item) && !empty($item)) {
                $item = $escaper->escapeHtml($item);
            }
        });
    } else {
        $result = null;
    }
    $Micro->response->setJsonContent([
        $config->rest->statuskey  => $config->rest->statusok,
        $config->rest->messagekey => null,
        $config->rest->reloginkey => $registry->relogin,
        $config->rest->resultkey  => $result,
    ]);
    $Micro->response->send();
});

/**
 * after all done
 */
$Micro->finish(function () use ($Micro) {
});

/**
 * run phalcon run!
 */
$Micro->handle();
