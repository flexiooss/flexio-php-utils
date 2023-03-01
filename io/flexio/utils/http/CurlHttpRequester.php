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
    private $totalMaxRetry = 5;
    private $retryStrategy = [
        0 => [2, 5],
        500 => [5, 5]
    ];

    /**
     * @param HeadersParameters $headersParams
     * @param callable|null $logger - function(string):void
     */
    public function __construct(HeadersParameters $headersParams, callable $logger = null)
    {
        $this->headersParams = $headersParams;
        $this->client = curl_init();
        $this->init();
        $this->logger = $logger;
    }

    public function setTotalMaxRetry(int $totalMaxRetry): CurlHttpRequester
    {
        $this->totalMaxRetry = $totalMaxRetry;
        return $this;
    }

    /**
     *
     * @param int $code - HTTP return code
     * @param int[] $in - define the number of attempts and the waiting time (sec.) between each one
     * @return $this
     */
    public function codeRetryStrategy(int $code, array $in): CurlHttpRequester
    {
        $v = $this->retryStrategy;
        $v[$code] = $in;
        $this->retryStrategy = $v;
        return $this;
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
        if (gettype($this->client) === 'resource') {
            curl_close($this->client);
        }
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

    /**
     * @throws ZeroCodeException
     * @throws HttpIOException
     */
    private function requestWithoutPayload(string $method): ResponseDelegate
    {

        $query = http_build_query($this->requestParameters);

        $exe = function ($retry) use ($method, $query) {
            $this
                ->debug('requestWithoutPayload')
                ->debug('retry::' . $retry)
                ->debug('method::' . $method)
                ->debug('path::' . $this->path . '?' . $query)
                ->debug('headers::' . json_encode($this->responseHeaders));

            curl_setopt($this->client, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($this->client, CURLOPT_URL, $this->path . '?' . $query);
            curl_setopt($this->client, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->client, CURLOPT_HTTPHEADER, $this->requestHeaders);

            $this
                ->buildLastStatus($this->exec())
                ->reset();
        };

        $this
            ->retryStrategyExecutor($exe)
            ->handleResultStatus($method, $this->path . '?' . $query);

        return new CurlResponseDelegate(
            $this->lastStatus()->getCode(),
            $this->lastStatus()->getBody(),
            $this->lastStatus()->getHeaders());
    }

    private function retryStrategyExecutor(callable $clb): CurlHttpRequester
    {
        $retry = 0;
        $retryStrat = $this->retryStrategy;
        do {
            $error = false;
            $clb($retry);
            if ($this->lastStatus()->getError() !== '' || $this->lastStatus()->getCode() === 0) {
                if (!isset($retryStrat[0])) continue;
                $strat = $retryStrat[0];
                if (!count($strat)) continue;
                $wait = array_shift($strat);
                $retryStrat[0] = $strat;
                if (!is_numeric($wait)) continue;
                sleep($wait);
                $error = true;
            } elseif (isset($retryStrat[$this->lastStatus()->getCode()])) {
                $strat = $retryStrat[$this->lastStatus()->getCode()];
                if (!count($strat)) continue;
                $wait = array_shift($strat);
                $retryStrat[$this->lastStatus()->getCode()] = $strat;
                if (!is_numeric($wait)) continue;
                sleep($wait);
                $error = true;
            }

            $retry++;
        } while ($error && $retry <= $this->totalMaxRetry);

        return $this;
    }

    /**
     * @throws ZeroCodeException
     * @throws HttpIOException
     */
    private function requestWithPayload(string $body, string $contentType, string $method): ResponseDelegate
    {
        $exe = function ($retry) use ($method, $body, $contentType) {
            $this
                ->debug('requestWithPayload')
                ->debug('retry::' . $retry)
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

            $this
                ->buildLastStatus($this->exec())
                ->reset();
        };

        $this
            ->retryStrategyExecutor($exe)
            ->handleResultStatus($method, $this->path);

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

    /**
     * @throws ZeroCodeException
     * @throws HttpIOException
     */
    public function get(): ResponseDelegate
    {
        return $this->requestWithoutPayload('GET');
    }

    /**
     * @throws HttpIOException
     * @throws ZeroCodeException
     */
    public function post(string $contentType = null, string $body = null): ResponseDelegate
    {
        return $this->requestWithPayload($body != null ? $body : "", $contentType != null ? $contentType : 'application/json', 'POST');
    }

    /**
     * @throws HttpIOException
     * @throws ZeroCodeException
     */
    public function put(string $contentType = null, string $body = null): ResponseDelegate
    {
        return $this->requestWithPayload($body != null ? $body : "", $contentType != null ? $contentType : 'application/json', 'PUT');
    }

    /**
     * @throws HttpIOException
     * @throws ZeroCodeException
     */
    public function patch(string $contentType = null, string $body = null): ResponseDelegate
    {
        return $this->requestWithPayload($body != null ? $body : "", $contentType != null ? $contentType : 'application/json', 'PATCH');
    }

    /**
     * @throws HttpIOException
     * @throws ZeroCodeException
     */
    public function delete(): ResponseDelegate
    {
        return $this->requestWithoutPayload('DELETE');
    }

    /**
     * @throws HttpIOException
     * @throws ZeroCodeException
     */
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

    /**
     * @throws HttpIOException
     * @throws ZeroCodeException
     */
    private function handleResultStatus(string $method, string $path): CurlHttpRequester
    {
        if ($this->lastStatus()->getError() !== '') {
            throw new HttpIOException('REQUEST FAIL, for:' . $method . ':' . $path . ' details:' . $this->lastStatus()->verboseOnSerialize()->__toString());
        }

        if ($this->lastStatus()->getCode() === 0) {
            throw new ZeroCodeException('status code 0, for:' . $method . ':' . $path . ' details:' . $this->lastStatus()->verboseOnSerialize()->__toString());
        }

        $this->debug('[EXECUTION] for:' . $method . ':' . $path . ' details:' . $this->lastStatus()->__toString());
        return $this;
    }
}
