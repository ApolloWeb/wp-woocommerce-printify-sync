<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use eftec\bladeone\BladeOne;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use phpseclib3\Crypt\AES;
use Exception;

class LibraryLoader {
    private static ?Client $httpClient = null;
    private static ?BladeOne $bladeEngine = null;

    public static function initLibraries(): void {
        // Load PSR interfaces first
        require_once WPWPS_PATH . 'lib/psr/http-client/ClientInterface.php';
        require_once WPWPS_PATH . 'lib/psr/http-message/MessageInterface.php';
        require_once WPWPS_PATH . 'lib/psr/http-message/RequestInterface.php';
        require_once WPWPS_PATH . 'lib/psr/http-message/ResponseInterface.php';
        require_once WPWPS_PATH . 'lib/psr/http-message/ServerRequestInterface.php';
        require_once WPWPS_PATH . 'lib/psr/http-message/StreamInterface.php';
        require_once WPWPS_PATH . 'lib/psr/http-message/UploadedFileInterface.php';
        require_once WPWPS_PATH . 'lib/psr/http-message/UriInterface.php';

        // Load GuzzleHttp core files in correct order
        require_once WPWPS_PATH . 'lib/GuzzleHttp/ClientInterface.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/ClientTrait.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/functions.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/functions_include.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/Utils.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/Handler/CurlHandler.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/Handler/CurlMultiHandler.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/Handler/Proxy.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/Handler/StreamHandler.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/HandlerStack.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/Middleware.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/RedirectMiddleware.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/RequestOptions.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/Client.php';

        // Load Promise interfaces
        require_once WPWPS_PATH . 'lib/GuzzleHttp/Promise/PromiseInterface.php';
        require_once WPWPS_PATH . 'lib/GuzzleHttp/Promise/functions.php';

        // Load BladeOne
        require_once WPWPS_PATH . 'lib/BladeOne/BladeOne.php';

        // Load phpseclib
        require_once WPWPS_PATH . 'lib/phpseclib/phpseclib/bootstrap.php';
    }

    public static function getHttpClient(): Client {
        if (self::$httpClient === null) {
            self::$httpClient = new Client([
                'base_uri' => 'https://api.printify.com/v1/',
                RequestOptions::TIMEOUT => 30,
                RequestOptions::HEADERS => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);
        }
        return self::$httpClient;
    }

    public static function getBladeEngine(): BladeOne {
        if (self::$bladeEngine === null) {
            $views = WPWPS_PATH . 'templates';
            $cache = WPWPS_PATH . 'templates/cache';
            self::$bladeEngine = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
        }
        return self::$bladeEngine;
    }

    public static function encrypt(string $value, string $key): string {
        try {
            $cipher = new AES('cbc');
            $cipher->setKey($key);
            $iv = random_bytes(16);
            $cipher->setIV($iv);
            return base64_encode($iv . $cipher->encrypt($value));
        } catch (Exception $e) {
            error_log('WPWPS encryption error: ' . $e->getMessage());
            return '';
        }
    }

    public static function decrypt(string $value, string $key): string {
        try {
            $data = base64_decode($value);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            $cipher = new AES('cbc');
            $cipher->setKey($key);
            $cipher->setIV($iv);
            return $cipher->decrypt($encrypted);
        } catch (Exception $e) {
            error_log('WPWPS decryption error: ' . $e->getMessage());
            return '';
        }
    }
}