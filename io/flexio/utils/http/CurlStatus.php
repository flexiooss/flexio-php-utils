<?php
/**
 * User: thomas
 */

namespace io\flexio\utils\http;


class CurlStatus implements \JsonSerializable
{
    private $code;
    private $headers;
    private $body;
    private $infos;
    private $error;
    private $verbose = false;

    public function __construct($client, array $headers, string $body)
    {
        $this->code = curl_getinfo($client, CURLINFO_HTTP_CODE);
        $this->infos = curl_getinfo($client);
        $this->error = curl_error($client);
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }


    public function verboseOnSerialize(): CurlStatus
    {
        $this->verbose = true;
        return $this;
    }

    public function jsonSerialize()
    {
        $ret = [
            'code' => $this->code,
            'error' => $this->error,
            'headers' => $this->headers,
            'body' => $this->body
        ];
        if ($this->verbose) {
            $ret['infos'] = $this->infos;
        }

        return $ret;
    }

    public function __toString()
    {
        return json_encode($this);
    }
}