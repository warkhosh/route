<?php

namespace Warkhosh\Route;

class Option
{
    const SIGNAL_CONTINUE = 2000;
    const SIGNAL_COMPLETED = 2010;

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