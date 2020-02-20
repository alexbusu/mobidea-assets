<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use LogicException;
use Ola\Assets\Asset;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FilesystemStorage extends StorageAdapter
{
    /** @var string */
    private $assetsType = '';
    /** @var string */
    private $basepath;

    public function __construct(string $assetsType, string $basepath)
    {
        $this->assetsType = $assetsType;
        $this->basepath = rtrim($basepath, DIRECTORY_SEPARATOR) ?: DIRECTORY_SEPARATOR;
        parent::__construct();
    }

    public function sendToClient(Asset $asset)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $stream = new Stream($this->getSourcePath($asset));
        $response = new BinaryFileResponse($stream);
        $response->headers->set('Content-Type', $stream->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($asset->getPath())
        );
        $response->send();
    }

    public function persist(Asset $asset, string $newPath = null): Asset
    {
        $newAsset = $this->asset($newPath ?? $asset->getPath());
        /**
         * Use result of {@see StorageAdapter::getSourcePath()}
         *  instead of {@see Asset::getSourcePath()}
         *  since we want _this_ handler's path for $asset.
         */
        $targetPath = $this->getSourcePath($newAsset);
        $targetDir = dirname($targetPath);
        if ((!is_dir($targetDir) && !mkdir($targetDir, 644, true))
            || ($targetStream = fopen($targetPath, 'w+')) === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new LogicException("could not open path [{$targetPath}]");
        }
        $sourceStream = $asset->getResourceStream();
        if (stream_copy_to_stream($sourceStream, $targetStream) === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new LogicException('could not persist (copy) the asset');
        }
        fclose($sourceStream);
        fclose($targetStream);
        return $newAsset;
    }

    public function getSourcePath(Asset $asset): string
    {
        return $this->basepath . DIRECTORY_SEPARATOR . ltrim($asset->getPath(), '\\/');
    }

    public function getResourceStream(Asset $asset)
    {
        return fopen($this->getSourcePath($asset), 'r');
    }

    public function delete(Asset $asset)
    {
        $filepath = $this->getSourcePath($asset);
        if (!is_file($filepath)) {
            throw new LogicException("the file does not exist: $filepath");
        }
        if (!unlink($filepath)) {
            throw new RuntimeException("the file cannot be deleted: $filepath");
        }
    }
}
