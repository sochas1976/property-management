<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Storage;
use App\Models\Document;

class DocumentController extends Controller {
    public function view(string $id): void {
        $this->serve((int)$id, false);
    }

    public function download(string $id): void {
        $this->serve((int)$id, true);
    }

    private function serve(int $id, bool $download): void {
        Auth::requireLogin();

        $doc = Document::findById($id);
        if (!$doc) {
            http_response_code(404);
            die("Document record not found.");
        }

        // Verify that the file exists in the designated local storage path
        if (!file_exists($doc['file_path'])) {
            http_response_code(404);
            die("The physical file for this document is missing on the server disk.");
        }

        Storage::stream($doc['file_path'], $doc['original_name'], $doc['mime_type'], $download);
    }

    public function delete(string $id): void {
        Auth::requireRole(['admin', 'manager']);
        $doc = Document::findById((int)$id);
        if ($doc) {
            Document::delete((int)$id);
            $this->setFlash('success', 'Document deleted.');
            if ($doc['related_type'] === 'lease') {
                $this->redirect('/leases/' . $doc['related_id']);
            } elseif ($doc['related_type'] === 'maintenance') {
                $this->redirect('/maintenance/' . $doc['related_id']);
            }
        }
        $this->redirect('/dashboard');
    }
}
