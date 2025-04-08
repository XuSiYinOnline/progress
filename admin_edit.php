<?php
require 'config.php';
require_admin();

$project_id = $_GET['id'] ?? '';
if (empty($project_id)) {
    header('Location: admin.php');
    exit;
}

$projects = get_projects();
$project = null;

foreach ($projects as $p) {
    if ($p['id'] === $project_id) {
        $project = $p;
        break;
    }
}

if (!$project) {
    $_SESSION['error'] = "项目不存在或已被删除";
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('HTTP/1.1 403 Forbidden');
        die("非法请求");
    }

    // 更新项目信息
    $project['name'] = sanitize_input($_POST['name']);
    $project['description'] = sanitize_input($_POST['description']);
    $project['progress'] = (int)$_POST['progress'];

    // 处理文件上传
    if (!empty($_FILES['new_files']['tmp_name'][0])) {
        foreach ($_FILES['new_files']['tmp_name'] as $key => $tmp) {
            if ($_FILES['new_files']['error'][$key] === UPLOAD_ERR_NO_FILE) continue;
            
            $file = [
                'name' => $_FILES['new_files']['name'][$key],
                'type' => $_FILES['new_files']['type'][$key],
                'tmp_name' => $tmp,
                'error' => $_FILES['new_files']['error'][$key],
                'size' => $_FILES['new_files']['size'][$key]
            ];
            
            $upload = handle_upload($file, $project_id);
            if (isset($upload['error'])) {
                $_SESSION['error'] = $upload['error'];
            } elseif ($upload) {
                $project['files'][] = $upload;
            }
        }
    }

    // 处理文件删除
    if (!empty($_POST['delete_files'])) {
        $remaining_files = [];
        foreach ($project['files'] as $file) {
            if (!in_array($file['path'], $_POST['delete_files'])) {
                $remaining_files[] = $file;
            } else {
                @unlink(UPLOAD_DIR . $file['path']);
            }
        }
        $project['files'] = $remaining_files;
    }

    // 保存更新
    foreach ($projects as &$p) {
        if ($p['id'] === $project_id) {
            $p = $project;
            break;
        }
    }
    
    save_projects($projects);
    $_SESSION['success'] = "项目更新成功";
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑项目 - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container admin-container">
        <header class="admin-header">
            <h1>编辑项目: <?= htmlspecialchars($project['name']) ?></h1>
            <div class="admin-actions">
                <a href="admin.php" class="btn">返回管理</a>
                <a href="logout.php" class="btn logout-btn">退出登录</a>
            </div>
        </header>
        
        <main class="edit-project">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="project-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="name">项目名称</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($project['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">项目描述</label>
                    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($project['description']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="progress">当前进度</label>
                    <input type="range" id="progress" name="progress" min="0" max="100" value="<?= $project['progress'] ?>" required>
                    <span id="progress-value"><?= $project['progress'] ?>%</span>
                </div>
                
                <div class="form-group">
                    <label>现有文件</label>
                    <?php if (empty($project['files'])): ?>
                        <p class="no-files">暂无文件</p>
                    <?php else: ?>
                        <div class="file-list">
                            <?php foreach ($project['files'] as $file): ?>
                                <div class="file-item">
                                    <input type="checkbox" id="delete_<?= $file['path'] ?>" name="delete_files[]" value="<?= $file['path'] ?>">
                                    <label for="delete_<?= $file['path'] ?>">
                                        <a href="uploads/<?= $file['path'] ?>" target="_blank" class="file-link">
                                            <?= htmlspecialchars($file['name']) ?>
                                        </a>
                                        <small>(<?= strtoupper($file['type']) ?>, <?= format_size($file['size']) ?>)</small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="new_files">上传新文件</label>
                    <input type="file" id="new_files" name="new_files[]" multiple>
                    <small>支持格式: JPG, PNG, GIF, PDF, DOC, XLS 等 (最大5MB)</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">保存修改</button>
                    <a href="admin.php" class="btn btn-cancel">取消</a>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        // 实时显示进度条值
        document.getElementById('progress').addEventListener('input', function() {
            document.getElementById('progress-value').textContent = this.value + '%';
        });
    </script>
</body>
</html>