<?php

namespace Ola\Assets;

use Ola\Assets\Handler\AssetsAbstractHandler;

class Asset
{
    /** @var string */
    private $path;
    /**
     * @var AssetsAbstractHandler
     */
    private $handler;

    public function __construct(AssetsAbstractHandler $handler, string $path)
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
}
