<?php

namespace Denismitr\NetCall;

use Denismitr\NetCall\Contracts\NetCallResponseInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Traits\Macroable;

class NetCallResponse implements NetCallResponseInterface
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * @var Response
     */
    protected $response;

    /**
     * NetCallResponse constructor.
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function body() : string
    {
        return (string) $this->response->getBody();
    }

    /**
     * @return array
     */
    public function json() : array
    {
        return json_decode($this->response->getBody(), true);
    }

    /**
     * @param string $headerKey
     * @return string
     */
    public function header(string $headerKey) : string
    {
        return $this->response->getHeaderLine($headerKey);
    }

    /**
     * @return array
     */
    public function headers() : array
    {
        return collect($this->response->getHeaders())->mapWithKeys(function($v, $k) {
            return [$k => $v[0]];
        })->all();
    }

    /**
     * @return int
     */
    public function status() : int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return bool
     */
    public function isSuccess() : bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * @return bool
     */
    public function isOk() : bool
    {
        return $this->isSuccess();
    }

    /**
     * @return bool
     */
    public function isRedirect() : bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * @return bool
     */
    public function isClientError() : bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * @return bool
     */
    public function isServerError() : bool
    {
        return $this->status() >= 500;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->body();
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $args);
        }

        return $this->response->{$method}(...$args);
    }
}