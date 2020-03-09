<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use Aws\Result as AwsResult;
use Aws\S3\S3ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Stream as GuzzleHttpStream;
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

    public function sendToClient(string $filepath, string $disposition = '', string $filename = '')
    {
        $object = $this->getRemoteObject($filepath);
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
            $disposition ?: ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename ?: basename($filepath)
        );
        $response->headers->set('Content-Length', $stream->getSize());
        $response->headers->set('Content-Type', $object->get('ContentType'));
        $response->headers->set('Content-Disposition', $disposition);
        $response->send();
    }

    /**
     * @param string $filepath
     * @param resource|string $contentStream
     * @return bool
     * @throws UnexpectedValueException
     */
    public function persist(string $filepath, $contentStream): bool
    {
        $object = $this->putRemoteObject($contentStream, $filepath);
        if (($code = $object->get('@metadata')['statusCode'] ?? null) !== 200) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new UnexpectedValueException("could not put the object; status code: {$code}");
        }
        return true;
    }

    /**
     * @param string $filepath
     * @return string
     * @noinspection PhpDocMissingThrowsInspection
     * @internal
     */
    public function getSourcePath(string $filepath): string
    {
        $object = $this->getRemoteObject($filepath);
        if (!($path = $object->get('@metadata')['effectiveUri'] ?? '')) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new UnexpectedValueException('could not fetch the source path');
        }
        return $path;
    }

    /**
     * @param string $filepath
     * @return AwsResult
     */
    private function getRemoteObject(string $filepath): AwsResult
    {
        if ($cached = $this->getAssetResource($filepath)) {
            return $cached;
        }
        $object = $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $filepath,
        ]);
        $this->setAssetCache($filepath, $object);
        return $object;
    }

    /**
     * @param resource|string $stream
     * @param string $path
     * @return AwsResult
     */
    private function putRemoteObject($stream, string $path): AwsResult
    {
        return $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
            'Body' => $stream,
        ]);
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function getResourceStream(string $filepath)
    {
        $object = $this->getRemoteObject($filepath);
        /** @var GuzzleHttpStream $assetStream */
        $assetStream = $object->get('Body');
        $stream = fopen('php://temp', 'r+');
        $assetStream->rewind();
        fwrite($stream, $assetStream->getContents());
        fseek($stream, 0, SEEK_SET);
        return $stream;
    }

    public function delete(string $filepath)
    {
        $result = $this->s3Client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $filepath,
        ]);
        if (($code = $result->get('@metadata')['statusCode'] ?? null) !== 200) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new UnexpectedValueException("could not delete the object; status code: {$code}");
        }
    }

    public function exists(string $filepath): bool
    {
        return $this->s3Client->doesObjectExist($this->bucket, $filepath);
    }
}
