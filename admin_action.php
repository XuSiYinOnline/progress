<?php
require 'config.php';
require_admin();

// 验证CSRF令牌
if (!isset($_GET['action']) || 
    !isset($_REQUEST['csrf_token']) || 
    $_REQUEST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('HTTP/1.1 403 Forbidden');
    die("非法请求");
}

switch ($_GET['action']) {
    case 'create':
        // 验证输入
        if (empty($_POST['name']) || !isset($_POST['progress'])) {
            $_SESSION['error'] = "项目名称和进度是必填项";
            header('Location: admin.php');
            exit;
        }

        $project = [
            'id' => uniqid(),
            'name' => sanitize_input($_POST['name']),
            'code' => generate_code(),
            'progress' => (int)$_POST['progress'],
            'description' => sanitize_input($_POST['description']),
            'files' => [],
            'created' => date('Y-m-d H:i:s')
        ];

        // 处理文件上传
        if (!empty($_FILES['files'])) {
            foreach ($_FILES['files']['tmp_name'] as $key => $tmp) {
                if ($_FILES['files']['error'][$key] === UPLOAD_ERR_NO_FILE) continue;
                
                $file = [
                    'name' => $_FILES['files']['name'][$key],
                    'type' => $_FILES['files']['type'][$key],
                    'tmp_name' => $tmp,
                    'error' => $_FILES['files']['error'][$key],
                    'size' => $_FILES['files']['size'][$key]
                ];
                
                $upload = handle_upload($file, $project['id']);
                if (isset($upload['error'])) {
                    $_SESSION['error'] = $upload['error'];
                } elseif ($upload) {
                    $project['files'][] = $upload;
                }
            }
        }

        $projects = get_projects();
        $projects[] = $project;
        save_projects($projects);
        $_SESSION['success'] = "项目创建成功！查询码: " . $project['code'];
        break;

    case 'reply':
        if (empty($_POST['fb_id']) || !isset($_POST['reply'])) {
            $_SESSION['error'] = "反馈ID和回复内容是必填项";
            header('Location: admin.php');
            exit;
        }

        $feedbacks = get_feedbacks();
        $found = false;
        
        foreach ($feedbacks as &$fb) {
            if ($fb['id'] === $_POST['fb_id']) {
                $fb['reply'] = sanitize_input($_POST['reply']);
                $found = true;
                break;
            }
        }
        
        if ($found) {
            save_feedbacks($feedbacks);
            $_SESSION['success'] = "回复已保存";
        } else {
            $_SESSION['error'] = "未找到指定的反馈";
        }
        break;

    case 'delete':
        if (empty($_GET['id'])) {
            $_SESSION['error'] = "未指定要删除的项目";
            header('Location: admin.php');
            exit;
        }

        $project_id = $_GET['id'];
        $projects = get_projects();
        $feedbacks = get_feedbacks();
        $deleted = false;
        
        // 删除项目及其相关文件
        foreach ($projects as $key => $project) {
            if ($project['id'] === $project_id) {
                // 删除项目文件
                foreach ($project['files'] as $file) {
                    @unlink(UPLOAD_DIR . $file['path']);
                }
                
                // 删除项目
                unset($projects[$key]);
                $deleted = true;
                break;
            }
        }
        
        // 重新索引数组
        $projects = array_values($projects);
        
        // 删除相关反馈
        $feedbacks = array_filter($feedbacks, function($fb) use ($project_id) {
            return $fb['project_id'] !== $project_id;
        });
        
        if ($deleted) {
            save_projects($projects);
            save_feedbacks($feedbacks);
            $_SESSION['success'] = "项目已成功删除";
        } else {
            $_SESSION['error'] = "未找到要删除的项目";
        }
        break;

    default:
        $_SESSION['error'] = "无效的操作";
}

header('Location: admin.php');
exit;
?>