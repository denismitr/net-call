<?php

namespace Denismitr\NetCall\Contracts;


interface NetCallResponseInterface
{
    /**
     * @return string
     */
    public function body() : string;

    /**
     * @return array
     */
    public function json() : array;

    /**
     * @param string $headerKey
     * @return string
     */
    public function header(string $headerKey) : string;

    /**
     * @return array
     */
    public function headers() : array;

    /**
     * @return int
     */
    public function status() : int;

    /**
     * @return bool
     */
    public function isSuccess() : bool;

    /**
     * @return bool
     */
    public function isOk() : bool;

    /**
     * @return bool
     */
    public function isRedirect() : bool;

    /**
     * @return bool
     */
    public function isClientError() : bool;

    /**
     * @return bool
     */
    public function isServerError() : bool;

    /**
     * @return string
     */
    public function __toString();
}