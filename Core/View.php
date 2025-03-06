<?php
namespace Core;
use Exception;

/**
 *  Manages templates and variables.
 */
class View {
    /**
     * Variables for the template.
     * @var array
     */
    private $variables = array();

    /**
     * Path to a form template.
     * @var string|null
     */
    private $form = null;

    /**
     *  Base directory for templates.
     * @var string
     */
    private $templateDir = 'template';

    /**
     * Constructor.
     *
     * @param string $templateDir  Optional: Override default template directory.
     */
    public function __construct($templateDir = 'Templates') {

        $this->templateDir = rtrim(__DIR__ . '/../'. $templateDir, '/');
    }

    /**
     * Sets a variable for the template.
     *
     * @param string $name  Variable name.
     * @param mixed $value Variable value.
     * @return View  Current instance for method chaining.
     */
    public function set($name, $value) {
        $this->variables[$name] = $value;
        return $this;
    }

    /**
     * Sets the form template.
     *
     * @param string $formPath Path to the form template, relative to template directory.
     * @return View Current instance for method chaining.
     */
    public function form($formPath) {
        $this->form = $formPath;
        return $this;
    }

    /**
     * Renders the template.
     *
     * @param string $template Path to the template, relative to template directory.
     * @return string Rendered content.
     */
    public function render($template) {
        //  Template path.
        $templatePath = $this->templateDir . '/' . $template;

        // Make variables available in the template.
        extract($this->variables);

        // If a form is set, make its path available as $form.
        if ($this->form !== null) {
            $form = $this->templateDir . '/' . $this->form;
        }

        // Start output buffering to return rendered content.
        ob_start();

        // Include the template.
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            throw new Exception("Template '$templatePath' not found!");
        }

        // Return buffered content.
        return ob_get_clean();
    }
}