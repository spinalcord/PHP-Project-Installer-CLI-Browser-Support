<?php

namespace Core;
interface InstallControllerInterface
{
    public function getPriority() : int;
    public function taskName() : string;
    public function initialize();
    public function getFields() : array;
    public function nextTriggered(array $postValues);
    public function backTriggered(array $postValues);
    public function resetTriggered();
}