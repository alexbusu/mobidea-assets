<?php

namespace Ola\Assets\Tags;

interface TagInterface
{
    public function __invoke(): string;
}
