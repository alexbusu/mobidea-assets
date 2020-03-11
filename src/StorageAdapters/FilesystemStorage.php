<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FilesystemStorage extends StorageAdapter
{
    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR) ?: DIRECTORY_SEPARATOR;
        parent::__construct();
    }

    public function sendToClient(
        string $filepath,
        string $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        string $filename = ''
    ) {
        /** @noinspection PhpUnhandledExceptionInspection */
        $stream = new Stream($this->getSourcePath($filepath));
        $response = new BinaryFileResponse($stream);
        $response->headers->set('Content-Type', $stream->getMimeType());
        $response->setContentDisposition(
            $disposition ?: ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename ?: basename($filepath)
        );
        $response->send();
    }

    /**
     * @param string $filepath
     * @param resource $contentStream
     * @return true
     * @throws LogicException
     */
    public function persist(string $filepath, $contentStream): bool
    {
        $targetPath = $this->getSourcePath($filepath);
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir) && !mkdir($targetDir, 755, true)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new LogicException("could not create target directory [{$targetDir}]");
        }
        if (($targetStream = fopen($targetPath, 'w+')) === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new LogicException("could not open path [{$targetPath}]");
        }
        $sourceStream = $contentStream;
        if (stream_copy_to_stream($sourceStream, $targetStream) === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new LogicException('could not persist (copy) the asset');
        }
        fclose($sourceStream);
        fclose($targetStream);
        return true;
    }

    /**
     * @param string $filepath
     * @return string
     * @internal
     */
    public function getSourcePath(string $filepath): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($filepath, '\\/');
    }

    public function getResourceStream(string $filepath)
    {
        return fopen($this->getSourcePath($filepath), 'r');
    }

    public function delete(string $filepath)
    {
        $filepath = $this->getSourcePath($filepath);
        if (!is_file($filepath)) {
            throw new LogicException("the file does not exist: $filepath");
        }
        if (!unlink($filepath)) {
            throw new RuntimeException("the file cannot be deleted: $filepath");
        }
    }

    public function exists(string $filepath): bool
    {
        return is_file($this->getSourcePath($filepath));
    }
}
