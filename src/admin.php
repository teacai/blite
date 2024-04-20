<?php
    $isAdmin = $router->isAdmin();
    if ($isAdmin) {
        $files = new BLite\Files($config);
        if (isset($_POST['action']) && $_POST['action'] === 'Backup') {
            $confirmation = 'Backup file created at: '.$files->createBackup();
        } elseif (isset($_POST['action']) && isset($_POST['file'])) {
            if ($_POST['action'] === 'Delete') {
                $confirmation = $files->deleteFiles($_POST['file']);
            } elseif ($_POST['action'] === 'Rename') {
                $confirmation = $files->renameFile($_POST['file'], $_POST['newFile']);
            }
        } elseif (isset($_GET['action']) && $_GET['action'] === 'upload' && isset($_FILES['files'])) {
            $uploadedFiles = $files->saveUploadedFiles();
            $confirmation = '<div>Files uploaded:</div>';
            foreach($uploadedFiles as $f) $confirmation = $confirmation."<div>$f</div>";
        } elseif (isset($_POST['slug']) || isset($_POST['content'])) {
            try {
                if (!isset($_POST['slug']) || $_POST['slug'] === '' || ctype_space($_POST['slug'])) {
                    $error = "Error: slug is required.";
                } else {
                    $page = $db->savePage($_POST['id'], $_POST['slug'], $_POST['content'], $_POST['title'],
                                $_POST['author'], $_POST['access'], $_POST['content_type'], $_POST['published_at']);
                }
            } catch(Exception $e) {
                $error = "Error: ".$e->getMessage();
            }
            if (isset($error)) {
                $page = (object)['id' => $_POST['id'], 'slug' => $_POST['slug'], 'content' => $_POST['content'],
                              'title' => $_POST['title'], 'author' => $_POST['author'], 'access' => $_POST['access'],
                              'content_type' => $_POST['content_type'], 'published_at' => $_POST['published_at']];
            }
        } elseif (isset($_GET['slug'])) {
            if ($_GET['slug'] == '_new') {
                $page = (object)['id' => '', 'slug' => '', 'title' => '', 'content' => '', 'content_type' => 'html',
                 'author' => '', 'access' => 'public', 'published_at' => date("Y-m-d\TH:i")];
            } else {
                $page = $db->getPage($_GET['slug']);
            }
            if (isset($_GET['action']) && $_GET['action'] === 'delete' && $page) {
                $db->deletePage($page->slug);
                $confirmation = 'Page deleted: '.$page->slug;
            }
        }
    }
?>

