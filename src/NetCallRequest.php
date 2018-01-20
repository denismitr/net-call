<?php

namespace Denismitr\NetCall;


use Denismitr\NetCall\Contracts\NetCallRequestInterface;
use GuzzleHttp\Psr7\Request;

class NetCallRequest implements NetCallRequestInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * RequestInfo constructor.
     * @param $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function url() : string
    {
        return (string) $this->request->getUri();
    }

    /**
     * @return string
     */
    public function method() : string
    {
        return $this->request->getMethod();
    }

    /**
     * @return string
     */
    public function body() : string
    {
        return (string) $this->request->getBody();
    }

    /**
     * @return array
     */
    public function headers() : array
    {
        return collect($this->request->getHeaders())->mapWithKeys(function ($values, $header) {
            return [$header => $values[0]];
        })->all();
    }
}