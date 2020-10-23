<?php


namespace io\flexio\utils\http;


class HeadersParameters
{
    private $X_Account = null;
    private $Authorization_Bearer = null;
    private $flexContext = null;

    public function flexContext(string $flexContext): HeadersParameters
    {
        $this->flexContext = $flexContext;
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


    public function toArrayHeaders(): array
    {
        $headers = [];
        if ($this->X_Account !== null) {
            $headers[] = "X-account: " . $this->X_Account;
        }
        if ($this->Authorization_Bearer !== null) {
            $headers[] = "Authorization: Bearer " . $this->Authorization_Bearer;
        }
        if ($this->flexContext !== null) {
            $headers[] = "flex-context: " . $this->flexContext;
        }
        return $headers;
    }
}