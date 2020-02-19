<?php

namespace Ola\Assets;

use Ola\Assets\StorageAdapters\StorageAdapter;

class Asset
{
    /** @var string */
    private $path;
    /**
     * @var StorageAdapter
     */
    private $handler;

    public function __construct(StorageAdapter $handler, string $path)
    {
        $this->handler = $handler;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function sendToClient()
    {
        $this->handler->sendToClient($this);
    }

    public function persist(string $newPath = null): self
    {
        return $this->handler->persist($this, $newPath);
    }

    public function getSourcePath()
    {
        return $this->handler->getSourcePath($this);
    }

    public function getResourceStream()
    {
        return $this->handler->getResourceStream($this);
    }

    public function getContents()
    {
        $stream = $this->getResourceStream();
        return stream_get_contents($stream); // $stream gets automatically closed by PHP
    }
}
