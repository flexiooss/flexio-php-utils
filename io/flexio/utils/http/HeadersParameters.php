<?php


namespace io\flexio\utils\http;


class HeadersParameters
{
    private $X_Account = null;
    private $Authorization_Bearer = null;
    private $flexContext = null;
    private $requestId = null;
    private $X_current_flexapp_id = null;
    private $headers = [];

    public function flexContext(string $flexContext): HeadersParameters
    {
        $this->flexContext = $flexContext;
        return $this;
    }

    public function currentFlexappId(string $value): HeadersParameters
    {
        $this->X_current_flexapp_id = $value;
        return $this;
    }

    public function requestId(string $value): HeadersParameters
    {
        $this->requestId = $value;
        return $this;
    }

    public function add_X_Account(string $account): HeadersParameters
    {
        $this->X_Account = $account;
        return $this;
    }

    public function add_Authorization_Bearer(string $token): HeadersParameters
    {
        $this->Authorization_Bearer = $token;
        return $this;
    }

    public function set(string $key, string $value): HeadersParameters
    {
        $this->headers[$key] = $value;
        return $this;
    }


    public function toArrayHeaders(): array
    {
        $headers = [];
        foreach ($this->headers as $k => $v) {
            $headers[] = $k . ": " . $v;
        }

        if ($this->X_Account !== null) {
            $headers[] = "X-account: " . $this->X_Account;
        }
        if ($this->Authorization_Bearer !== null) {
            $headers[] = "Authorization: Bearer " . $this->Authorization_Bearer;
        }
        if ($this->flexContext !== null) {
            $headers[] = "flex-context: " . $this->flexContext;
        }
        if ($this->requestId !== null) {
            $headers[] = "x-request-id: " . $this->requestId;
        }
        if ($this->X_current_flexapp_id !== null) {
            $headers[] = "X-current-flexapp-id: " . $this->X_current_flexapp_id;
        }
        return $headers;
    }
}