<?php
    $config = (object)[
        'jwtKey' => 'change-me-key-123',
        'dbPath' => 'database.sqlite',
        'filesPath' => 'up',
        'filesDailyFolders' => false,
        'adminPassword' => 'admin',
        'adminUsername' => 'admin',
        'adminPath' => 'admin',
        'publicPagesPath' => 'posts',
        'autoUpdateEnabled' => true,
        // DO NOT CHANGE START
        'repoUrl' => 'https://github.com/teacai/blite',
        'currentVersion' => '0.2',
        'latestVersion' => '0.2',
        'lastUpdateCheck' => '1713795423',
        // DO NOT CHANGE END--
    ];

    require 'blite.php';

    $db = new BLite\DB($config);
    $router = new BLite\Router($config);

    if ($router->isAdminRoute()) {
        require 'admin.php';
        exit(0);
    } else if ($router->isPublicPagesRoute()) {
        $pages = $db->getPublicPages();
    } else {
        $page = $db->getPage($router->route());
        $public = $page && $page->access === 'public';
        $noAccess = !$public && !$router->isAdmin();
        $notPublished = !$page || is_null($page->published_at) || empty($page->published_at)
                            || (strtotime($page->published_at) - strtotime(date("Y-m-d\TH:i")) > 0);
        if (!$page || $noAccess || $notPublished) {
            $page = (object)['title' => '404 Not found', 'content_type' => 'txt',
                'content' => 'Page could not be found: '.$router->route()];
        }
    }

?>

<!doctype html>
<html lang=en>
<head>
    <meta charset=utf-8>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo 'bLite | '.(isset($page) ? $page->title : 'Posts'); ?></title>
    <style>
        <?php include 'index.inline.css'; ?>
    </style>
</head>
<body>
    <nav>
        <div class="left">
            <a href="" class="logo">bLite</a>
        </div>
        <div class="right">
            <?php if ($router->isAdmin()) echo '<a href="'.$config->adminPath.'">Admin</a>' ?>
            <?php echo '<a href="'.$config->publicPagesPath.'">Posts</a>' ?>
            <a href="home" class="active">Home</a>
        </div>
    </nav>
    <main>
    <?php
    if (isset($page)) {
        if ( $page->content_type == 'php' ) {
            eval( $page->content );
        } elseif ( $page->content_type == 'html' ) {
            echo $page->content;
        } else {
            echo "<pre>".$page->content."</pre>";
        }
    }
    if (isset($pages)) {
        echo "<h3>Pages</h3>";
        foreach($pages as $page) {
            $pub = str_replace('T', ' ', $page->published_at);
            $author = $page->author ? '('.$page->author.')' : '';
            echo "<div><a href='$page->slug'>[$pub] <strong>$page->title</strong> $author</a></div>";
        }
    }
    ?>
    </main>
    <footer>
    </footer>
    <script>
        <?php include 'index.inline.js' ?>
    </script>
</body>
</html>
