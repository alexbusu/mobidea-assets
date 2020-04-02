<?php

namespace Ola\Assets\Tags;

class AdvertiserIdTag implements TagInterface
{
    /** @var int */
    private $uid;

    public function __construct(int $uid)
    {
        $this->uid = $uid;
    }

    public function __invoke(): string
    {
        return "adv-{$this->uid}";
    }
}
