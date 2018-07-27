<?php

namespace io\flexio\utils\http;

interface HttpRequester {

    public function get(): ResponseDelegate;
    public function post( string contentType, string body ): ResponseDelegate;
    public function put( string contentType, string body ): ResponseDelegate;
    public function patch( string contentType, string body ): ResponseDelegate;
    public function delete(): ResponseDelegate;
    public function delete( string contentType, string body ): ResponseDelegate;
    public function head(): ResponseDelegate;

    public function parameter( string $name, string $value ): HttpRequester;

    public function header( string $name, string $value ): HttpRequester;

    public function path( string $path ): HttpRequester;
}