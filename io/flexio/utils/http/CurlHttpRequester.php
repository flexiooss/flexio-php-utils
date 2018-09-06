<?php

namespace io\flexio\utils\http;


class CurlHttpRequester implements HttpRequester {

    private $client;
    private $path;
    private $responseHeaders = [];
    private $requestHeaders;
    private $requestParameters;

    public function __construct() {
        $this->client = curl_init();
        $this->init();
    }

    private function init() {
        curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, false);
        $this->addHeaderHandler();
        $this->requestHeaders = array();
        $this->requestParameters = array();
    }

    private function reset() {
        curl_reset($this->client);
        $this->init();
        return $this;
    }

    public function __destruct() {
        curl_close($this->client);
    }

    private function addHeaderHandler() {
        curl_setopt($this->client, CURLOPT_HEADERFUNCTION, function( $curl, $header ) {
            $len    = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2){ // ignore invalid headers
                return $len;
            }
            $name = strtolower(trim($header[0]));
            if (!array_key_exists($name, $this->responseHeaders)){
                $this->responseHeaders[$name] = [trim($header[1])];
            } else {
                $this->responseHeaders[$name][] = trim($header[1]);
            }
            return $len;
        });
    }

    private function exec() {
        $this->responseHeaders = array();
        return curl_exec($this->client);
    }

    private function requestWithoutPayload( $method ): ResponseDelegate {
        $query = http_build_query( $this->requestParameters );
        curl_setopt( $this->client, CURLOPT_CUSTOMREQUEST, $method );
        curl_setopt( $this->client, CURLOPT_URL, $this->path . $query );
        curl_setopt( $this->client, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $this->client, CURLOPT_HTTPHEADER, $this->requestHeaders );
        $response = $this->exec();
        $code = curl_getinfo($this->client, CURLINFO_HTTP_CODE);
        $this->reset();
        return new CurlResponseDelegate($code, $response, $this->responseHeaders );
    }

    private function requestWithPayload( string $body, string $contentType, $method ): ResponseDelegate {
        $json = json_encode( $body );
        $this->requestHeaders[] = 'Expect:';
        curl_setopt( $this->client, CURLOPT_CUSTOMREQUEST, $method );
        curl_setopt( $this->client, CURLOPT_URL, $this->path );
        curl_setopt( $this->client, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $this->client, CURLOPT_POSTFIELDS, $json );
        curl_setopt( $this->client, CURLOPT_HTTPHEADER, $this->requestHeaders );
        $response = $this->exec();
        $code = curl_getinfo( $this->client, CURLINFO_HTTP_CODE );
        $curlError = curl_error( $this->client );
        $this->reset();
        return new CurlResponseDelegate( $code, $response, $this->responseHeaders );
    }

    public function get(): ResponseDelegate {
        return requestWithoutPayload( 'GET' );
    }

    public function post( string $contentType = null, string $body = null ): ResponseDelegate {
        return requestWithPayload( $body, $contentType, 'POST' );
    }

    public function put( string $contentType = null, string $body = null ): ResponseDelegate {
        return requestWithPayload( $body, $contentType, 'PUT' );
    }

    public function patch( string $contentType = null, string $body = null ): ResponseDelegate {
        return requestWithPayload( $body, $contentType, 'PATCH' );
    }

    public function delete(): ResponseDelegate {
        return requestWithoutPayload( 'DELETE' );
    }

    public function head(): ResponseDelegate {
        return requestWithoutPayload( 'HEAD' );
    }



    public function parameter( string $name, string $value ): HttpRequester {
        $this->requestParameters[$name] = $value;
        return $this;
    }

    public function arrayParameter( string $name, array $values ): HttpRequester {
        $this->requestParameters[$name] = $value;
        return $this;
    }

    public function header( string $name, string $value ): HttpRequester {
        $this->requestHeaders[$name] = $value;
        return $this;
    }

    public function arrayHeader( string $name, array $value ): HttpRequester {
        $this->requestHeaders[$name] = $value;
        return $this;
    }

    public function path( string $path ): HttpRequester {
        $this->path = $url;
        return $this;
    }

}