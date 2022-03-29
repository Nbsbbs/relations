<?php

namespace App\Entity\Response;

class ErrorResponse implements ServiceResponseInterface
{
    /**
     * @var int
     */
    private int $code;

    /**
     * @var string
     */
    private string $message;

    /**
     * @param int $code
     * @param string $message
     */
    public function __construct(int $code, string $message)
    {
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return true;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return int|null
     */
    public function getErrorCode(): ?int
    {
        return $this->code;
    }
}
