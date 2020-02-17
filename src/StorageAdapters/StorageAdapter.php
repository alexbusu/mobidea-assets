<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use Ola\Assets\Asset;
use SplObjectStorage;

abstract class StorageAdapter implements StorageAdapterInterface
{
    private $assetResources;

    public function __construct()
    {
        $this->assetResources = new SplObjectStorage();
    }

    public function asset(string $name): Asset
    {
        return new Asset($this, $name);
    }

    protected function setAssetResource(Asset $asset, $resource)
    {
        $this->assetResources->offsetSet($asset, $resource);
    }

    protected function getAssetResource(Asset $asset)
    {
        return $this->assetResources->offsetExists($asset) ? $this->assetResources->offsetGet($asset) : null;
    }
}
