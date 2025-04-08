<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 配置常量
define('DATA_DIR', __DIR__ . '/data/');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ADMIN_PASSWORD', password_hash('admin888', PASSWORD_DEFAULT)); // 默认密码admin123
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// 创建必要目录
if (!file_exists(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

// 初始化数据文件
if (!file_exists(DATA_DIR.'projects.json')) file_put_contents(DATA_DIR.'projects.json', '[]');
if (!file_exists(DATA_DIR.'feedbacks.json')) file_put_contents(DATA_DIR.'feedbacks.json', '[]');

/**
 * 获取所有项目数据
 */
function get_projects() {
    $data = file_get_contents(DATA_DIR.'projects.json');
    return json_decode($data, true) ?: [];
}

/**
 * 保存项目数据
 */
function save_projects($data) {
    file_put_contents(DATA_DIR.'projects.json', json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * 获取所有反馈数据
 */
function get_feedbacks() {
    $data = file_get_contents(DATA_DIR.'feedbacks.json');
    return json_decode($data, true) ?: [];
}

/**
 * 保存反馈数据
 */
function save_feedbacks($data) {
    file_put_contents(DATA_DIR.'feedbacks.json', json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * 生成随机查询码
 */
function generate_code($length = 8) {
    return strtoupper(substr(md5(uniqid()), 0, $length));
}

/**
 * 安全过滤输入数据
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * 处理文件上传
 */
function handle_upload($file, $project_id) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['error' => '不支持的文件类型'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => '文件大小超过限制'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => '文件上传错误: ' . $file['error']];
    }

    $filename = uniqid().'.'.$ext;
    $target = UPLOAD_DIR.$filename;
    
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return [
            'name' => $file['name'],
            'path' => $filename,
            'type' => $ext,
            'size' => $file['size'],
            'date' => date('Y-m-d H:i:s'),
            'project' => $project_id
        ];
    }
    
    return ['error' => '文件移动失败'];
}

/**
 * 验证管理员登录状态
 */
function require_admin() {
    if (!isset($_SESSION['admin'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * 格式化文件大小
 */
function format_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// 设置CSRF令牌
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>