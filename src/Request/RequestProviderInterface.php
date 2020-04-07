<?php

namespace Warkhosh\Route\Request;

interface RequestProviderInterface
{
    /**
     * @return array
     */
    public function getRequestParts();
}