<?php

if ( ! function_exists('tap') ) {
    function tap($value, callable $callback) {
        $callback($value);
        return $value;
    }
}