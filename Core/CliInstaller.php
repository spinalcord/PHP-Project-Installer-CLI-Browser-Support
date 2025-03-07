<?php

namespace Core;

use Core\InstallFeedback;
use Exception;

class CliInstaller
{
    private $sessionManager;
    private $maxAllowedPages = 20;
    private $currentPage = 1;

    public function __construct()
    {
        $this->sessionManager = new SessionManager();
    }

    /**
     * Runs the CLI installer
     *
     * @return void
     * @throws Exception If any installation step fails
     */
    public function run()
    {
        // Clear screen and display welcome message
        $this->clearScreen();
        echo "=== CLI Installer ===\n\n";

        try {
            // Get all controller files
            $files = glob(__DIR__ . "/../InstallControllers/*.php", GLOB_NOSORT);
            if ($files === false || count($files) > $this->maxAllowedPages) {
                throw new Exception("Error loading installation files or too many files detected.");
            }

            $controllers = $this->getSortedControllers($files);
            $totalPages = count($controllers);

            if ($totalPages == 0) {
                throw new Exception("No installation pages found.");
            }

            // Initialize session
            $this->sessionManager->setCurrentPage(1);

            // Process each page sequentially
            while ($this->currentPage <= $totalPages) {
                $pageIndex = $this->currentPage - 1;
                $controllerInfo = $controllers[$pageIndex];
                $fullClassName = "InstallControllers\\" . $controllerInfo['class'];

                if (!class_exists($fullClassName)) {
                    throw new Exception("Class $fullClassName does not exist.");
                }

                $installController = new $fullClassName();
                $installController->initialize();
                $taskName = $installController->taskName();
                $this->sessionManager->setCurrentController($fullClassName);

                // Display page header
                $this->clearScreen();
                echo "=== Step {$this->currentPage} of {$totalPages}: {$taskName} ===\n\n";

                // Get and process fields
                $fields = $installController->getFields();
                $fields = $this->loadSavedFieldValues($fields, $fullClassName);

                // Process this page until successful
                $success = false;
                while (!$success) {
                    try {
                        $postData = $this->processFields($fields);

                        // Confirm inputs
                        echo "\nPlease confirm your inputs (y/n): ";
                        $confirm = trim(fgets(STDIN));

                        if (strtolower($confirm) === 'y' || strtolower($confirm) === 'yes') {
                            // Process next step
                            $feedback = $installController->nextTriggered($postData);

                            if ($feedback instanceof InstallFeedback && $feedback->getFeedBackType() == 'error') {
                                echo "\n[ERROR] " . $feedback->getFeedBackValue() . "\n";
                                echo "Press Enter to try again...";
                                fgets(STDIN);
                            } else {
                                // Save values and move to next page
                                $this->sessionManager->setInstallValuesForController($fullClassName, $postData);
                                $this->currentPage++;
                                $success = true;
                            }
                        } else {
                            echo "\nRestarting this step...\n";
                            echo "Press Enter to continue...";
                            fgets(STDIN);
                        }
                    } catch (Exception $e) {
                        echo "\n[ERROR] " . $e->getMessage() . "\n";
                        echo "Press Enter to try again...";
                        fgets(STDIN);
                    }
                }
            }

            // Installation complete
            $this->clearScreen();
            echo "=== Installation Complete ===\n\n";
            echo "All installation steps have been completed successfully.\n";

            // Reset controllers if needed
            // $this->resetAllControllers();

            // Clear session data directly instead of using destroySession()
            $this->clearCliSession();

        } catch (Exception $e) {
            echo "\n[CRITICAL ERROR] " . $e->getMessage() . "\n";
            echo "Installation failed. Please try again.\n";
            exit(1);
        }
    }

