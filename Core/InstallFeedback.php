<?php

namespace Core;

class InstallFeedback
{
    /**
     * @var string
     */
    private $feedBackType;
    private $feedBackValue;

    function __construct(string $feedBackType, $feedBackValue)
    {
        $this->feedBackType = $feedBackType;
        $this->feedBackValue = $feedBackValue;
    }

    public function getFeedBackType(): string
    {
        return $this->feedBackType;
    }

    /**
     * @return mixed
     */
    public function getFeedBackValue()
    {
        return $this->feedBackValue;
    }
}