<?php
require 'config.php';

// 仅允许POST请求访问
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 403 Forbidden');
    die("禁止直接访问本页面");
}

// 验证必要参数存在
if (empty($_POST['project_id']) || empty($_POST['code']) || empty($_POST['message'])) {
    $_SESSION['error'] = "所有字段都是必填的";
    header("Location: query.php?code=" . urlencode($_POST['code'] ?? ''));
    exit;
}

// 验证项目真实性
$project_id = sanitize_input($_POST['project_id']);
$code = sanitize_input($_POST['code']);
$projects = get_projects();
$project_exists = false;

foreach ($projects as $p) {
    if ($p['id'] === $project_id && $p['code'] === $code) {
        $project_exists = true;
        break;
    }
}

if (!$project_exists) {
    $_SESSION['error'] = "关联项目不存在或查询码不匹配";
    header("Location: query.php?code=" . urlencode($code));
    exit;
}

// 处理反馈提交
$feedback = [
    'id' => uniqid(),
    'project_id' => $project_id,
    'message' => sanitize_input($_POST['message']),
    'date' => date('Y-m-d H:i:s'),
    'reply' => ''
];

$feedbacks = get_feedbacks();
$feedbacks[] = $feedback;
save_feedbacks($feedbacks);

$_SESSION['success'] = "反馈提交成功！感谢您的意见。";
header("Location: query.php?code=" . urlencode($code));
exit;
?>