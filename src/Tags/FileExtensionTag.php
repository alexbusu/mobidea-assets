<?php

namespace Ola\Assets\Tags;

class FileExtensionTag implements TagInterface
{
    /**
     * @var string
     */
    private $extension;

    /**
     * FileExtensionTag constructor.
     * @param string $extension
     */
    public function __construct(string $extension)
    {
        $this->extension = $extension;
    }

    public function __invoke(): string
    {
        return "ext-{$this->extension}";
    }
}
