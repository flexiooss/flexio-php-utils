<?php

namespace io\flexio\utils\http;


class CurlHttpRequester implements HttpRequester
{

    private $client;
    private $path;
    private $headersParams;
    private $responseHeaders = [];
    private $requestHeaders;
    private $requestParameters;
    private $logger;
    private $lastStatus;


    public function __construct(HeadersParameters $headersParams, callable $logger = null)
    {
        $this->headersParams = $headersParams;
        $this->client = curl_init();
        $this->init();
        $this->logger = $logger;
    }

    public function setCurlOption(int $option, $value): CurlHttpRequester
    {
        curl_setopt($this->client, $option, $value);
        return $this;
    }

    private function debug(string $message): CurlHttpRequester
    {
        if (!is_null($this->logger)) {
            ($this->logger)('CURL DEBUG ' . $message);
        }
        return $this;
    }

    private function init()
    {
        curl_setopt($this->client, CURLOPT_SSL_VERIFYPEER, false);
        $this->addHeaderHandler();
        $this->requestHeaders = $this->headersParams->toArrayHeaders();
        $this->requestParameters = array();
    }

    private function reset()
    {
        curl_reset($this->client);
        $this->init();
        return $this;
    }

    public function __destruct()
    {
        curl_close($this->client);
    }

    private function addHeaderHandler()
    {
        curl_setopt($this->client, CURLOPT_HEADERFUNCTION, function ($curl, $header) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) { // ignore invalid headers
                return $len;
            }
            $name = strtolower(trim($header[0]));
            if (!array_key_exists($name, $this->responseHeaders)) {
                $this->responseHeaders[$name] = [trim($header[1])];
            } else {
                $this->responseHeaders[$name][] = trim($header[1]);
            }
            return $len;
        });
    }

    private function exec()
    {
        $this->responseHeaders = array();
        return curl_exec($this->client);
    }

    private function requestWithoutPayload($method): ResponseDelegate
    {

        $query = http_build_query($this->requestParameters);

        $this
            ->debug('requestWithoutPayload')
            ->debug('method::' . $method)
            ->debug('path::' . $this->path . '?' . $query)
            ->debug('headers::' . json_encode($this->responseHeaders));

        curl_setopt($this->client, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->client, CURLOPT_URL, $this->path . '?' . $query);
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->client, CURLOPT_HTTPHEADER, $this->requestHeaders);

        $this->buildLastStatus($this->exec());
        $this->reset();

        $this->debug($this->lastStatus()->__toString());

        if ($this->lastStatus()->getError() !== '') {
            throw new HttpIOException('REQUEST FAIL :: ' . $this->lastStatus()->verboseOnSerialize()->__toString());
        }

        return new CurlResponseDelegate(
            $this->lastStatus()->getCode(),
            $this->lastStatus()->getBody(),
            $this->lastStatus()->getHeaders());
    }

    private function requestWithPayload(string $body, string $contentType, $method): ResponseDelegate
    {
        $this
            ->debug('requestWithPayload')
            ->debug('method::' . $method)
            ->debug('path::' . $this->path)
            ->debug('headers::' . json_encode($this->responseHeaders))
            ->debug('body::' . $body);

        $this->requestHeaders[] = 'Content-type: ' . $contentType;
        $this->requestHeaders[] = 'Expect:';
        curl_setopt($this->client, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->client, CURLOPT_URL, $this->path);
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->client, CURLOPT_POSTFIELDS, $body);
        curl_setopt($this->client, CURLOPT_HTTPHEADER, $this->requestHeaders);

        $this->buildLastStatus($this->exec());
        $this->reset();

        $this->debug($this->lastStatus()->__toString());

        return new CurlResponseDelegate(
            $this->lastStatus()->getCode(),
            $this->lastStatus()->getBody(),
            $this->lastStatus()->getHeaders());
    }

    private function buildLastStatus(string $body): CurlHttpRequester
    {
        $this->lastStatus = new CurlStatus(
            $this->client,
            $this->responseHeaders,
            $body
        );
        return $this;
    }

    public function lastStatus(): CurlStatus
    {
        return $this->lastStatus;
    }

    public function get(): ResponseDelegate
    {
        return $this->requestWithoutPayload('GET');
    }

    public function post(string $contentType = null, string $body = null): ResponseDelegate
    {
        return $this->requestWithPayload($body != null ? $body : "", $contentType != null ? $contentType : 'application/json', 'POST');
    }

    public function put(string $contentType = null, string $body = null): ResponseDelegate
    {
        return $this->requestWithPayload($body != null ? $body : "", $contentType != null ? $contentType : 'application/json', 'PUT');
    }

    public function patch(string $contentType = null, string $body = null): ResponseDelegate
    {
        return $this->requestWithPayload($body != null ? $body : "", $contentType != null ? $contentType : 'application/json', 'PATCH');
    }

    public function delete(): ResponseDelegate
    {
        return $this->requestWithoutPayload('DELETE');
    }

    public function head(): ResponseDelegate
    {
        return $this->requestWithoutPayload('HEAD');
    }


    public function parameter(string $name, string $value): HttpRequester
    {
        $this->requestParameters[$name] = $value;
        return $this;
    }

    public function arrayParameter(string $name, array $values): HttpRequester
    {
        $this->requestParameters[$name] = $values;
        return $this;
    }

    public function header(string $name, string $value): HttpRequester
    {
        if ($this->needEncoding($value)) {
            $this->requestHeaders[] = $name . "*: " . $this->encode($value);
        } else {
            $this->requestHeaders[] = $name . ": " . $value;
        }
        return $this;
    }

    private function needEncoding(string $value)
    {
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];
            if (ord($char) <= 31 || ord($char) >= 127) {
                return true;
            }
        }
        return false;
    }

    private function encode(string $value)
    {
        return "utf-8''" . urlencode($value);
    }

    public function arrayHeader(string $name, array $value): HttpRequester
    {
        $this->requestHeaders[$name] = $value;
        return $this;
    }

    public function path(string $path): HttpRequester
    {
        $this->path = $this->clearSlashes($path);
        return $this;
    }

    function clearSlashes($path)
    {
        while ($this->endWithSlash($path)) {
            $path = substr($path, 0, -1);
        }
        return $path;
    }

    function endWithSlash(string $path)
    {
        return preg_match("/\/$/", $path);
    }


}
