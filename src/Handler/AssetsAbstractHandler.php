<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\Handler;

use Ola\Assets\Asset;
use SplObjectStorage;

abstract class AssetsAbstractHandler implements AssetsHandlerInterface
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
        return $this->assetResources->offsetGet($asset);
    }
}