    /**
     * Clears session data safely in CLI mode
     *
     * @return void
     */
    private function clearCliSession()
    {
        // Clear all session variables
        $_SESSION = [];

        // Don't attempt to set cookies in CLI mode
        // Just end the session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Process fields for a page and gather user input
     *
     * @param array $fields Fields array from controller
     * @return array User input data
     */
    private function processFields($fields)
    {
        $data = [];

        foreach ($fields as $key => $field) {
            switch ($field['type']) {
                case 'info':
                    echo $field['value'] . "\n\n";
                    break;

                case 'text':
                    $default = $field['value'] ?? '';
                    echo $field['label'] . " [" . $default . "]: ";
                    $input = trim(fgets(STDIN));
                    $data[$key] = empty($input) ? $default : $input;
                    break;
                case 'password':
                    $default = $field['value'] ?? '';
                    echo $field['label'] . " [" . ($default ? str_repeat("*", strlen($default)) : "") . "]: "; // Show asterisks for default

                    // Hide input using system command (if available)
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        // Windows: use PowerShell
                        $input = trim(shell_exec('powershell -Command "$password = Read-Host -AsSecureString; [System.Runtime.InteropServices.Marshal]::PtrToStringAuto([System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($password))"'));

                    } else {
                        // Linux/macOS/other Unix-like: use stty -echo
                        system('stty -echo');  // Disable echo
                        $input = trim(fgets(STDIN));
                        system('stty echo');  // Re-enable echo
                        echo "\n"; // Add a newline after the input (since echo was disabled)
                    }
                    $data[$key] = empty($input) ? $default : $input;
                    break;
                case 'email':
                    $default = $field['value'] ?? '';
                    echo $field['label'] . " [" . $default . "]: ";
                    $input = trim(fgets(STDIN));
    
                    // E-Mail-Validierung
                    if (empty($input)) {
                        $data[$key] = $default;
                    } else {
                        if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Ungültige E-Mail-Adresse. Bitte geben Sie eine gültige E-Mail-Adresse ein.");
                        }
                        $data[$key] = $input;
                    }
                    break;
                case 'checkbox':
                    $default = $field['value'] ? 'yes' : 'no';
                    echo $field['label'] . " (yes/no) [" . $default . "]: ";
                    $input = trim(strtolower(fgets(STDIN)));

                    if (empty($input)) {
                        $input = $default;
                    }

                    if (!in_array($input, ['yes', 'no', 'y', 'n'])) {
                        throw new Exception("Invalid input. Please enter 'yes' or 'no'.");
                    }

                    $data[$key] = ($input === 'yes' || $input === 'y');
                    break;

                case 'select':
                    $options = $field['options'];
                    $default = $field['value'] ?? $options[0];

                    echo $field['label'] . "\n";
                    foreach ($options as $index => $option) {
                        echo ($index + 1) . ") " . $option . "\n";
                    }

                    echo "Choose option [" . (array_search($default, $options) + 1) . "]: ";
                    $input = trim(fgets(STDIN));

                    if (empty($input)) {
                        $data[$key] = $default;
                    } else {
                        // Check if input is a number
                        if (is_numeric($input)) {
                            $index = (int)$input - 1;
                            if (isset($options[$index])) {
                                $data[$key] = $options[$index];
                            } else {
                                throw new Exception("Invalid option selected.");
                            }
                        } else {
                            // Check if input is a valid option
                            if (in_array($input, $options)) {
                                $data[$key] = $input;
                            } else {
                                throw new Exception("Invalid option selected.");
                            }
                        }
                    }
                    break;

                default:
                    throw new Exception("Unknown field type: " . $field['type']);
            }
        }

        return $data;
    }

    // [The rest of the methods remain the same]

    /**
     * Sorts controllers by priority (reused from Installer class)
     * @param array $files Array of file paths
     * @return array Sorted controllers
     * @throws Exception If controller class does not exist
     */
    private function getSortedControllers($files)
    {
        $controllers = [];
        foreach ($files as $file) {
            // Validate file path to prevent path traversal
            if (!$this->isValidFilePath($file)) {
                continue;
            }

            $class = pathinfo($file, PATHINFO_FILENAME);
            $fullClassName = "InstallControllers\\" . $class;

            if (class_exists($fullClassName)) {
                $installController = new $fullClassName();
                $controllers[] = [
                    'priority' => $installController->getPriority(),
                    'class' => $class
                ];
            } else {
                throw new Exception("Class $fullClassName does not exist.");
            }
        }

        // Sort controllers by priority
        usort($controllers, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return $controllers;
    }

    /**
     * Loads saved field values from session (reused from Installer class)
     *
     * @param array $fields Fields array
     * @param string $fullClassName Controller class name
     * @return array Updated fields with saved values
     */
    private function loadSavedFieldValues($fields, $fullClassName)
    {
        $currentController = $this->sessionManager->getCurrentController();
        if ($currentController) {
            $savedFieldValues = $this->sessionManager->getInstallValues($currentController);
            if ($savedFieldValues) {
                foreach ($fields as $key => $item) {
                    if (array_key_exists($key, $savedFieldValues)) {
                        $fields[$key]['value'] = $savedFieldValues[$key];
                    }
                }
            }
        }
        return $fields;
    }

    /**
     * Resets all controllers (reused from Installer class)
     *
     * @return void
     * @throws Exception If controller class does not exist
     */
    private function resetAllControllers()
    {
        $files = glob(__DIR__ . "/../InstallControllers/*.php", GLOB_NOSORT);
        if ($files === false) {
            throw new Exception('Error reading installation files.');
        }

        foreach ($files as $file) {
            // Validate file path
            if (!$this->isValidFilePath($file)) {
                continue;
            }

            $class = pathinfo($file, PATHINFO_FILENAME);
            $fullClassName = "InstallControllers\\" . $class;

            if (class_exists($fullClassName)) {
                $installController = new $fullClassName();
                $installController->resetTriggered();
            } else {
                throw new Exception("Class $fullClassName does not exist.");
            }
        }
    }

    /**
     * Validates file path to prevent path traversal (reused from Installer class)
     *
     * @param string $path File path
     * @return bool True if path is valid
     */
    private function isValidFilePath($path)
    {
        $realPath = realpath($path);
        $installDir = realpath(__DIR__ . "/../InstallControllers");

        if ($realPath === false || $installDir === false) {
            return false;
        }

        return strpos($realPath, $installDir) === 0 && pathinfo($realPath, PATHINFO_EXTENSION) === 'php';
    }

    /**
     * Clears the CLI screen
     *
     * @return void
     */
    private function clearScreen()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            system('cls');
        } else {
            system('clear');
        }
    }
}