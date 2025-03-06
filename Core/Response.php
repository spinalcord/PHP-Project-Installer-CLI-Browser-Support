<?php

namespace Core;

class Response {
    // Sends a JSON response.
    public function json($data, int $status = 200)
    {
        // Set HTTP status code.
        http_response_code($status);

        // Set content type to JSON.
        header('Content-Type: application/json');

        // Output JSON encoded data.
        echo json_encode($data);

        // Stop script execution.
        exit;
    }

    // Sends a JSON error response.
    public function error(string $message, int $status = 400)
    {
        $this->json([
            'error' => true,
            'message' => $message
        ], $status);
        exit;
    }

    // Sends a plain text response.
    public function text(string $message, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: text/plain');
        echo $message;
        exit;
    }

    // Sends an HTML response.
    public function html(string $html, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: text/html');
        echo $html;
        exit;
    }

    public function reroute(string $url, int $status = 302)
    {
        http_response_code($status);
        if (php_sapi_name() === 'cli') {
            echo "Visit: {$url}";
        } else {
            header("Location: $url");
            $backtrace = debug_backtrace();
            if (isset($backtrace[1]['class'])) {
                $callerClass = $backtrace[1]['class'];
                $callerMethod = $backtrace[1]['function'];
                error_log("Reroute from: {$callerClass}::{$callerMethod}()");
            }
            exit;
        }

    }

    // Sets a custom header.
    public function setHeader(string $name, string $value)
    {
        header("{$name}: {$value}");
    }
}