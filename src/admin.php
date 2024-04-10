<?php
    $isAdmin = $router->isAdmin();
    $error = false;
    $confirmation = false;
    if ($isAdmin) {

        if (isset($_POST['slug']) || isset($_POST['content'])) {
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
            if ($error) {
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
    <?php if ($confirmation) {
        echo '<alert class="info">'.$confirmation.'</alert>';
    } ?>
    <?php if ($router->view() == 'dashboard') {
            if ($config->adminUsername == 'admin' || $config->adminPassword == 'admin' || $config->adminPath == 'admin') {
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
    } ?>
    <?php if ($router->view() == 'page') {
        if ($error) {
            echo '<div><alert class="warn">'.$error.'</alert></div>';
        }
     ?>
    <form method="post" action="<?php echo $config->adminPath; ?>?view=page" class="form">
        <input type="hidden" name="id" value="<?php echo $page->id; ?>"/>
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
        <div>
            <label for="savePage">&nbsp;</label>
            <button id="savePage" type="submit">Save page</button>
            <button id="cancel" type="cancel" onclick="event.preventDefault();window.location.href='<?php echo $config->adminPath; ?>';return false;">Cancel</button>
        </div>
    </form>
    <?php } ?>
</main>
</body>
</html>