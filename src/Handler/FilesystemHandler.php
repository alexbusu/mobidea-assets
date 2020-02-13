<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\Handler;

use Ola\Assets\Asset;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\Response;
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
        $stream = new Stream($this->getFilepath($asset));
        $response = new BinaryFileResponse($stream);
        $response->headers->set('Content-Type', $stream->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($asset->getPath())
        );
        $response->send();
    }

    private function getFilepath(Asset $asset): string
    {
        return $this->basepath . DIRECTORY_SEPARATOR . ltrim($asset->getPath(), '\\/');
    }
}
