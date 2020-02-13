<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\Handler;

use Ola\Assets\Asset;

abstract class AssetsAbstractHandler implements AssetsHandlerInterface
{
    public function asset(string $name): Asset
    {
        return new Asset($this, $name);
    }
}
