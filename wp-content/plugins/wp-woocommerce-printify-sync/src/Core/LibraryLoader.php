<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use eftec\bladeone\BladeOne;
use Exception;

class LibraryLoader {
    private static $httpClient = null;
    private static $bladeEngine = null;

    public static function initLibraries(): void {
        // Create directory for PSR interfaces if not exists
        $psr_dir = WPWPS_PATH . 'lib/WpwpsPsr';
        if (!file_exists($psr_dir)) {
            mkdir($psr_dir, 0755, true);
        }

        // Create Promise directory if not exists
        $promise_dir = WPWPS_PATH . 'lib/GuzzleHttp/Promise';
        if (!file_exists($promise_dir)) {
            mkdir($promise_dir, 0755, true);
        }

        // Create PSR interfaces with proper namespaces
        self::createPsrInterfaces();
        
        // Create Promise interfaces
        self::createPromiseInterfaces();
        
        // Now load our custom PSR interfaces
        require_once WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Client_ClientInterface.php';
        require_once WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_MessageInterface.php';
        require_once WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_RequestInterface.php';
        require_once WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_ResponseInterface.php';
        require_once WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_StreamInterface.php';
        require_once WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_UriInterface.php';
        
        // Load GuzzleHttp core files
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
    
    private static function createPromiseInterfaces(): void {
        // Create PromiseInterface
        $promise_interface = <<<'PHP'
<?php
namespace GuzzleHttp\Promise;

interface PromiseInterface
{
    const PENDING = 'pending';
    const FULFILLED = 'fulfilled';
    const REJECTED = 'rejected';

    public function then(callable $onFulfilled = null, callable $onRejected = null);
    public function otherwise(callable $onRejected);
    public function getState();
    public function resolve($value);
    public function reject($reason);
    public function cancel();
    public function wait($unwrap = true);
}
PHP;
        file_put_contents(WPWPS_PATH . 'lib/GuzzleHttp/Promise/PromiseInterface.php', $promise_interface);

        // Create Promise functions
        $promise_functions = <<<'PHP'
<?php
namespace GuzzleHttp\Promise;

function is_promise($promise) {
    return $promise instanceof PromiseInterface;
}

function promise_for($value) {
    if ($value instanceof PromiseInterface) {
        return $value;
    }
    // Return a fulfilled promise.
    $p = new FulfilledPromise($value);
    return $p;
}

function rejection_for($reason) {
    if ($reason instanceof PromiseInterface) {
        return $reason;
    }
    // Return a rejected promise.
    $p = new RejectedPromise($reason);
    return $p;
}

function exception_for($reason) {
    return $reason instanceof \Exception || $reason instanceof \Throwable
        ? $reason
        : new \RuntimeException($reason);
}

// Empty implementations for minimal functionality
class FulfilledPromise implements PromiseInterface
{
    private $value;
    public function __construct($value) { $this->value = $value; }
    public function then(callable $onFulfilled = null, callable $onRejected = null) { return promise_for($this->value); }
    public function otherwise(callable $onRejected) { return $this; }
    public function getState() { return self::FULFILLED; }
    public function resolve($value) { throw new \LogicException("Cannot resolve a fulfilled promise"); }
    public function reject($reason) { throw new \LogicException("Cannot reject a fulfilled promise"); }
    public function cancel() { }
    public function wait($unwrap = true) { return $this->value; }
}

class RejectedPromise implements PromiseInterface
{
    private $reason;
    public function __construct($reason) { $this->reason = $reason; }
    public function then(callable $onFulfilled = null, callable $onRejected = null) { return rejection_for($this->reason); }
    public function otherwise(callable $onRejected) { return rejection_for($this->reason); }
    public function getState() { return self::REJECTED; }
    public function resolve($value) { throw new \LogicException("Cannot resolve a rejected promise"); }
    public function reject($reason) { throw new \LogicException("Cannot reject a rejected promise"); }
    public function cancel() { }
    public function wait($unwrap = true) { throw $this->reason; }
}
PHP;
        file_put_contents(WPWPS_PATH . 'lib/GuzzleHttp/Promise/functions.php', $promise_functions);
    }

    private static function createPsrInterfaces(): void {
        // Create ClientInterface
        $client_interface = <<<'PHP'
<?php
namespace Psr\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientInterface {
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
PHP;
        file_put_contents(WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Client_ClientInterface.php', $client_interface);

        // Create MessageInterface
        $message_interface = <<<'PHP'
<?php
namespace Psr\Http\Message;

interface MessageInterface {
    public function getProtocolVersion();
    public function withProtocolVersion($version);
    public function getHeaders();
    public function hasHeader($name);
    public function getHeader($name);
    public function getHeaderLine($name);
    public function withHeader($name, $value);
    public function withAddedHeader($name, $value);
    public function withoutHeader($name);
    public function getBody();
    public function withBody(StreamInterface $body);
}
PHP;
        file_put_contents(WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_MessageInterface.php', $message_interface);

        // Create RequestInterface
        $request_interface = <<<'PHP'
<?php
namespace Psr\Http\Message;

interface RequestInterface extends MessageInterface {
    public function getRequestTarget();
    public function withRequestTarget($requestTarget);
    public function getMethod();
    public function withMethod($method);
    public function getUri();
    public function withUri(UriInterface $uri, $preserveHost = false);
}
PHP;
        file_put_contents(WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_RequestInterface.php', $request_interface);

        // Create ResponseInterface
        $response_interface = <<<'PHP'
<?php
namespace Psr\Http\Message;

interface ResponseInterface extends MessageInterface {
    public function getStatusCode();
    public function withStatus($code, $reasonPhrase = '');
    public function getReasonPhrase();
}
PHP;
        file_put_contents(WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_ResponseInterface.php', $response_interface);

        // Create StreamInterface
        $stream_interface = <<<'PHP'
<?php
namespace Psr\Http\Message;

interface StreamInterface {
    public function __toString();
    public function close();
    public function detach();
    public function getSize();
    public function tell();
    public function eof();
    public function isSeekable();
    public function seek($offset, $whence = SEEK_SET);
    public function rewind();
    public function isWritable();
    public function write($string);
    public function isReadable();
    public function read($length);
    public function getContents();
    public function getMetadata($key = null);
}
PHP;
        file_put_contents(WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_StreamInterface.php', $stream_interface);

        // Create UriInterface
        $uri_interface = <<<'PHP'
<?php
namespace Psr\Http\Message;

interface UriInterface {
    public function getScheme();
    public function getAuthority();
    public function getUserInfo();
    public function getHost();
    public function getPort();
    public function getPath();
    public function getQuery();
    public function getFragment();
    public function withScheme($scheme);
    public function withUserInfo($user, $password = null);
    public function withHost($host);
    public function withPort($port);
    public function withPath($path);
    public function withQuery($query);
    public function withFragment($fragment);
    public function __toString();
}
PHP;
        file_put_contents(WPWPS_PATH . 'lib/WpwpsPsr/Psr_Http_Message_UriInterface.php', $uri_interface);
    }

    public static function getHttpClient() {
        if (self::$httpClient === null) {
            self::$httpClient = new \GuzzleHttp\Client([
                'base_uri' => 'https://api.printify.com/v1/',
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);
        }
        return self::$httpClient;
    }

    public static function getBladeEngine() {
        if (self::$bladeEngine === null) {
            $views = WPWPS_PATH . 'templates';
            $cache = WPWPS_PATH . 'templates/cache';
            self::$bladeEngine = new BladeOne($views, $cache, BladeOne::MODE_AUTO);
        }
        return self::$bladeEngine;
    }

    public static function encrypt($value, $key) {
        try {
            $cipher = new \phpseclib3\Crypt\AES('cbc');
            $cipher->setKey($key);
            $iv = random_bytes(16);
            $cipher->setIV($iv);
            return base64_encode($iv . $cipher->encrypt($value));
        } catch (Exception $e) {
            error_log('WPWPS encryption error: ' . $e->getMessage());
            return '';
        }
    }

    public static function decrypt($value, $key) {
        try {
            $data = base64_decode($value);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            $cipher = new \phpseclib3\Crypt\AES('cbc');
            $cipher->setKey($key);
            $cipher->setIV($iv);
            return $cipher->decrypt($encrypted);
        } catch (Exception $e) {
            error_log('WPWPS decryption error: ' . $e->getMessage());
            return '';
        }
    }
}