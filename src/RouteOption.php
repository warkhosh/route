<?php

namespace Warkhosh\Route;

class RouteOption
{
    const SIGNAL_CONTINUE = 2000;
    const SIGNAL_COMPLETED = 2010;
    const SIGNAL_IGNORE_PROCESS = 2020;

    const SIGNAL_STOP = 3000;

    /**
     * Список сигналов для проверки на допустимость
     *
     * @var array
     */
    public static $signals = [
        self::SIGNAL_CONTINUE,
        self::SIGNAL_STOP,
    ];
}