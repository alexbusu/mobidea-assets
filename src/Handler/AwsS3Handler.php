<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\Handler;

use Aws\Result;
use Aws\Result as AwsResult;
use Aws\S3\S3ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Stream as GuzzleHttpStream;
use Ola\Assets\Asset;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnexpectedValueException;

class AwsS3Handler extends AssetsAbstractHandler
{
    /**
     * @var S3ClientInterface
     */
    private $s3Client;
    /**
     * @var string
     */
    private $bucket;

    public function __construct(S3ClientInterface $s3Client, string $bucket)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
    }

    public function sendToClient(Asset $asset)
    {
        $object = $this->getRemoteObject($asset);
        /** @var GuzzleHttpStream $stream */
        $stream = $object->get('Body');
        $response = new StreamedResponse(function () use (&$stream) {
            $streamSize = $stream->getSize();
            $source = $stream->detach();
            /**
             * Use php://output to write to the output buffer mechanism
             */
            $target = fopen('php://output', 'w');
            if (($copied = stream_copy_to_stream($source, $target)) === false
                || $copied != $streamSize
            ) {
                throw new TransferException('could not write to output buffer');
            }
        });
        /** @noinspection PhpUnhandledExceptionInspection */
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($asset->getPath())
        );
        $response->headers->set('Content-Length', $stream->getSize());
        $response->headers->set('Content-Type', $object->get('ContentType'));
        $response->headers->set('Content-Disposition', $disposition);
        $response->send();
    }

    public function persist(Asset $asset, string $newPath = null): Asset
    {
        $newAsset = $newPath ? $this->asset($newPath) : clone $asset;
        $object = $this->putRemoteObject($asset->getSourcePath(), $newAsset->getPath());
        /** @var Result $object */
        if (($code = $object->get('@metadata')['statusCode'] ?? null) !== 200) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new UnexpectedValueException("could not put the object; status code: {$code}");
        }
        return $newAsset;
    }

    public function getSourcePath(Asset $asset): string
    {
        $object = $this->getRemoteObject($asset);
        if (!($path = $object->get('@metadata')['effectiveUri'] ?? '')) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new UnexpectedValueException('could not fetch the source path');
        }
        return $path;
    }

    /**
     * @param Asset $asset
     * @return AwsResult
     */
    public function getRemoteObject(Asset $asset): AwsResult
    {
        return $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $asset->getPath(),
        ]);
    }

    private function putRemoteObject(string $sourcePath, string $remotePath): AwsResult
    {
        return $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $remotePath,
            'SourceFile' => $sourcePath,
        ]);
    }
}
