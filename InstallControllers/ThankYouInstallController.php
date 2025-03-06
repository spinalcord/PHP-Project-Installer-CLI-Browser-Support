<?php

namespace InstallControllers;

use Core\InstallControllerInterface;
use Core\InstallFeedback;
use Core\Response;

class ThankYouInstallController implements InstallControllerInterface
{
    public function getPriority(): int
    {
        return 99;
    }

    public function initialize()
    {
        // Optional: Code to execute *after* the page is rendered.
    }

    public function getFields(): array
    {
        return [
            'db_host' => [
                'type' => 'info',
                'value' => 'If you press complete you will be directed to github.com. You can implement your logic in nextTriggered() to decide what to do.',
                'required' => true
            ]
        ];
    }

    public function taskName(): string
    {
        return 'Thank you for installing this Product';
    }

    public function resetTriggered()
    {
        // Handle reset actions.
    }

    public function nextTriggered(array $postValues)
    {
        // rename the installation folder
        // Or do something else like a reroute
        (new Response())->reroute('http://github.com');
    }

    public function backTriggered(array $postValues)
    {
        // Handle "Back" button press.
    }
}