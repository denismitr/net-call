<?php

namespace Denismitr\NetCall;


use Denismitr\NetCall\Contracts\NetCallResponseInterface;
use Denismitr\NetCall\Exceptions\NetCallException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Closure;

class NetCall
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $beforeSendingCallbacks;

    /**
     * @var string
     */
    protected $bodyFormat = 'json';

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var NetCall
     */
    protected static $mock;

    /**
     * NetCallRequest constructor.
     */
    public function __construct()
    {
        $this->beforeSendingCallbacks = collect();
        $this->bodyFormat = 'json';
        $this->options = [
            'http_errors' => false
        ];
    }

    /**
     * @param NetCall $mock
     */
    public static function setTestMock(NetCall $mock)
    {
        static::$mock = $mock;
    }

    public static function clearMock()
    {
        static::$mock = null;
    }

    /**
     * @return NetCall
     */
    public static function new(): self
    {
        if (static::$mock) {
            return static::$mock;
        }

        return new static;
    }

    /**
     * @return NetCall
     */
    public function noRedirects(): self
    {
        return $this->mergeToOptions(['allow_redirects' => false]);
    }

    /**
     * @return NetCall
     */
    public function noVerify(): self
    {
        return $this->mergeToOptions(['verify' => false]);
    }

    /**
     * @return NetCall
     */
    public function asJson(): self
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }

    /**
     * @return NetCall
     */
    public function asFormData(): self
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    public function asMultipart(): self
    {
        return $this->bodyFormat('multipart');
    }

    /**
     * @param string $format
     * @return NetCall
     */
    public function bodyFormat(string $format): self
    {
        $this->bodyFormat = $format;

        return $this;
    }

    /**
     * @param string $type
     * @return NetCall
     */
    public function contentType(string $type) : self
    {
        return $this->withHeaders(['Content-Type' => $type]);
    }

    /**
     * @param string $header
     * @return NetCall
     */
    public function accept(string $header): self
    {
        return $this->withHeaders(['Accept' => $header]);
    }

    /**
     * @param array $headers
     * @return NetCall
     */
    public function withHeaders(array $headers): self
    {
        return $this->mergeToOptions(['headers' => $headers]);
    }

    /**
     * @param string $username
     * @param string $password
     * @return NetCall
     */
    public function withBasicAuth(string $username, string $password): self
    {
        return $this->mergeToOptions(['auth' => [$username, $password]]);
    }

    /**
     * @param string $username
     * @param string $password
     * @return NetCall
     */
    public function withDigestAuth(string $username, string $password): self
    {
        return $this->mergeToOptions(['auth' => [$username, $password, 'digest']]);
    }

    /**
     * @param int $seconds
     * @return NetCall
     */
    public function timeout(int $seconds): self
    {
        return $this->mergeToOptions(['timeout' => $seconds]);
    }

    /**
     * @param callable $callback
     * @return NetCall
     */
    public function beforeSending(callable $callback): self
    {
        return tap($this, function() use ($callback) {
            $this->beforeSendingCallbacks[] = $callback;
        });
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     * @return NetCallResponseInterface
     * @throws NetCallException
     */
    public function send(string $method, string $url, array $options = []) : NetCallResponseInterface
    {
        try {
            $this->mergeToOptions([
                'query' => $this->parseQueryParams($url)
            ], $options);

            $guzzleResponse = $this->buildClient()->request($method, $url, $this->options);

            return new NetCallResponse($guzzleResponse);
        } catch (ConnectException $e) {
            throw new NetCallException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $url
     * @param array $queryParams
     * @return NetCallResponseInterface
     */
    public function get(string $url, array $queryParams = []) : NetCallResponseInterface
    {
        return $this->send('GET', $url, [
            'query' => $queryParams
        ]);
    }

    /**
     * @param string $url
     * @param array $params
     * @return NetCallResponseInterface
     */
    public function post(string $url, array $params = []) : NetCallResponseInterface
    {
        return $this->send('POST', $url, [
            $this->bodyFormat => $params
        ]);
    }

    /**
     * @param string $url
     * @param array $params
     * @return NetCallResponseInterface
     */
    public function patch(string $url, array $params = []) : NetCallResponseInterface
    {
        return $this->send('PATCH', $url, [
            $this->bodyFormat => $params
        ]);
    }

    /**
     * @param string $url
     * @param array $params
     * @return NetCallResponseInterface
     */
    public function put(string $url, array $params = []) : NetCallResponseInterface
    {
        return $this->send('PUT', $url, [
            $this->bodyFormat => $params
        ]);
    }

    /**
     * @param string $url
     * @param array $params
     * @return NetCallResponseInterface
     */
    public function delete(string $url, array $params = []) : NetCallResponseInterface
    {
        return $this->send('DELETE', $url, [
            $this->bodyFormat => $params
        ]);
    }

    /**
     * @param string $url
     * @return array
     */
    public function parseQueryParams(string $url) : array
    {
        return tap([], function(&$queryArray) use ($url) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?: "", $queryArray);
        });
    }

    /**
     * @return Client
     */
    protected function buildClient() : Client
    {
        return new Client(['handler' => $this->buildHandlerStack()]);
    }

    /**
     * @return HandlerStack
     */
    protected function buildHandlerStack() : HandlerStack
    {
        return tap(HandlerStack::create(), function($stack) {
            $stack->push($this->buildBeforeSendingHandler());
        });
    }

    /**
     * @return Closure
     */
    protected function buildBeforeSendingHandler(): Closure
    {
        return function($handler) {
            return function($request, $options) use ($handler) {
                return $handler($this->runBeforeSendingCallbacks($request), $options);
            };
        };
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function runBeforeSendingCallbacks(Request $request)
    {
        return tap($request, function(Request $request) {
            $this->beforeSendingCallbacks->each->__invoke(new NetCallRequest($request));
        });
    }

    /**
     * @param array $newOptions
     * @return NetCall
     */
    protected function mergeToOptions(...$newOptions): self
    {
        $this->options = array_merge_recursive($this->options, ...$newOptions);

        return $this;
    }
}