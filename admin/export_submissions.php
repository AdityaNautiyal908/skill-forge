<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ------------------------
// Set proper timezone
// ------------------------
date_default_timezone_set('Asia/Kolkata'); // ensures correct current time

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_mongo.php';
require_once __DIR__ . '/../config/db_mysql.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// ========================
// CREATE WORD DOCUMENT
// ========================
$phpWord = new PhpWord();

// ------------------------
// FIRST PAGE: Big SkillForge Title with page border
// ------------------------
$firstSection = $phpWord->addSection([
    'borderTopSize' => 6, 'borderTopColor' => '000000',
    'borderBottomSize' => 6, 'borderBottomColor' => '000000',
    'borderLeftSize' => 6, 'borderLeftColor' => '000000',
    'borderRightSize' => 6, 'borderRightColor' => '000000',
    'marginTop' => 1440, 'marginBottom' => 1440, 'marginLeft' => 1440, 'marginRight' => 1440
]);

$firstSection->addText(
    'SkillForge',
    ['name' => 'Arial', 'size' => 36, 'bold' => true, 'color' => '0000FF'],
    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
);
$firstSection->addTextBreak(3);
$firstSection->addText(
    'User Submissions Report',
    ['name' => 'Arial', 'size' => 24, 'bold' => true],
    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
);
$firstSection->addTextBreak(2);
$firstSection->addText('Generated on: ' . date('Y-m-d h:i:s A'), ['name' => 'Arial', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

// ------------------------
// SECOND SECTION: Header + Watermark + page borders
// ------------------------
$sectionStyle = [
    'borderTopSize' => 6, 'borderTopColor' => '000000',
    'borderBottomSize' => 6, 'borderBottomColor' => '000000',
    'borderLeftSize' => 6, 'borderLeftColor' => '000000',
    'borderRightSize' => 6, 'borderRightColor' => '000000',
    'marginTop' => 1440, 'marginBottom' => 1440, 'marginLeft' => 1440, 'marginRight' => 1440,
    'breakType' => 'nextPage'
];

$section = $phpWord->addSection($sectionStyle);

// Header
$header = $section->addHeader();
$header->addText('SkillForge', ['name' => 'Arial', 'size' => 14, 'bold' => true, 'color' => '0000FF']);

// Watermark behind text
$section->addWatermark('SkillForge', [
    'rotation' => -40,
    'color' => 'C0C0C0',
    'size' => 120,
    'positioning' => \PhpOffice\PhpWord\Style\Image::POSITION_ABSOLUTE,
    'marginTop' => 300,
    'marginLeft' => 300,
    'behindDocument' => true
]);

// ========================
// FETCH SUBMISSIONS
// ========================
$coll = getCollection('coding_platform', 'submissions');
$query = new MongoDB\Driver\Query([], ['sort' => ['submitted_at' => -1]]);
$submissions = $coll['manager']->executeQuery($coll['db'] . ".submissions", $query)->toArray();

if (empty($submissions)) {
    $section->addText('No submissions found.');
} else {
    $userIds = [];
    $problemIds = [];
    $mcqIds = [];

    foreach ($submissions as $sub) {
        $userIds[] = (int)$sub->user_id;
        if (isset($sub->type) && $sub->type === 'code') $problemIds[] = (string)$sub->problem_id;
        elseif (isset($sub->type) && $sub->type === 'mcq') $mcqIds[] = (string)$sub->mcq_id;
    }

    $userIds = array_unique($userIds);
    $problemIds = array_unique($problemIds);
    $mcqIds = array_unique($mcqIds);

    // Fetch usernames from MySQL
    $usernames = [];
    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE id IN ($placeholders)");
        $types = str_repeat('i', count($userIds));
        $stmt->bind_param($types, ...$userIds);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $usernames[$row['id']] = $row['username'];
    }

    // Fetch problem titles from MongoDB
    $problemTitles = [];
    if (!empty($problemIds)) {
        $validProblemIds = [];
        foreach ($problemIds as $id) {
            try { $validProblemIds[] = new MongoDB\BSON\ObjectId($id); } catch (Exception $e) {}
        }
        if (!empty($validProblemIds)) {
            $problemColl = getCollection('coding_platform', 'problems');
            $problemQuery = new MongoDB\Driver\Query(['_id' => ['$in' => $validProblemIds]]);
            $problems = $problemColl['manager']->executeQuery($problemColl['db'] . ".problems", $problemQuery)->toArray();
            foreach ($problems as $p) $problemTitles[(string)$p->_id] = $p->title ?? 'Untitled Problem';
        }
    }

    // Fetch MCQ questions
    $mcqQuestions = [];
    if (!empty($mcqIds)) {
        $validMcqIds = [];
        foreach ($mcqIds as $id) {
            try { $validMcqIds[] = new MongoDB\BSON\ObjectId($id); } catch (Exception $e) {}
        }
        if (!empty($validMcqIds)) {
            $mcqColl = getCollection('coding_platform', 'mcq');
            $mcqQuery = new MongoDB\Driver\Query(['_id' => ['$in' => $validMcqIds]]);
            $mcqs = $mcqColl['manager']->executeQuery($mcqColl['db'] . ".mcq", $mcqQuery)->toArray();
            foreach ($mcqs as $m) $mcqQuestions[(string)$m->_id] = $m->question ?? 'Untitled Question';
        }
    }

    // Add submissions
    foreach ($submissions as $sub) {
        $section->addTextBreak(1);
        $section->addText('Submission ID: ' . (string)$sub->_id);
        $section->addText('User: ' . ($usernames[(int)$sub->user_id] ?? 'Unknown User') . ' (ID: ' . $sub->user_id . ')');

        // ------------------------
        // Correct Submitted At in IST and 12-hour format
        // ------------------------
        $submittedAt = 'Unknown Date';
        if (isset($sub->submitted_at) && $sub->submitted_at instanceof MongoDB\BSON\UTCDateTime) {
            $dt = $sub->submitted_at->toDateTime(); // UTC time
            $dt->setTimezone(new DateTimeZone('Asia/Kolkata')); // convert to IST
            $submittedAt = $dt->format('Y-m-d h:i:s A'); // 12-hour format with AM/PM
        }

        $section->addText('Submitted At: ' . $submittedAt);

        if (isset($sub->type) && $sub->type === 'code') {
            $section->addText('Type: Code Submission');
            $section->addText('Problem: ' . ($problemTitles[(string)$sub->problem_id] ?? 'Unknown Problem'));
            $section->addText('Language: ' . ($sub->language ?? 'N/A'));
            $section->addText('Code:');

            $codeText = $sub->code ?? '';
            $codeLines = explode("\n", $codeText);
            $codeStyle = ['name' => 'Consolas', 'size' => 9, 'color' => '000000'];
            $paragraphStyle = ['spaceAfter' => 0, 'indentation' => ['left' => 400], 'shading' => ['fill' => 'EDEDED']];
            foreach ($codeLines as $line) $section->addText(htmlspecialchars($line), $codeStyle, $paragraphStyle);
        } elseif (isset($sub->type) && $sub->type === 'mcq') {
            $section->addText('Type: MCQ Submission');
            $section->addText('Question: ' . ($mcqQuestions[(string)$sub->mcq_id] ?? 'Unknown MCQ'));
            $section->addText('User Choice: ' . ($sub->choice ?? 'N/A'));
            $section->addText('Correct: ' . ((isset($sub->correct) && $sub->correct) ? 'Yes' : 'No'));
        }

        $section->addTextBreak(1);
        $section->addLine(['weight' => 1, 'width' => 450, 'height' => 0, 'color' => '555555']);
        $section->addTextBreak(1);
    }
}

// Save Word document
$filename = 'skillforge_submissions_' . date('Ymd_His') . '.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
ob_end_flush();
exit;
