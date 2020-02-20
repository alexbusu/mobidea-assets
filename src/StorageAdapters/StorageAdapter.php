<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use Ola\Assets\Asset;
use SplObjectStorage;

abstract class StorageAdapter implements StorageAdapterInterface
{
    private $assetsCache;

    public function __construct()
    {
        $this->assetsCache = new SplObjectStorage();
    }

    public function asset(string $name): Asset
    {
        return new Asset($this, $name);
    }

    protected function setAssetCache(Asset $asset, $resource)
    {
        $this->assetsCache->offsetSet($asset, $resource);
    }

    protected function invalidateAssetCache(Asset $asset)
    {
        $this->assetsCache->offsetUnset($asset);
    }

    protected function getAssetResource(Asset $asset)
    {
        return $this->assetsCache->offsetExists($asset) ? $this->assetsCache->offsetGet($asset) : null;
    }
}
