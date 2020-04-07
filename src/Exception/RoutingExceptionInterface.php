<?php

namespace Warkhosh\Exception;

interface RoutingExceptionInterface
{
    /**
     * @return int
     */
    public function getHttpCode();
}