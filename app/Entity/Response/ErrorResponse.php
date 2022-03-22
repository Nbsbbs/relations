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
     * @param string $message
     */
    public function __construct(int $code, string $message)
    {
        $this->message = $message;
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
}
