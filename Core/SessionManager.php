<?php

namespace Core;

use Core\InstallFeedback;

class SessionManager
{
    /**
     * Constructor to ensure session is properly started
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $httpOnly = true;
            $sameSite = 'Strict';

            // Set cookie parameters for enhanced security
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => $sameSite
            ]);

            session_start();
            $this->updateLastActivity();
        }
    }

    /**
     * Gets the install feedback from session
     *
     * @return InstallFeedback|null The feedback object or null
     */
    public function getInstallFeedback()
    {
        return isset($_SESSION['InstallFeedback']) ? $_SESSION['InstallFeedback'] : null;
    }

    /**
     * Sets the install feedback in session
     *
     * @param InstallFeedback $feedback The feedback object
     * @return void
     */
    public function setInstallFeedback(InstallFeedback $feedback)
    {
        $_SESSION['InstallFeedback'] = $feedback;
    }

    /**
     * Clears the install feedback from session
     *
     * @return void
     */
    public function clearInstallFeedback()
    {
        unset($_SESSION['InstallFeedback']);
    }

    /**
     * Checks if current page is set in session
     *
     * @return bool True if current page is set
     */
    public function hasCurrentPage()
    {
        return isset($_SESSION['CurrentPage']);
    }

    /**
     * Gets the current page from session
     *
     * @return int The current page number
     */
    public function getCurrentPage()
    {
        return isset($_SESSION['CurrentPage']) ? (int)$_SESSION['CurrentPage'] : 1;
    }

    /**
     * Sets the current page in session
     *
     * @param int $pageNumber The page number
     * @return void
     */
    public function setCurrentPage(int $pageNumber)
    {
        $_SESSION['CurrentPage'] = $pageNumber;
    }

    /**
     * Checks if navigation is allowed
     *
     * @return bool True if navigation is allowed
     */
    public function isNavigationAllowed()
    {
        return isset($_SESSION['NavigationAllowed']) && $_SESSION['NavigationAllowed'] === true;
    }

    /**
     * Gets the allowed page from session
     *
     * @return int The allowed page number
     */
    public function getAllowedPage()
    {
        return isset($_SESSION['AllowedPage']) ? (int)$_SESSION['AllowedPage'] : 1;
    }

    /**
     * Allows navigation in session
     *
     * @return void
     */
    public function allowNavigation()
    {
        $_SESSION['NavigationAllowed'] = true;
    }

    /**
     * Sets the allowed page in session
     *
     * @param int $pageNumber The allowed page number
     * @return void
     */
    public function setAllowedPage(int $pageNumber)
    {
        $_SESSION['AllowedPage'] = $pageNumber;
    }

    /**
     * Resets navigation flags in session
     *
     * @return void
     */
    public function resetNavigationFlags()
    {
        $_SESSION['NavigationAllowed'] = false;
        unset($_SESSION['AllowedPage']);
    }

    /**
     * Gets the current controller from session
     *
     * @return string|null The current controller class or null
     */
    public function getCurrentController()
    {
        return isset($_SESSION['CurrentController']) ? $_SESSION['CurrentController'] : null;
    }

    /**
     * Sets the current controller in session
     *
     * @param string $controllerClass The controller class
     * @return void
     */
    public function setCurrentController(string $controllerClass)
    {
        $_SESSION['CurrentController'] = $controllerClass;
    }

    /**
     * Gets the install values for a controller
     *
     * @param string $controller The controller class
     * @return array|null The values or null
     */
    public function getInstallValues(string $controller)
    {
        return isset($_SESSION['InstallValues'][$controller]) ? $_SESSION['InstallValues'][$controller] : null;
    }

    /**
     * Sets the install values for a controller
     *
     * @param string $controller The controller class
     * @param array $values The values
     * @return void
     */
    public function setInstallValuesForController(string $controller, array $values)
    {
        // Filter out CSRF token to prevent it from being stored with form values
        if (isset($values['csrf_token'])) {
            unset($values['csrf_token']);
        }
        $_SESSION['InstallValues'][$controller] = $values;
    }

    /**
     * Destroys the session
     *
     * @return void
     */
    public function destroySession()
    {
        $_SESSION = [];

        if (php_sapi_name() !== 'cli' && ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params["path"],
                    'domain' => $params["domain"],
                    'secure' => $params["secure"],
                    'httponly' => $params["httponly"],
                    'samesite' => $params["samesite"] ?? null
                ]
            );
        }
        else if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params["path"],
                    'domain' => $params["domain"],
                    'secure' => $params["secure"],
                    'httponly' => $params["httponly"],
                    'samesite' => 'Strict'
                ]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Updates the last activity timestamp
     *
     * @return void
     */
    public function updateLastActivity()
    {
        $_SESSION['LastActivity'] = time();
    }

    /**
     * Gets the last activity timestamp
     *
     * @return int|null The timestamp or null
     */
    public function getLastActivity()
    {
        return isset($_SESSION['LastActivity']) ? (int)$_SESSION['LastActivity'] : null;
    }

    /**
     * Sets the CSRF token in session
     *
     * @param string $token The CSRF token
     * @return void
     */
    public function setCSRFToken(string $token)
    {
        $_SESSION['csrf_token'] = $token;
    }

    /**
     * Gets the CSRF token from session
     *
     * @return string|null The CSRF token or null
     */
    public function getCSRFToken()
    {
        return isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : null;
    }

    /**
     * Regenerates the session ID to prevent session fixation
     *
     * @return void
     */
    public function regenerateSessionId()
    {
        session_regenerate_id(true);
    }
}