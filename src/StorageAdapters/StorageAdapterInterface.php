<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use Exception;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

interface StorageAdapterInterface
{
    /**
     * @param string $filepath
     * @param string $disposition One of {@see ResponseHeaderBag::DISPOSITION_ATTACHMENT}
     *  or {@see ResponseHeaderBag::DISPOSITION_INLINE}
     * @param string $filename
     * @return void
     */
    public function sendToClient(
        string $filepath,
        string $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        string $filename = ''
    );

    /**
     * @param string $filepath
     * @param resource $contentStream
     * @return bool
     */
    public function persist(string $filepath, $contentStream): bool;

    /**
     * @param string $filepath
     * @return void
     * @throws Exception
     */
    public function delete(string $filepath);

    /**
     * @param string $filepath
     * @return string
     * @internal
     */
    public function getSourcePath(string $filepath): string;

    /**
     * @param string $filepath
     * @return resource
     */
    public function getResourceStream(string $filepath);

    public function getContents(string $filepath): string;

    public function exists(string $filepath): bool;
}
