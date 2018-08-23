<?php

namespace io\flexio\utils\http;

interface HttpRequester {

    public function get(): ResponseDelegate;
    public function post( string $contentType = null, string $body = null ): ResponseDelegate;
    public function put( string $contentType = null, string $body = null ): ResponseDelegate;
    public function patch( string $contentType = null, string $body = null ): ResponseDelegate;
    public function delete(): ResponseDelegate;
    public function head(): ResponseDelegate;

    public function parameter( string $name, string $value ): HttpRequester;
    public function arrayParameter( string $name, string $value ): HttpRequester;

    public function header( string $name, string $value ): HttpRequester;
    public function arrayHeader( string $name, array $value ): HttpRequester;

    public function path( string $path ): HttpRequester;
}