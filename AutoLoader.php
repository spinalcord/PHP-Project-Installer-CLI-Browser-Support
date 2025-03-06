<?php

class Autoloader {
    protected $prefixes = [];

    public function register() {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function addNamespace($prefix, $baseDir) {
        // Normalisiere den Präfix
        $prefix = trim($prefix, '\\') . '\\';

        // Normalisiere das Basisverzeichnis
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';

        // Speichere den Präfix mit dem zugehörigen Verzeichnis
        $this->prefixes[$prefix] = $baseDir;
    }

    public function loadClass($className) {
        // Initialisiere den aktuellen Präfix
        $prefix = $className;

        // Durchlaufe den Klassennamen von hinten nach vorne, um den passenden Präfix zu finden
        while (false !== $pos = strrpos($prefix, '\\')) {
            // Teile den Präfix und den relativen Klassennamen
            $prefix = substr($className, 0, $pos + 1);
            $relativeClass = substr($className, $pos + 1);

            // Überprüfe, ob der Präfix registriert ist
            if (isset($this->prefixes[$prefix])) {
                // Erstelle den Pfad zur Datei
                $file = $this->prefixes[$prefix]
                    . str_replace('\\', '/', $relativeClass)
                    . '.php';

                // Wenn die Datei existiert, binde sie ein
                if (file_exists($file)) {
                    require $file;
                    return true;
                }
            }

            // Entferne den letzten Namespace-Teil für den nächsten Durchlauf
            $prefix = rtrim($prefix, '\\');
        }

        // Kein passender Präfix gefunden
        return false;
    }
}