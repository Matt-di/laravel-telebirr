<?php

namespace Telebirr\LaravelTelebirr\Exceptions;

use Exception;

class TelebirrException extends Exception
{
    /**
     * The error code from Telebirr API.
     *
     * @var string|null
     */
    protected $telebirrCode;

    /**
     * Additional context data.
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Create a new exception instance.
     *
     * @param string $message
     * @param string|null $telebirrCode
     * @param array $context
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        ?string $telebirrCode = null,
        array $context = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->telebirrCode = $telebirrCode;
        $this->context = $context;
    }

    /**
     * Get the Telebirr error code.
     *
     * @return string|null
     */
    public function getTelebirrCode(): ?string
    {
        return $this->telebirrCode;
    }

    /**
     * Get the context data.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Convert the exception to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'telebirr_code' => $this->telebirrCode,
            'context' => $this->context,
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
