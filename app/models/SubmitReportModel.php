<?php

class SubmitReportModel
{
    public function getSubjects(): array
    {
        return db_fetch_all('SELECT id, name FROM subjects WHERE is_active = 1 ORDER BY name ASC');
    }

    public function getCategoriesBySubject(int $subjectId): array
    {
        return db_fetch_all(
            'SELECT id, name FROM categories WHERE subject_id = ? AND is_active = 1 ORDER BY name ASC',
            'i',
            [$subjectId],
        );
    }

    public function getSubcategoriesByCategory(int $categoryId): array
    {
        return db_fetch_all(
            'SELECT id, name FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY name ASC',
            'i',
            [$categoryId],
        );
    }

    public function getClassificationNames(int $subjectId, int $categoryId, int $subCategoryId): ?array
    {
        $row = db_fetch_one(
            "SELECT
                s.name AS subject_name,
                c.name AS category_name,
                sc.name AS sub_category_name
             FROM subjects s
             JOIN categories c ON c.subject_id = s.id
             JOIN subcategories sc ON sc.category_id = c.id
             WHERE s.id = ? AND c.id = ? AND sc.id = ?
               AND s.is_active = 1 AND c.is_active = 1 AND sc.is_active = 1
             LIMIT 1",
            'iii',
            [$subjectId, $categoryId, $subCategoryId],
        );

        return $row ?: null;
    }

    public function isActiveDepartment(int $departmentId): bool
    {
        if ($departmentId <= 0) {
            return false;
        }

        $row = db_fetch_one('SELECT id FROM departments WHERE id = ? AND is_active = 1 LIMIT 1', 'i', [$departmentId]);

        return (bool) $row;
    }

    public function generateSecurityReportNo(): string
    {
        $year = date('Y');
        $prefix = 'SR-' . $year . '-';

        $row = db_fetch_one(
            'SELECT report_no FROM reports WHERE report_no LIKE ? ORDER BY report_no DESC LIMIT 1',
            's',
            [$prefix . '%'],
        );

        $last = $row['report_no'] ?? null;
        $seq = 0;
        if ($last && preg_match('/^SR-' . preg_quote($year, '/') . '-(\d{4})$/', $last, $m)) {
            $seq = (int) $m[1];
        }
        $seq++;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function insertReport(array $data): int
    {
        db_execute(
            "INSERT INTO reports (
                report_no,
                subject_id,
                category_id,
                sub_category_id,
                subject,
                category,
                location,
                severity,
                building,
                security_type,
                responsible_department_id,
                details,
                actions_taken,
                remarks,
                assessment,
                recommendations,
                submitted_by,
                status,
                current_reviewer,
                submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted_to_ga_manager', 'ga_manager', NOW())",
            'siiissssssisssssss',
            [
                $data['report_no'],
                $data['subject_id'],
                $data['category_id'],
                $data['sub_category_id'],
                $data['subject_name'],
                $data['category_name'],
                $data['location'],
                $data['severity'],
                $data['building'],
                $data['security_type'],
                (int) $data['department_id'],
                $data['details'],
                $data['actions_taken'],
                $data['remarks'],
                $data['assessment'],
                $data['recommendations'],
                (string) $data['submitted_by'],
            ],
        );

        return (int) db_last_insert_id();
    }

    public function insertStatusHistory(int $reportId, string $status, string $changedBy, string $notes): void
    {
        db_execute(
            'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, NOW())',
            'isss',
            [$reportId, $status, $changedBy, $notes],
        );
    }

    public function insertAttachment(
        int $reportId,
        string $fileName,
        string $filePath,
        string $mimeType,
        int $fileSizeBytes,
        string $uploadedBy,
    ): void {
        db_execute(
            'INSERT INTO report_attachments (report_id, file_name, file_path, mime_type, file_size_bytes, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)',
            'isssis',
            [$reportId, $fileName, $filePath, $mimeType, $fileSizeBytes, $uploadedBy],
        );
    }

    public function updateEvidenceImagePath(int $reportId, string $evidencePath): void
    {
        db_execute('UPDATE reports SET evidence_image_path = ? WHERE id = ?', 'si', [$evidencePath, $reportId]);
    }

    public function addSubject(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Subject name is required.');
        }

        $existing = db_fetch_one('SELECT id FROM subjects WHERE LOWER(name) = LOWER(?) LIMIT 1', 's', [$name]);
        if ($existing) {
            return (int) $existing['id'];
        }

        db_execute('INSERT INTO subjects (name, is_active, created_at) VALUES (?, 1, NOW())', 's', [$name]);

        return (int) db_last_insert_id();
    }

    public function addCategory(string $name, int $subjectId): int
    {
        $name = trim($name);
        if ($name === '' || $subjectId <= 0) {
            throw new RuntimeException('Category name and subject are required.');
        }

        $existing = db_fetch_one(
            'SELECT id FROM categories WHERE subject_id = ? AND LOWER(name) = LOWER(?) LIMIT 1',
            'is',
            [$subjectId, $name],
        );
        if ($existing) {
            return (int) $existing['id'];
        }

        db_execute('INSERT INTO categories (subject_id, name, is_active, created_at) VALUES (?, ?, 1, NOW())', 'is', [
            $subjectId,
            $name,
        ]);

        return (int) db_last_insert_id();
    }

    public function addSubcategory(string $name, int $categoryId): int
    {
        $name = trim($name);
        if ($name === '' || $categoryId <= 0) {
            throw new RuntimeException('Subcategory name and category are required.');
        }

        $existing = db_fetch_one(
            'SELECT id FROM subcategories WHERE category_id = ? AND LOWER(name) = LOWER(?) LIMIT 1',
            'is',
            [$categoryId, $name],
        );
        if ($existing) {
            return (int) $existing['id'];
        }

        db_execute(
            'INSERT INTO subcategories (category_id, name, is_active, created_at) VALUES (?, ?, 1, NOW())',
            'is',
            [$categoryId, $name],
        );

        return (int) db_last_insert_id();
    }
}
