<?php

namespace App\Service;

class DateTimeService
{
    /**
     * @return \DateTime
     */
    public static function dateTime(): \DateTime
    {
        return new \DateTime();
    }
}
