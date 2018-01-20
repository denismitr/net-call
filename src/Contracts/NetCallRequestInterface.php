<?php

namespace Denismitr\NetCall\Contracts;


interface NetCallRequestInterface
{
    /**
     * @return string
     */
    public function url() : string;

    /**
     * @return string
     */
    public function method() : string;

    /**
     * @return string
     */
    public function body() : string;

    /**
     * @return array
     */
    public function headers() : array;
}