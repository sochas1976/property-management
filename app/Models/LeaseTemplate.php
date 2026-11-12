<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class LeaseTemplate {
    public static function all(): array {
        $db = Database::getConnection();
        $rows = $db->query("SELECT * FROM lease_templates ORDER BY created_at DESC")->fetchAll();
        foreach ($rows as &$row) {
            $row['mappings'] = !empty($row['placeholder_mappings'])
                ? (json_decode($row['placeholder_mappings'], true) ?: [])
                : [];
        }
        return $rows;
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM lease_templates WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['mappings'] = !empty($row['placeholder_mappings'])
            ? (json_decode($row['placeholder_mappings'], true) ?: [])
            : [];
        return $row;
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $mappingsJson = isset($data['placeholder_mappings'])
            ? (is_string($data['placeholder_mappings']) ? $data['placeholder_mappings'] : json_encode($data['placeholder_mappings']))
            : null;

        $stmt = $db->prepare("
            INSERT INTO lease_templates (name, description, original_name, stored_filename, file_path, file_size, placeholder_mappings, uploaded_by_user_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['original_name'],
            $data['stored_filename'],
            $data['file_path'],
            (int)($data['file_size'] ?? 0),
            $mappingsJson,
            $data['uploaded_by_user_id'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function updateMappings(int $id, array $mappings): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE lease_templates SET placeholder_mappings = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([json_encode($mappings, JSON_UNESCAPED_UNICODE), $id]);
    }

    public static function delete(int $id): bool {
        $template = self::findById($id);
        if (!$template) return false;

        if (!empty($template['file_path']) && file_exists($template['file_path'])) {
            @unlink($template['file_path']);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM lease_templates WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Seeds a default residential lease template if no templates exist yet.
     */
    public static function seedDefaultIfNeeded(?int $userId = null): void {
        $db = Database::getConnection();
        $count = (int)$db->query("SELECT COUNT(*) FROM lease_templates")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $dir = STORAGE_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'leases';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'standard_residential_lease_template.docx';
        $targetPath = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($targetPath)) {
            $zip = new \ZipArchive();
            if ($zip->open($targetPath, \ZipArchive::CREATE) === TRUE) {
                $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
                    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
                    <Default Extension="xml" ContentType="application/xml"/>
                    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
                </Types>';
                $zip->addFromString('[Content_Types].xml', $contentTypes);

                $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
                </Relationships>';
                $zip->addFromString('_rels/.rels', $rels);

                $docXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
                    <w:body>
                        <w:p>
                            <w:r><w:rPr><w:b/><w:sz w:val="32"/></w:rPr><w:t>RESIDENTIAL LEASE AGREEMENT</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:t>This Residential Tenancy Agreement is entered into on this </w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/><w:b/></w:rPr><w:t>[AGREEMENT DATE]</w:t></w:r>
                            <w:r><w:t>, between the Landlord and Tenant(s) identified below.</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:rPr><w:b/></w:rPr><w:t>1. PARTIES TO THIS AGREEMENT:</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:t>LANDLORD: </w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/><w:b/></w:rPr><w:t>[LANDLORD NAME / COMPANY]</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:t>TENANT(S): </w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/><w:b/></w:rPr><w:t>[TENANT FULL NAME]</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:rPr><w:b/></w:rPr><w:t>2. RENTAL PREMISES:</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:t>The Landlord agrees to rent to the Tenant the residential premises located at: Unit </w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/></w:rPr><w:t>[UNIT NUMBER]</w:t></w:r>
                            <w:r><w:t>, </w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/></w:rPr><w:t>[PROPERTY ADDRESS]</w:t></w:r>
                            <w:r><w:t>, </w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/></w:rPr><w:t>[CITY, PROVINCE/STATE POSTAL CODE]</w:t></w:r>
                            <w:r><w:t>.</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:rPr><w:b/></w:rPr><w:t>3. TERM OF TENANCY:</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:t>The tenancy shall commence on </w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/></w:rPr><w:t>[START DATE]</w:t></w:r>
                            <w:r><w:t> and shall terminate on </w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/></w:rPr><w:t>[END DATE]</w:t></w:r>
                            <w:r><w:t>.</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:rPr><w:b/></w:rPr><w:t>4. RENT &amp; SECURITY DEPOSIT:</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:t>The Tenant shall pay to the Landlord rent of $</w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/></w:rPr><w:t>[MONTHLY RENT]</w:t></w:r>
                            <w:r><w:t> payable on a </w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/></w:rPr><w:t>[PAYMENT FREQUENCY]</w:t></w:r>
                            <w:r><w:t> basis. A security deposit in the amount of $</w:t></w:r>
                            <w:r><w:rPr><w:color w:val="FF0000"/></w:rPr><w:t>[SECURITY DEPOSIT]</w:t></w:r>
                            <w:r><w:t> is held by the Landlord.</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:rPr><w:b/></w:rPr><w:t>5. SIGNATURES:</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:t>Landlord Signature: __________________________    Date: ____________</w:t></w:r>
                        </w:p>
                        <w:p>
                            <w:r><w:t>Tenant Signature:   __________________________    Date: ____________</w:t></w:r>
                        </w:p>
                    </w:body>
                </w:document>';
                $zip->addFromString('word/document.xml', $docXml);
                $zip->close();
            }
        }

        if (file_exists($targetPath)) {
            self::create([
                'name'                => 'Standard Residential Tenancy Agreement',
                'description'         => 'Default residential lease agreement with [ ] bracket placeholders for Landlord, Tenant, Unit, Dates, and Financial Terms.',
                'original_name'       => $filename,
                'stored_filename'     => $filename,
                'file_path'           => $targetPath,
                'file_size'           => filesize($targetPath),
                'uploaded_by_user_id' => $userId
            ]);
        }
    }
}
