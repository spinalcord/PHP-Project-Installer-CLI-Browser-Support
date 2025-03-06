<?php

namespace Core;

use Core\InstallFeedback;
use Exception;

class Installer
{
    private $sessionManager;
    private $maxAllowedPages = 20; // Maximum number of pages to prevent excessive page checking

    public function __construct()
    {
        $this->sessionManager = new SessionManager();
    }

    /**
     * Displays the installation page with the given page number
     *
     * @param int $pageNumber The page number to show
     * @return void
     * @throws Exception If controller class does not exist
     */
    public function show(int $pageNumber)
    {
        // Validate page number input
        $pageNumber = filter_var($pageNumber, FILTER_VALIDATE_INT);
        if ($pageNumber === false) {
            (new Response())->reroute('/page/1');
            return;
        }

        $installFeedback = $this->sessionManager->getInstallFeedback();

        // Get all controller files with a limit to prevent excessive file operations
        $files = glob(__DIR__ . "/../InstallControllers/*.php", GLOB_NOSORT);
        if ($files === false || count($files) > $this->maxAllowedPages) {
            throw new Exception("Error loading installation files or too many files detected.");
        }

        $controllers = $this->getSortedControllers($files);
        $priorityTable = array_map(function ($item) {
            return $item['class'];
        }, $controllers);

        // Page flow validation
        if (!$this->sessionManager->hasCurrentPage()) {
            // First page, set session
            $this->sessionManager->setCurrentPage(1);
            $pageNumber = 1;
        } else {
            // Validate requested page index
            $currentPage = $this->sessionManager->getCurrentPage();

            // Add timeout check to prevent session lock
            if ($this->isSessionExpired()) {
                $this->sessionManager->destroySession();
                (new Response())->reroute('/page/1');
                return;
            }

            if ($this->sessionManager->isNavigationAllowed()) {
                // Navigation allowed by submit button
                $allowedPage = $this->sessionManager->getAllowedPage();
                if ($pageNumber == $allowedPage) {
                    // Update current page only if navigating to allowed page
                    $this->sessionManager->setCurrentPage($pageNumber);
                    // Reset navigation flags
                    $this->sessionManager->resetNavigationFlags();
                } else {
                    // If navigating to a non-allowed page
                    (new Response())->reroute('/page/' . $currentPage);
                    return;
                }
            } else {
                // Direct URL manipulation without submit button
                if ($pageNumber != $currentPage) {
                    // Redirect to current page
                    (new Response())->reroute('/page/' . $currentPage);
                    return;
                }
            }
        }

        // Validate page range
        if ($pageNumber <= 0) {
            (new Response())->reroute('/page/1');
            return;
        }

        $totalPages = count($files);
        if ($totalPages == 0) {
            throw new Exception("No installation pages found.");
        }

        if ($totalPages < $pageNumber) {
            (new Response())->reroute('/page/' . $totalPages);
            return;
        }

        $pageIndex = $pageNumber - 1;

        // Determine button visibility
        $showNextButton = $pageNumber < count($priorityTable);
        $showBackButton = $pageNumber > 1;
        $showCompleteButton = !$showNextButton;

        // Check if priority table index exists before accessing
        if (!isset($priorityTable[$pageIndex])) {
            throw new Exception("Invalid page index: $pageIndex");
        }

        $fullClassName = "InstallControllers\\" . $priorityTable[$pageIndex];
        if (class_exists($fullClassName)) {
            // Add CSRF protection
            $csrfToken = $this->generateCSRFToken();
            $this->sessionManager->setCSRFToken($csrfToken);

            $installController = new $fullClassName();
            $installController->initialize();
            $taskName = $installController->taskName();
            $this->sessionManager->setCurrentController($fullClassName);
            $errorMessage = null;

            if ($installFeedback !== null && $installFeedback->getFeedBackType() == 'error') {
                $errorMessage = $installFeedback->getFeedBackValue();
            }

            // Load saved field values
            $fields = $installController->getFields();
            $fields = $this->loadSavedFieldValues($fields, $fullClassName);

            $view = new View();
            $view->set('error', $errorMessage);
            $view->set('taskName', $taskName);
            $view->set('fields', $fields);
            $view->set('showNextButton', $showNextButton);
            $view->set('showBackButton', $showBackButton);
            $view->set('showCompleteButton', $showCompleteButton);
            $view->set('csrfToken', $csrfToken);
            echo $view->render('template.php');
        } else {
            throw new Exception("Class $fullClassName does not exist.");
        }
    }

