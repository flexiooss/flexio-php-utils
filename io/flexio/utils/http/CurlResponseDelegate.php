<?php

namespace io\flexio\utils\http;

class CurlResponseDelegate implements ResponseDelegate {

    private $code;
    private $body;
    private $headers;

    public function __construct( int $code, string $body, array $headers = array() ) {
        $this-> code = $code;
        $this-> body = $body;
        $this-> headers = $headers;
    }

    public function code(): int {
        return $this -> code;
    }

    public function body(): string {
        return $this -> body;
    }

    public function header( string $name ): array {
        if( isset( $this->headers[$name . "*"] ) ) {
            return $this->decode( $this->headers[$name . "*"] );
        }
        return $this -> headers[$name];
    }

    public function contentType(): string {
        return $this->header( 'content-type' );
    }

    private function decode( $string ) {
        $explode = explode( "'", $string );
        return urldecode( $explode[count($explode)-1] );
    }

}