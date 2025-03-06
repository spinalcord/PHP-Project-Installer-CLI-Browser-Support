<?php

namespace InstallControllers;

use Core\InstallControllerInterface;
use Core\InstallFeedback;
use Core\Response;
use Exception;

class WelcomeInstallController implements InstallControllerInterface
{

    /**
     * @var array
     */
    private $installValues;

    public function getPriority() : int
    {
        return 1;
    }

    public function initialize()
    {
        error_log('Starting installation');
    }
    public function getFields(): array
    {

        return [
            'test' => [
                'type' => 'info',
                'value' => 'Welcome :)'
            ],
            'db_host' =>
                [
                'type' => 'select',
                'label' => 'Database-Host',
                'value' => 'a',
                'options' => ['a','b','c','d'],
            ],
            'db_name' => [
                'type' => 'text',
                'label' => 'Database-Name',
                'value' => 'localhost'
            ],
            'asdf' => [
                'type' => 'checkbox',
                'label' => 'allow something',
                'value' => true
            ]
        ];
    }

    public function taskName(): string
    {
        return 'Welcome';
    }

    public function resetTriggered()
    {
        echo 'test reset';
    }

    public function nextTriggered(array $postValues)
    {
        // return new InstallFeedback('error',"hey no");
    }


    public function backTriggered(array $postValues)
    {
        // TODO: Implement backTriggered() method.
    }
}