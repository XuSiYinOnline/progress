<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>项目进度查询</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">

        <header>
            <h1>项目进度查询</h1>
        </header>

        <main class="query-form1">
            <form method="POST" action="query.php">
                <div class="form-group">
                    <input type="text" name="code" placeholder="请输入查询码" required style="width: 200px;">
                </div>
                <button type="submit" class="btn">查 询</button>
            </form>
	<!--            
            <div class="admin-link">
                <a href="login.php" class="btn admin-btn">登 录</a>
            </div>
    --> 			
        </main>
 	<!--       
        <footer>
            <p>&copy; <?= date('Y') ?> 项目进度查询. 保留所有权利.</p>
        </footer>
    </div>
	-->
	
</body>
</html>