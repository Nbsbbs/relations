<?php

declare(strict_types=1);

namespace App\Service;

class DateBagHolder
{
    /**
     * @var array
     */
    private array $dates = [];

    /**
     * @param string $id
     * @param \DateTimeInterface $dateTime
     *
     * @return void
     */
    public function push(string $id, \DateTimeInterface $dateTime): void
    {
        if (array_key_exists($id, $this->dates)) {
            if ($dateTime < $this->dates[$id]) {
                $this->dates[$id] = $dateTime;
            }
        } else {
            $this->dates[$id] = $dateTime;
        }
    }

    /**
     * @param string $id
     *
     * @return \DateTimeInterface|null
     */
    public function get(string $id): ?\DateTimeInterface
    {
        if (array_key_exists($id, $this->dates)) {
            return $this->dates[$id];
        } else {
            return null;
        }
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->dates = [];
    }
}
