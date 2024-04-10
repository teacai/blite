<?php
    $config = (object)[
        'jwtKey' => '123450099joKo1',
        'dbPath' => 'database.sqlite',
        'adminPassword' => 'admin',
        'adminUsername' => 'admin',
        'adminPath' => 'admin'
    ];

    require 'blite.php';

    $db = new BLite\DB($config);
    $router = new BLite\Router($config);

    if ($router->isAdminRoute()) {
        require 'admin.php';
        exit(0);
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
    <title><?php echo 'bLite | '.$page->title; ?></title>
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
            <a href="home" class="active">Home</a>
        </div>
    </nav>
    <main>
    <?php
    if ( $page->content_type == 'php' ) {
        eval( $page->content );
    } elseif ( $page->content_type == 'html' ) {
        echo $page->content;
    } else {
        echo "<pre>".$page->content."</pre>";
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
