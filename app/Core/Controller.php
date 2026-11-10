<?php
namespace App\Core;

abstract class Controller {
    protected function render(string $view, array $data = []): void {
        extract($data);
        
        // Flash messages
        $flashSuccess = $this->getFlash('success');
        $flashError = $this->getFlash('error');
        $flashWarning = $this->getFlash('warning');

        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewFile)) {
            die("View file not found: " . htmlspecialchars($viewFile));
        }

        require VIEW_PATH . '/layouts/header.php';
        require $viewFile;
        require VIEW_PATH . '/layouts/footer.php';
    }

    /**
     * @param mixed $data
     * @param int $status
     */
    protected function json($data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): void {
        header('Location: ' . Router::url($path));
        exit;
    }

    protected function setFlash(string $type, string $message): void {
        Auth::start();
        $_SESSION['flash_' . $type] = $message;
    }

    protected function getFlash(string $type): ?string {
        Auth::start();
        $key = 'flash_' . $type;
        if (isset($_SESSION[$key])) {
            $msg = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $msg;
        }
        return null;
    }

    protected function sanitize(array $data): array {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $clean[$key] = trim($value);
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }
}
