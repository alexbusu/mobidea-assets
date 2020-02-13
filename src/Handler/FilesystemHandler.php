<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\Handler;

use Ola\Assets\Asset;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FilesystemHandler extends AssetsAbstractHandler
{
    private $assetsType = '';
    /**
     * @var string
     */
    private $basepath;

    public function __construct(string $assetsType, string $basepath)
    {
        $this->assetsType = $assetsType;
        $this->basepath = rtrim($basepath, DIRECTORY_SEPARATOR) ?: DIRECTORY_SEPARATOR;
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
         * First argument of copy is result of {@see Asset::getSourcePath()}
         *      since we want the original asset handler's path
         * The 2nd argument of copy is result of {@see AssetsAbstractHandler::getSourcePath()}
         *      since we want _this_ asset handler's path
         * @todo make operation to work with streams, so that the copy can be possible from handlers like Aws' S3
         */
        if (!copy($asset->getSourcePath(), $this->getSourcePath($newAsset))) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new \LogicException('could not persist (copy) the asset');
        }
        return $newAsset;
    }

    public function getSourcePath(Asset $asset): string
    {
        return $this->basepath . DIRECTORY_SEPARATOR . ltrim($asset->getPath(), '\\/');
    }
}
