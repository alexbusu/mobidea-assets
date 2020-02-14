<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\Handler;

use LogicException;
use Ola\Assets\Asset;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FilesystemHandler extends AssetsAbstractHandler
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
        $newAsset = $newPath ? $this->asset($newPath) : clone $asset;
        /**
         * Use result of {@see AssetsAbstractHandler::getSourcePath()}
         *  instead of {@see Asset::getSourcePath()}
         *  since we want _this_ handler's path for $asset.
         */
        $targetPath = $this->getSourcePath($newAsset);
        if (($targetStream = fopen($targetPath, 'w+')) === false) {
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
}
