<?php
require 'config.php';

$code = '';
$project = null;
$feedbacks = [];

// 处理查询请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize_input($_POST['code']);
} elseif (isset($_GET['code'])) {
    $code = sanitize_input($_GET['code']);
}

if (!empty($code)) {
    $projects = get_projects();
    
    foreach ($projects as $p) {
        if ($p['code'] === $code) {
            $project = $p;
            $feedbacks = array_filter(get_feedbacks(), function($f) use ($p) {
                return $f['project_id'] === $p['id'];
            });
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $project ? htmlspecialchars($project['name']) : '项目查询' ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1 style="margin-bottom: 30px;">项目进度查询</h1>
       <!-- <a href="index.php" class="btn home-btn">返回首页</a>-->
        </header>
        
        <main class="query-result">
            <?php if ($project): ?>
                <div class="project-details">
                    <h2><?= htmlspecialchars($project['name']) ?></h2>
                    
                    <div class="progress-container">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $project['progress'] ?>%"></div>
                        </div>
                        <span class="progress-text">当前进度: <?= $project['progress'] ?>%</span>
                    </div>
                    
                    <?php if (!empty($project['description'])): ?>
                        <div class="project-description">
                            <h3>项目描述</h3>
                            <p><?= nl2br(htmlspecialchars($project['description'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($project['files'])): ?>
                        <div class="project-files">
                            <h3>项目文件</h3>
                            <ul class="file-list">
                                <?php foreach ($project['files'] as $file): ?>
                                    <li>
                                        <a href="uploads/<?= $file['path'] ?>" target="_blank" class="file-link">
                                            <?= htmlspecialchars($file['name']) ?>
                                        </a>
                                        <small>(<?= strtoupper($file['type']) ?>, <?= format_size($file['size']) ?>)</small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="feedback-section">
                        <h3>反馈信息</h3>
                        <form method="POST" action="feedback.php" class="feedback-form">
                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                            <input type="hidden" name="code" value="<?= $code ?>">
                            
                            <div class="form-group">
                                <textarea name="message" placeholder="请输入您的反馈意见..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn">提交反馈</button>
                        </form>
                    </div>
                    
                    <?php if (!empty($feedbacks)): ?>
                        <div class="feedback-history">
                            <h3>历史反馈</h3>
                            <?php foreach ($feedbacks as $fb): ?>
                                <div class="feedback-item">
                                    <div class="feedback-meta">
                                        <span class="feedback-date"><?= $fb['date'] ?></span>
                                    </div>
                                    
                                    <div class="feedback-content">
                                        <p><?= nl2br(htmlspecialchars($fb['message'])) ?></p>
                                    </div>
                                    
                                    <?php if (!empty($fb['reply'])): ?>
                                        <div class="feedback-reply">
                                            <strong style="color: red;">管理员回复:</strong>
                                            <p><?= nl2br(htmlspecialchars($fb['reply'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-project">
                    <?php if (!empty($code)): ?>
                        <div class="alert alert-danger">无效的查询码，请检查后重试</div>
                    <?php endif; ?>
                    
                    <form method="POST" class="query-form">
                        <div class="form-group">
                            <input type="text" name="code" placeholder="请输入查询码" value="<?= htmlspecialchars($code) ?>" required>
                        </div>
                        <button type="submit" class="btn">查询</button>
                    </form>
                </div>
            <?php endif; ?>
        </main>
   <!--         
        <footer>
            <p>&copy; <?= date('Y') ?> 项目进度查询. 保留所有权利.</p>
        </footer>
	-->	
    </div>
</body>
</html>