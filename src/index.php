<?php
    $config = (object)[
        'jwtKey' => '123450099joKo1',
        'dbPath' => 'database.sqlite',
        'adminPassword' => 'admin',
        'adminUsername' => 'admin'
    ];

    require 'blite.php';

    $db = new BLite\DB($config);
    $router = new BLite\Router($config);

    if ($router->isAdminRoute()) {
        require 'admin.php';
        exit(0);
    } else {
        $page = $db->getPage($router->route());
        if (!$page) {
            $page = (object)['title' => '404 Not found', 'contentType' => 'txt',
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
            <a href="admin">Admin</a>
            <a href="home" class="active">Home</a>
        </div>
    </nav>
    <main>
    <?php
    if ( $page->contentType == 'php' ) {
        eval( $page->content );
    } elseif ( $page->contentType == 'html' ) {
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
