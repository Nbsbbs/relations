<?php

namespace App\Entity\Response;

interface ServiceResponseInterface
{
    /**
     * @return bool
     */
    public function isError(): bool;

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string;

    /**
     * @return int|null
     */
    public function getErrorCode(): ?int;
}