    /**
     * Sorts controllers by priority
     *
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
     * Loads saved field values from session
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
     * Processes form submission
     *
     * @return void
     * @throws Exception If page number is invalid or state is invalid
     */
    public function submit()
    {
        $this->sessionManager->clearInstallFeedback();

        // Verify CSRF token
        if (!$this->verifyCSRFToken($_POST)) {
            $feedback = new InstallFeedback('error', 'Invalid form submission. Please try again.');
            $this->sessionManager->setInstallFeedback($feedback);
            (new Response())->reroute('/page/' . $this->sessionManager->getCurrentPage());
            return;
        }

        if (isset($_POST['submitNext']) || isset($_POST['submitComplete'])) {

            if (isset($_POST['submitComplete'])) {
                $files = glob(__DIR__ . "/../InstallControllers/*.php", GLOB_NOSORT);
                if ($files === false) {
                    throw new Exception('Error reading installation files.');
                }

                if (count($files) != $this->sessionManager->getCurrentPage()) {
                    throw new Exception('Invalid page number.');
                }
            }

            $currentControllerClass = $this->sessionManager->getCurrentController();
            if (!class_exists($currentControllerClass)) {
                throw new Exception("Controller class does not exist: $currentControllerClass");
            }

            $currentController = new $currentControllerClass();

            // Sanitize POST data
            $sanitizedPost = $this->sanitizeInputData($_POST);
            $feedback = $currentController->nextTriggered($sanitizedPost);

            if ($feedback instanceof InstallFeedback) {
                $this->sessionManager->setInstallFeedback($feedback);
                (new Response())->reroute('/page/' . $this->sessionManager->getCurrentPage());
                return;
            }

            // Allow navigation to next page
            $this->sessionManager->allowNavigation();
            $this->setInstallValues($sanitizedPost);
            $this->sessionManager->updateLastActivity();

            if (isset($_POST['submitNext'])) {
                $nextPage = $this->sessionManager->getCurrentPage() + 1;
                $this->sessionManager->setAllowedPage($nextPage);
                (new Response())->reroute('/page/' . $nextPage);
            } else {
                $currentPage = $this->sessionManager->getCurrentPage();
                $this->sessionManager->setAllowedPage($currentPage);
                (new Response())->reroute('/page/' . $currentPage);
            }
        } elseif (isset($_POST['submitBack'])) {
            // Allow navigation to previous page
            $sanitizedPost = $this->sanitizeInputData($_POST);
            $this->setInstallValues($sanitizedPost);

            $currentControllerClass = $this->sessionManager->getCurrentController();
            if (!class_exists($currentControllerClass)) {
                throw new Exception("Controller class does not exist: $currentControllerClass");
            }

            $currentController = new $currentControllerClass();
            $feedback = $currentController->backTriggered($sanitizedPost);

            if ($feedback instanceof InstallFeedback) {
                $this->sessionManager->setInstallFeedback($feedback);
                (new Response())->reroute('/page/' . $this->sessionManager->getCurrentPage());
                return;
            }

            $previousPage = $this->sessionManager->getCurrentPage() - 1;
            if ($previousPage < 1) {
                $previousPage = 1;
            }

            $this->sessionManager->allowNavigation();
            $this->sessionManager->setAllowedPage($previousPage);
            $this->sessionManager->updateLastActivity();
            (new Response())->reroute('/page/' . $previousPage);
        } elseif (isset($_POST['submitReset'])) {
            $this->resetAllControllers();
            $this->sessionManager->destroySession();
            (new Response())->reroute('/');
        } else {
            // Invalid submission, redirect to current page
            (new Response())->reroute('/page/' . $this->sessionManager->getCurrentPage());
        }
    }

    /**
     * Resets all controllers
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
     * Sets installation values in session
     *
     * @param array $postValues POST values
     * @return void
     * @throws Exception If state is invalid
     */
    public function setInstallValues(array $postValues)
    {
        $currentController = $this->sessionManager->getCurrentController();
        if ($currentController) {
            $this->sessionManager->setInstallValuesForController($currentController, $postValues);
        } else {
            throw new Exception('Invalid state');
        }
    }

    /**
     * Checks if session has expired to prevent loops
     *
     * @return bool True if session has expired
     */
    private function isSessionExpired()
    {
        $lastActivity = $this->sessionManager->getLastActivity();
        if ($lastActivity === null) {
            return false;
        }

        // Session timeout after 30 minutes of inactivity
        $sessionTimeout = 1800; // 30 minutes
        return (time() - $lastActivity > $sessionTimeout);
    }

    /**
     * Generates CSRF token for form submission security
     *
     * @return string CSRF token
     */
    private function generateCSRFToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Verifies CSRF token from POST data
     *
     * @param array $postData POST data
     * @return bool True if token is valid
     */
    private function verifyCSRFToken($postData)
    {
        if (!isset($postData['csrf_token'])) {
            return false;
        }

        $token = $postData['csrf_token'];
        $storedToken = $this->sessionManager->getCSRFToken();

        if (empty($token) || empty($storedToken)) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Sanitizes input data
     *
     * @param array $data Input data
     * @return array Sanitized data
     */
    private function sanitizeInputData($data)
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInputData($value);
            } else {
                // Basic sanitization, more specific sanitization should be done in controllers
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }

    /**
     * Validates file path to prevent path traversal
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
}