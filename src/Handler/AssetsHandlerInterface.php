<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\Handler;

use Ola\Assets\Asset;

interface AssetsHandlerInterface
{
    public function asset(string $name): Asset;

    public function sendToClient(Asset $asset);

    public function persist(Asset $asset): Asset;

    public function getSourcePath(Asset $asset): string;
}
