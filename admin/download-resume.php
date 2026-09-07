<?php
/**
 * Authenticated resume download.
 *
 * CVs carry an applicant's name, phone, email and address. They used to sit
 * under /uploads/resumes/ where anyone who guessed the filename could fetch
 * them, and robots.txt invited crawlers in with "Allow: /uploads/". The files
 * are now blocked at the web root by .htaccess and reach staff only here.
 *
 * The path is read from the database by application id, never from the query
 * string, so a caller cannot ask for a file of their choosing.
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireAdmin();

$id  = (int)($_GET['id'] ?? 0);
$app = $id ? dbFetchOne("SELECT name, resume_path FROM job_applications WHERE id = ?", [$id]) : null;

if (!$app || empty($app['resume_path'])) {
    http_response_code(404);
    exit('Resume not found.');
}

/* Resolve and confine to the resumes directory — a stored path containing
   ../ must not be able to reach outside it. */
$baseDir = realpath(UPLOADS_PATH . '/resumes');
$file    = realpath(UPLOADS_PATH . '/' . $app['resume_path']);

if ($baseDir === false || $file === false || !str_starts_with($file, $baseDir . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit('Resume not found.');
}

/* Name the download after the applicant rather than the stored hash. */
$ext      = strtolower(pathinfo($file, PATHINFO_EXTENSION)) ?: 'pdf';
$safeName = preg_replace('/[^A-Za-z0-9 _-]/', '', $app['name']) ?: 'resume';
$download = trim($safeName) . '.' . $ext;

$mime = match ($ext) {
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    default => 'application/octet-stream',
};

logActivity('view', 'application', "Downloaded resume for application #{$id}");

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $download . '"');
header('Content-Length: ' . filesize($file));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($file);
