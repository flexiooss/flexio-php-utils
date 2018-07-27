<?php

namespace io\flexio\utils\http;

interface ResponseDelegate {

    public function code(): int;
    public function body(): string;
    public function header( string $name ): string;
    public function contentType(): string;

}