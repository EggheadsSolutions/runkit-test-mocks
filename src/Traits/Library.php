<?php // phpcs:ignore

namespace Eggheads\Mocks\Traits;

trait Library
{
    /**
     * Защищаем от создания через new
     */
    private function __construct()
    {
    }

    /**
     * Защищаем от создания через клонирование
     */
    private function __clone()
    {
    }
}