<!doctype html>
<html lang=en>
<head>
    <meta charset=utf-8>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>bLite | Admin</title>
    <style>
        <?php include "index.inline.css"; ?>
        * { box-sizing: border-box; }
        html, body, main, .modal, form {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        main {
            padding: 0.5rem;
            overflow: auto;
        }
        nav {
            background-color: #333;
            overflow: auto;
            min-height: 2rem;
            width: 100%;
        }
        nav a {
            display: inline-block;
            padding: 0.7rem 1.1rem;
            text-decoration: none;
            color: #fff;
        }
        nav a:hover { color: #ccf; }
        nav .active { color: #ccfa; }
        img { max-width: 100%; }
        alert {
            display: block;
            margin: 1rem 4rem;
            padding: 1rem;
            border: 1px solid #333;
        }
        .danger {
            border-color: #aa3333;
            color: #aa3333;
        }
        .warn {
            border-color: #ffaa33;
            color: #ffaa33;
        }
        .info {
            border-color: #3333aa;
            color: #3333aa;
        }
        .success {
            border-color: #33aa33;
            color: #33aa33;
        }
        .left { float: left; }
        .right { float: right; }
        .modal {
            position: relative;
            background-color: #fff;
        }
        .modal .box {
            padding: 0.5rem;
            position: absolute;
            margin: auto;
            top: 0;
            bottom: 0;
            right: 0;
            left: 0;
            max-width: 30rem;
            max-height: 12rem;
            width: 99%;
            height: 99%;
        }
        .form div { margin: 0.1rem 0.3rem; }
        .form div * {
            margin: 0.1rem 0.2rem;
            display: inline-block;
            min-height: 1rem;
            font-size: 1rem;
        }
        .form div input, .form div label,
        .form div textarea, .form div select {
            width: calc(100% - 0.4rem);
        }
        .form div.textarea, .form textarea {
            height: 80%
        }
        .btn {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border: 1px solid #333;
            text-decoration: none;
            font-size: 1rem;
        }
        .lines {
            display: block;
            width: 100%;
            padding: 0.2rem 0;
        }
        .lines > * {
            padding: 0.2rem 0;
            margin: 0 0.2rem 0.4rem 0;
            border-bottom: 1px dashed #333;
        }
        .lines > *:hover {
            background-color: #eeee;
        }
        .dt {
            min-width: 10.5rem;
            min-height: 2rem;
        }
        .pt {
            width: 100%;
            margin-top:0.2rem;
        }
        .fw {
            width: 100%;
            padding: 0.35rem;
            margin-top:0.2rem;
        }
        .il {
            width: auto;
            display: inline-block;
        }

        @media screen and (min-width: 718px) {
            .form div input, .form div select {
                width: calc(60% - 0.6rem);
            }
            .form div label {
                width: calc(40% - 0.6rem);
                text-align: right;
            }
            .lines > * {
                 border-width: 0;
            }
            .pt {
                margin-top: 0;
                width: calc(100% - 23.2rem);
            }
            .fw {
                width: calc(100% - 16.8rem);
            }
        }
    </style>
</head>
<body>
<nav>
    <div class="left">
        <a href="" class="logo">bLite</a>
    </div>
    <div class="right">
        <?php if ($router->isAdmin()) echo '<a href="'.$config->adminPath.'">Admin</a>' ?>
        <a href="home">Home</a>
    </div>
</nav>
<main>
    <?php if (!$isAdmin) { ?>
        <div class="modal">
            <div class="box form">
            <form method="post" action="<?php echo $config->adminPath; ?>">
                <input type="hidden" name="action" value="login"/>
                <div>
                    <label for="username">Username:</label>
                    <input id="username" type="text" name="username" placeholder="username"/>
                </div>
                <div>
                    <label for="password">Password:</label>
                    <input id="password" type="password" name="password" placeholder="password"/>
                </div>
                <div>
                    <label for="submit">&nbsp;</label>
                    <input id="submit" type="submit" value="Login"/>
                </div>
            </form>
            </div>
        </div>
    <?php die(); } ?>
    <?php if (isset($confirmation)) {
        echo '<alert class="info">'.$confirmation.'</alert>';
    } ?>
    <?php if ($router->view() == 'dashboard') {
            if ($config->adminUsername == 'admin' || $config->adminPassword == 'admin' ||
                $config->adminPath == 'admin' || $config->jwtKey == 'change-me-key-123') {
                echo '<alert class="danger">For your security, please change the default admin username, password and path (in index.php).</alert>';
            }
        ?>
        <h3> Pages </h3>

        <div class="lines">
            <div><a href="<?php echo $config->adminPath; ?>?view=page&slug=_new" class="btn">New page</a></div>
        <?php
        $pageNo = isset($_GET['pno']) ? ($_GET['pno'] - 1) : 0;
        $itemsPerPage = isset($_GET['pco']) ? $_GET['pco'] : 20;
        $pages = $db->getPages($pageNo, $itemsPerPage);

        foreach($pages as $page) {
            echo "<div><a href='$page->slug' class='btn'>View</a> <a href='$config->adminPath?action=delete&slug=$page->slug' class='btn'>Delete</a> "
            ."<a href='$config->adminPath?view=page&slug=$page->slug' class='btn'>Edit</a> "
            ."<span class='btn dt'>".((is_null($page->published_at) || empty($page->published_at)) ? "Not published" : date("Y-m-d H:i:s", strtotime($page->published_at)))."</span>"
            ." <a href='$config->adminPath?view=page&slug=$page->slug' class='btn pt'>$page->id | $page->title</a></div>";
        }
        ?>
        </div>
    <?php
        $pagesCount = $db->getPagesCount()->count;

        $totalPages = floor($pagesCount/$itemsPerPage) + ($pagesCount%$itemsPerPage == 0 ? 0 : 1);
        $currentPageNo = $pageNo + 1;
        $nextPageNo = $currentPageNo + 1;
        $previousPageNo = $currentPageNo - 1;
        $next = $nextPageNo > $totalPages ? "" : "<a href='?pno=$nextPageNo&pco=$itemsPerPage'>[$nextPageNo]</a>";
        $previous = $previousPageNo < 1 ? "" : "<a href='?pno=$previousPageNo&pco=$itemsPerPage'>[$previousPageNo]</a>";
        echo "<div class='pages'> $previous [<a>$currentPageNo</a>/<a>$totalPages</a>] $next </div>";
    ?>
    <hr/>
    <hr/>
    <hr/>
    <h3> Files </h3>
    <div class="lines">
        <div>
            <form action="<?php echo $config->adminPath; ?>?action=upload" class="form il" method="post" enctype="multipart/form-data">
                Files: <input name="files[]" type="file" multiple />
                Directory: <input name="files[]" type="file" webkitdirectory multiple />
                <input type="submit" class="btn" value="Upload" />
            </form>
            <form action="<?php echo $config->adminPath; ?>" class="form il" method="post">
                <input name="action" type="hidden" value="Backup" />
                <input type="submit" class="btn" value="Backup" />
            </form>
        </div>

        <?php
            $myFiles = $files->listFiles();
            foreach($myFiles as $f) {
                echo "<form action='' method='post'>"
                        ."<div class=''><input class='btn' type='submit' name='action' value='Delete'/> "
                        ."<input class='btn' type='submit' name='action' value='Rename'/> "
                        ."<a class='btn' href='$f' target='_blank'>Download</a> "
                        ."<input class='fw' type='text' name='newFile' value='$f'/></div>".
                        "<input type='hidden' name='file' value='$f'/></form>";
            }
        ?>
    </div>
    <?php } ?>
    <?php if ($router->view() == 'page') {
        if (isset($error)) {
            echo '<div><alert class="warn">'.$error.'</alert></div>';
        }
     ?>
    <form method="post" action="<?php echo $config->adminPath; ?>?view=page" class="form">
        <input type="hidden" name="id" value="<?php echo $page->id; ?>"/>
        <div>
            <label for="savePage">&nbsp;</label>
            <button id="savePage" type="submit" class="btn">Save page</button>
            <button id="cancel" type="cancel" class="btn"
                onclick="event.preventDefault();window.location.href='<?php echo $config->adminPath; ?>';return false;">Cancel</button>
            <a class="btn" href="#" onclick="window.location.href=document.querySelector('#slug').value">View</a>
        </div>
        <div>
            <label for="slug">Slug:</label>
            <input id="slug" type="text" name="slug" placeholder="slug" value="<?php echo $page->slug; ?>"/>
        </div>
        <div>
            <label for="title">Title:</label>
            <input id="title" type="text" name="title" placeholder="title" value="<?php echo $page->title; ?>"/>
        </div>
        <div>
            <label for="author">Author:</label>
            <input id="author" type="text" name="author" placeholder="author" value="<?php echo $page->author; ?>"/>
        </div>
        <div>
            <label for="contentType">Content type:</label>
            <select id="contentType" name="content_type">
                <option value="html" <?php echo ($page->content_type=='html') ? 'selected' : '' ; ?>>HTML</option>
                <option value="php" <?php echo ($page->content_type=='php') ? 'selected' : '' ; ?>>PHP</option>
                <option value="text" <?php echo ($page->content_type=='text') ? 'selected' : '' ; ?>>TEXT</option>
            </select>
        </div>
        <div>
            <label for="access">Access:</label>
            <select id="access" name="access">
                <option value="private" <?php echo ($page->access=='private') ? 'selected' : '' ; ?>>Private</option>
                <option value="public" <?php echo ($page->access=='public') ? 'selected' : '' ; ?>>Public</option>
            </select>
        </div>
        <div>
            <label for="publishDate">Publish date:</label>
            <input id="publishDate" type="datetime-local" name="published_at" value="<?php echo $page->published_at; ?>"/>
        </div>
        <div class="textarea">
            <label for="content">Content:</label>
            <textarea id="content" name="content"><?php echo $page->content; ?></textarea>
        </div>
    </form>
    <?php } ?>
</main>
</body>
</html>