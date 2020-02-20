<?php

namespace Ola\Assets;

use Ola\Assets\StorageAdapters\StorageAdapter;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Asset
{
    /** @var string */
    private $path;
    /**
     * @var StorageAdapter
     */
    private $storage;

    public function __construct(StorageAdapter $handler, string $path)
    {
        $this->storage = $handler;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function sendToClient(
        string $filename = '',
        string $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT
    ) {
        $this->storage->sendToClient($this, $disposition, $filename);
    }

    public function persist(string $newPath = null): self
    {
        return $this->storage->persist($this, $newPath);
    }

    public function getSourcePath()
    {
        return $this->storage->getSourcePath($this);
    }

    public function getResourceStream()
    {
        return $this->storage->getResourceStream($this);
    }

    public function getContents()
    {
        try {
            $stream = $this->getResourceStream();
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    public function delete()
    {
        $this->storage->delete($this);
    }

    public function exists(): bool
    {
        return $this->storage->exists($this);
    }
}
