<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use ArrayObject;
use RuntimeException;

abstract class StorageAdapter implements StorageAdapterInterface
{
    private $assetsCache;

    public function __construct()
    {
        $this->assetsCache = new ArrayObject();
    }

    protected function setAssetCache(string $filepath, $resource)
    {
        $this->assetsCache->offsetSet($filepath, $resource);
    }

    protected function invalidateAssetCache(string $filepath)
    {
        $this->assetsCache->offsetUnset($filepath);
    }

    protected function getAssetResource(string $filepath)
    {
        return $this->assetsCache->offsetExists($filepath) ? $this->assetsCache->offsetGet($filepath) : null;
    }

    public function getContents(string $filepath): string
    {
        try {
            $stream = $this->getResourceStream($filepath);
            $contents = stream_get_contents($stream);
            if ($contents === false) {
                /** @noinspection PhpUnhandledExceptionInspection */
                throw new RuntimeException('cannot read from stream: ' . $filepath);
            }
            return $contents;
        } finally {
            fclose($stream);
        }
    }
}
