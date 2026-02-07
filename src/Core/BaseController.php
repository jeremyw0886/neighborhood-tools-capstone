<?php

namespace App\Core;

class BaseController
{
    /**
     * Render a view inside the main layout.
     *
     * @param string $view   Path relative to Views/ (e.g. 'home/index')
     * @param array  $data   Variables to extract into the view
     */
    protected function render(string $view, array $data = []): void
    {
        // Make data available as individual variables in the view
        extract($data);

        // Capture the page-specific content
        ob_start();
        require BASE_PATH . '/src/Views/' . $view . '.php';
        $content = ob_get_clean();

        // Load the layout (which uses $content)
        require BASE_PATH . '/src/Views/layouts/main.php';
    }
}
