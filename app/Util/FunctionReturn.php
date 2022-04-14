<?php

namespace App\Util;

class FunctionReturn
{
    private bool $status;

    private string $message;

    private array $data;


    /**
     * HttpResponse constructor.
     * @param bool $status
     * @param string|null $message
     * @param array|null $data
     */
    public function __construct(bool $status, ?string $message, ?array $data)
    {
        $this->status = $status;
        $this->message = $message === null ? '未知错误' : $message;
        $this->data = $data === null ? [] : $data;
    }

    /**
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @param string|null $field
     * @return mixed
     */
    public function getData(string $field = null): mixed
    {
        if ($field !== null && isset($this->data[$field])) {
            return $this->data[$field];
        }
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }


    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
