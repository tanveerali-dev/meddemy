<?php
/**
 * video_token.php
 * Secure AJAX endpoint — students/video_token.php
 * Returns embed URL only if:
 *   1. Student is logged in
 *   2. Student is enrolled in the course
 *   3. CSRF token matches
 *   4. Request is AJAX (X-Requested-With header)
 */

session_start();
include("../includes/db.php");

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

// ── 1. Must be AJAX ──
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// ── 2. Must be logged in ──
if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// ── 3. Validate CSRF token ──
$csrf = $_POST['csrf'] ?? '';
if (!isset($_SESSION['csrf_token']) || $csrf !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

$student_id = (int)$_SESSION['student_id'];
$video_id   = (int)($_POST['video_id'] ?? 0);
$course_id  = (int)($_POST['course_id'] ?? 0);

if (!$video_id || !$course_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad request']);
    exit();
}

// ── 4. Check enrollment ──
$enroll = $conn->prepare("SELECT 1 FROM enrollment WHERE student_id=? AND course_id=?");
$enroll->bind_param("ii", $student_id, $course_id);
$enroll->execute();
if ($enroll->get_result()->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Not enrolled']);
    exit();
}

// ── 5. Get video ──
$stmt = $conn->prepare("SELECT youtube_link, title FROM video WHERE video_id=? AND course_id=?");
$stmt->bind_param("ii", $video_id, $course_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Video not found']);
    exit();
}

// ── 6. Build embed URL ──
function buildEmbedUrl($url) {
    if (strpos($url, "watch?v=") !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        if (!empty($q['v'])) return "https://www.youtube.com/embed/" . $q['v'] . "?rel=0&modestbranding=1&enablejsapi=1";
    }
    if (strpos($url, "youtu.be/") !== false) {
        $id = basename(strtok($url, '?'));
        return "https://www.youtube.com/embed/" . $id . "?rel=0&modestbranding=1&enablejsapi=1";
    }
    if (strpos($url, "embed/") !== false) return $url;
    if (strpos($url, ".mp4") !== false) return $url;
    return $url;
}

$embedUrl = buildEmbedUrl($row['youtube_link']);
$isMp4    = strpos($embedUrl, '.mp4') !== false;

// ── 7. One-time use nonce per response (extra protection) ──
$nonce = bin2hex(random_bytes(16));
$_SESSION['last_video_nonce'] = $nonce;

echo json_encode([
    'embed'  => $embedUrl,
    'title'  => $row['title'],
    'is_mp4' => $isMp4,
    'nonce'  => $nonce,
]);
exit();