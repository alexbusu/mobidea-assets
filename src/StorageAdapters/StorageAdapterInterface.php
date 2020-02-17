<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use Ola\Assets\Asset;

interface StorageAdapterInterface
{
    public function asset(string $name): Asset;

    public function sendToClient(Asset $asset);

    public function persist(Asset $asset, string $newPath = null): Asset;

    public function getSourcePath(Asset $asset): string;

    public function getResourceStream(Asset $asset);
}
