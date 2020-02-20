<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use Aws\Result as AwsResult;
use Aws\S3\S3ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Stream as GuzzleHttpStream;
use LogicException;
use Ola\Assets\Asset;
use RuntimeException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnexpectedValueException;

class AwsS3Storage extends StorageAdapter
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
        parent::__construct();
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
        $newAsset = $this->asset($newPath ?: $asset->getPath());
        $object = $this->putRemoteObject($asset->getResourceStream(), $newAsset->getPath());
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
    private function getRemoteObject(Asset $asset): AwsResult
    {
        if ($cached = $this->getAssetResource($asset)) {
            return $cached;
        }
        $object = $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $asset->getPath(),
        ]);
        $this->setAssetCache($asset, $object);
        return $object;
    }

    /**
     * @param resource $stream
     * @param string $remotePath
     * @return AwsResult
     */
    private function putRemoteObject($stream, string $remotePath): AwsResult
    {
        return $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $remotePath,
            'Body' => $stream,
        ]);
    }

    /** @noinspection PhpUnhandledExceptionInspection */

    public function getResourceStream(Asset $asset)
    {
        $object = $this->getRemoteObject($asset);
        /** @var GuzzleHttpStream $assetStream */
        $assetStream = $object->get('Body');
        $stream = fopen('php://temp', 'r+');
        $assetStream->rewind();
        fwrite($stream, $assetStream->getContents());
        fseek($stream, 0, SEEK_SET);
        return $stream;
    }

    public function delete(Asset $asset)
    {
        $result = $this->s3Client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $asset->getPath(),
        ]);
        if (($code = $result->get('@metadata')['statusCode'] ?? null) !== 200) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new UnexpectedValueException("could not delete the object; status code: {$code}");
        }
    }
}
