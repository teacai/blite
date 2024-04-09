<?php
    $isAdmin = $router->isAdmin();

    if ($isAdmin) {
        if (isset($_POST['slug'])) {
            $page = $db->savePage($_POST['slug'], $_POST['content'], $_POST['title'], $_POST['access'], $_POST['contentType'], $_POST['publishDate']);
        }
        if (isset($_GET['slug'])) {
            if ($_GET['slug'] == '_new') {
                $page = (object)['slug' => '', 'title' => '', 'content' => '', 'contentType' => 'html',
                 'access' => 'public', 'published_at' => date("Y-m-d\TH:i")];
            } else {
                $page = $db->getPage($_GET['slug']);
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
    </style>
</head>
<body>
<nav>
    <div class="left">
        <a href="" class="logo">bLite</a>
    </div>
    <div class="right">
        <a href="admin">Admin</a>
        <a href="home" class="active">Home</a>
    </div>
</nav>
<main>
    <?php if (!$isAdmin) { ?>
        <div class="modal">
            <div class="box form">
            <form method="post" action="admin">
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

    <?php if ($router->view() == 'dashboard') {
            if ($config->adminUsername == 'admin' || $config->adminPassword == 'admin') {
                echo '<alert class="danger">For your security, please change the default admin username and password (in index.php).</alert>';
            }
        ?>
        <h3> Pages: </h3>
        <a href="admin?view=page&slug=_new">New page</a>
        <ol>
        <?php
        $pageNo = isset($_GET['pno']) ? ($_GET['pno'] - 1) : 0;
        $itemsPerPage = isset($_GET['pco']) ? $_GET['pco'] : 10;
        $pages = $db->getPages($pageNo, $itemsPerPage);

        foreach($pages as $page) {
            echo "<li><a href='$page->slug'>$page->title</a> [<a href='admin?view=page&slug=$page->slug'>Edit</a>]</li>";
        }
        ?>
        </ol>
    <?php
        $pagesCount = $db->getPagesCount()->count;

        $totalPages = floor($pagesCount/$itemsPerPage) + ($pagesCount%$itemsPerPage == 0 ? 0 : 1);
        $currentPageNo = $pageNo + 1;
        $nextPageNo = $currentPageNo + 1;
        $previousPageNo = $currentPageNo - 1;
        $next = $nextPageNo > $totalPages ? "" : "<a href='?pno=$nextPageNo&pco=$itemsPerPage'>[$nextPageNo]</a>";
        $previous = $previousPageNo < 1 ? "" : "<a href='?pno=$previousPageNo&pco=$itemsPerPage'>[$previousPageNo]</a>";
        echo "<div class='pages'> $previous [<a>$currentPageNo</a>/<a>$totalPages</a>] $next </div>";
    } ?>
    <?php if ($router->view() == 'page') { ?>
    <form method="post" action="admin" class="form">
        <div>
            <label for="slug">Slug:</label>
            <input id="slug" type="text" name="slug" placeholder="slug" value="<?php echo $page->slug; ?>"/>
        </div>
        <div>
            <label for="title">Title:</label>
            <input id="title" type="text" name="title" placeholder="title" value="<?php echo $page->title; ?>"/>
        </div>
        <div>
            <label for="contentType">Content type:</label>
            <select id="contentType" name="contentType">
                <option value="html" <?php echo ($page->contentType=='html') ? 'selected' : '' ; ?>>HTML</option>
                <option value="php" <?php echo ($page->contentType=='php') ? 'selected' : '' ; ?>>PHP</option>
                <option value="text" <?php echo ($page->contentType=='text') ? 'selected' : '' ; ?>>TEXT</option>
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
            <input id="publishDate" type="datetime-local" name="publishDate" value="<?php echo $page->published_at; ?>"/>
        </div>
        <div class="textarea">
            <label for="content">Content:</label>
            <textarea id="content" name="content"><?php echo $page->content; ?></textarea>
        </div>
        <div>
            <label for="savePage">&nbsp;</label>
            <button id="savePage" type="submit">Save page</button>
            <button id="cancel" type="cancel" onclick="event.preventDefault();window.location.href='admin';return false;">Cancel</button>
        </div>
    </form>
    <?php } ?>
</main>
</body>
</html>