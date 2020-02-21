<?php
/**
 * Copyright (c) 2020 www.olamobile.com
 */

namespace Ola\Assets\StorageAdapters;

use Exception;
use Ola\Assets\Asset;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

interface StorageAdapterInterface
{
    public function asset(string $name): Asset;

    /**
     * @param Asset $asset
     * @param string $disposition One of {@see ResponseHeaderBag::DISPOSITION_ATTACHMENT}
     *  or {@see ResponseHeaderBag::DISPOSITION_INLINE}
     * @param string $filename
     * @return mixed
     */
    public function sendToClient(Asset $asset, string $disposition = '', string $filename = '');

    /**
     * @param Asset $asset
     * @param string|null $newPath
     * @param resource|null $newContent
     * @return Asset
     */
    public function persist(Asset $asset, string $newPath = null, $newContent = null): Asset;

    /**
     * @param Asset $asset
     * @return void
     * @throws Exception
     */
    public function delete(Asset $asset);

    public function getSourcePath(Asset $asset): string;

    public function getResourceStream(Asset $asset);

    public function exists(Asset $asset): bool;
}
