<?php

namespace Ola\Assets\Tags;

class HistoryIdTag implements TagInterface
{
    /** @var int */
    private $historyId;

    public function __construct(int $historyId)
    {
        $this->historyId = $historyId;
    }

    public function __invoke(): string
    {
        return "history-{$this->historyId}";
    }
}
