<?php

namespace InstallControllers;

use Core\InstallControllerInterface;
use Core\InstallFeedback;

class SampleInstallController implements InstallControllerInterface
{
    public function getPriority(): int
    {
        return 2;
    }

    public function initialize()
    {
        // Optional: Code to execute *after* the page is rendered.
    }

    public function getFields(): array
    {
        return [
            'db_host' => [
                'type' => 'text',
                'label' => 'Database Host',
                'value' => 'localhost',
                'required' => true
            ],
            'db_name' => [
                'type' => 'text',
                'label' => 'Database Name',
                'value' => '',
                'required' => true
            ],
            'db_user' => [
                'type' => 'text',
                'label' => 'Database User',
                'value' => '',
                'required' => true
            ],
            'db_password' => [  // Added password field
                'type' => 'text',
                'label' => 'Database Password',
                'value' => '',
                'required' => false // Make password optional for this demo
            ],
            'accept_terms' => [
                'type' => 'checkbox',
                'label' => 'I accept the terms and conditions',
                'value' => false,
                'required' => true
            ],
            'fav_letter' => [
                'type' => 'select',
                'label' => 'Select your favorite letter:',
                'value' => 'b',
                'options' => ['a' , 'b' , 'c' ],
                'required' => true
            ],
            'some_info' => [
                'type' => 'info',
                'label' => 'Important Information',
                'value' => 'This is some important information for the user.'
            ]
        ];
    }

    public function taskName(): string
    {
        return 'Database Setup';
    }

    public function resetTriggered()
    {
        // Handle reset actions.
    }

    public function nextTriggered(array $postValues)
    {
        // Validate input.
        if (empty($postValues['db_host'])) {
            return new InstallFeedback('error', 'Database host is required.');
        }
        if (empty($postValues['db_name'])) {
            return new InstallFeedback('error', 'Database name is required.');
        }
        if (empty($postValues['db_user'])) {
            return new InstallFeedback('error', 'Database user is required.');
        }
        if (empty($postValues['accept_terms']) || $postValues['accept_terms'] != true ) { // Checkbox validation.  Must be '1' (checked).
            return new InstallFeedback('error', 'You must accept the terms and conditions.');
        }
        if(empty($postValues['fav_letter'])){
            return new InstallFeedback('error', 'You must choose your favorite letter.');
        }


        // --- Save to file ---
        $filePath = __DIR__ . '/../exampleValues.env';  // File in the parent directory of "InstallControllers"

        ob_start();
        var_dump($_POST);
        $output = ob_get_clean();

        // Write to the file.  Error handling included.
        if (file_put_contents($filePath, $output) === false) {
            return new InstallFeedback('error', 'Failed to write to configuration file.');
        }

        // Success!  Return nothing to proceed.
    }

    public function backTriggered(array $postValues)
    {
        // Handle "Back" button press.
    }
}