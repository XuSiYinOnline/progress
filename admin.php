<?php
require 'config.php';
require_admin();

$projects = get_projects();
$feedbacks = get_feedbacks();

// 按日期降序排序项目
usort($projects, function($a, $b) {
    return strtotime($b['created']) - strtotime($a['created']);
});

// 按日期降序排序反馈
usort($feedbacks, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container admin-container">
        <header class="admin-header">
            <h1>管理后台</h1>
            <div class="admin-actions">
                <a href="logout.php" class="btn logout-btn">退出登录</a>
                <a href="index.php" class="btn">返回首页</a>
            </div>
        </header>
        
        <div class="admin-content">
            <section class="new-project-section" style="margin-bottom: 25px;">
                <h2 style="margin-bottom: 25px;">新建项目</h2>
                <form method="POST" action="admin_action.php?action=create" enctype="multipart/form-data" class="project-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="form-group">
                        <label for="name">项目名称</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">项目描述</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="progress">当前进度</label>
                        <input type="range" id="progress" name="progress" min="0" max="100" value="0" required>
                        <span id="progress-value">0%</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="files">上传文件</label>
                        <input type="file" id="files" name="files[]" multiple>
                        <small>支持格式: JPG, PNG, GIF, PDF, DOC, XLS 等 (最大5MB)</small>
                    </div>
                    
                    <button type="submit" class="btn">创建项目</button>
                </form>
            </section>
            
            <section class="projects-section">
                <h2 style="margin-bottom: 25px;">项目管理</h2>
                <?php if (empty($projects)): ?>
                    <p class="no-data">暂无项目</p>
                <?php else: ?>
                    <div class="projects-list">
                        <?php foreach ($projects as $p): ?>
                            <div class="project-card">
                                <div class="project-header">
                                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                                    <div class="project-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $p['progress'] ?>%"></div>
                                        </div>
                                        <span><?= $p['progress'] ?>%</span>
                                    </div>
                                </div>
                                
                                <div class="project-meta">
                                    <p><strong>创建时间:</strong> <?= $p['created'] ?></p>
                                    <p><strong>查询码:</strong> <code><?= $p['code'] ?></code></p>
                                </div>
                                
                                <div class="project-actions">
                                    <a href="admin_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm">编辑</a>
                                    <a href="admin_action.php?action=delete&id=<?= $p['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('确定要删除这个项目吗？所有相关文件和反馈也将被删除！')">删除</a>
                                    <a href="query.php?code=<?= $p['code'] ?>" class="btn btn-sm" target="_blank">预览</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <section class="feedbacks-section">
                <h2>客户反馈</h2>
                <?php if (empty($feedbacks)): ?>
                    <p class="no-data">暂无客户反馈</p>
                <?php else: ?>
                    <div class="feedbacks-list">
                        <?php foreach ($feedbacks as $fb): 
                            // 查找关联项目
                            $project = null;
                            foreach ($projects as $p) {
                                if ($p['id'] === $fb['project_id']) {
                                    $project = $p;
                                    break;
                                }
                            }
                        ?>
                            <div class="feedback-card">
                                <div class="feedback-header">
                                    <h4>
                                        <?= $project ? htmlspecialchars($project['name']) : '已删除项目' ?>
                                        <small>(ID: <?= $fb['project_id'] ?>)</small>
                                    </h4>
                                    <span class="feedback-date"><?= $fb['date'] ?></span>
                                </div>
                                
                                <div class="feedback-content">
                                    <p><strong>客户反馈:</strong> <?= nl2br(htmlspecialchars($fb['message'])) ?></p>
                                    
                                    <?php if (!empty($fb['reply'])): ?>
                                        <div class="feedback-reply">
                                            <strong style="color: red;">管理员回复:</strong>
                                            <p><?= nl2br(htmlspecialchars($fb['reply'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <form method="POST" action="admin_action.php?action=reply" class="feedback-reply-form">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="fb_id" value="<?= $fb['id'] ?>">
                                    
                                    <div class="form-group">
                                        <textarea name="reply" placeholder="输入回复内容..."><?= !empty($fb['reply']) ? htmlspecialchars($fb['reply']) : '' ?></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-sm"><?= empty($fb['reply']) ? '回复' : '更新回复' ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
    
    <script>
        // 实时显示进度条值
        document.getElementById('progress').addEventListener('input', function() {
            document.getElementById('progress-value').textContent = this.value + '%';
        });
    </script>
</body>
</html>