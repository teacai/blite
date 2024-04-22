<?php
namespace BLite;

class Router {
    protected $page;
    protected $isAdmin;
    protected $jwt;
    protected $action;
    protected $view;
    protected $config;

    public function __construct($config) {
        $this->config = $config;
        $this->jwt = new JWT($config->jwtKey);

        $this->page = isset($_SERVER['page']) ? $_SERVER['page'] : \parse_url($_SERVER['REQUEST_URI'], \PHP_URL_PATH);
        $this->page = str_replace('/', '', $this->page);
        $this->page = $this->page == '' ? 'home' : $this->page;

        $this->action = isset($_POST['action']) ? $_POST['action'] : null;
        $this->view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

        if ($this->action == 'login') {
            $this->login();
        } else {
            $this->checkAdmin();
        }
    }
    public function isAdmin() {
        return $this->isAdmin;
    }

    public function isAdminRoute() {
        return $this->route() === $this->config->adminPath;
    }

    public function isPublicPagesRoute() {
        return $this->route() === $this->config->publicPagesPath;
    }

    public function view() {
        return $this->view;
    }

    public function route() {
        return $this->page;
    }

    public function login() {
        if (isset($_POST['username']) && isset($_POST['password'])) {
            if ($_POST['username'] == $this->config->adminUsername && $_POST['password'] == $this->config->adminPassword) {
                \setcookie('token', $this->jwt->encode(['app' => 'blite', 'role' => 'ADMIN']), time() + (86400 * 30), "/");
                $this->isAdmin = true;
            }
        }
    }

    public function checkAdmin() {
        if (isset($_COOKIE['token'])) {
            try {
                $token = $this->jwt->decode($_COOKIE['token']);
                if ($token['role'] == 'ADMIN') {
                    $this->isAdmin = true;
                    return true;
                } else {
                    \error_log('User not admin: '.$token);
                }
            } catch (\Exception $e) {
                \error_log('Invalid token: '.$_COOKIE['token']);
            }
        }
        return false;
    }
}

class DB {
    protected $path;
    protected $db;

    public function __construct($config) {
        $this->db = new \PDO("sqlite:".$config->dbPath);
        $this->initDatabase();
    }

    protected function initDatabase() {
        $query = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='pages';");
        $result = $query->fetch();

        if (!$result) {
            $result = $this->db->exec(
                "CREATE TABLE pages ("
                    ."id INTEGER PRIMARY KEY AUTOINCREMENT, "
                    ."slug VARCHAR(250) NOT NULL, "
                    ."title VARCHAR(250), "
                    ."author VARCHAR(120), "
                    ."content TEXT, "
                    ."content_type VARCHAR(16), "
                    ."access VARCHAR(16) DEFAULT 'public', "
                    ."updated_at TIMESTAMP, "
                    ."published_at TIMESTAMP, "
                    ."created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL"
                .")");
            $result = $this->db->exec("CREATE UNIQUE INDEX idx_pages_slug ON pages (slug)");
            $this->insertPage("home", "<h1>Hello</h1><p>This is homepage</p>", "Home page", "Admin", "public", "html", date("Y-m-d\TH:i"));
        }
    }

    public function savePage($id, $slug, $content, $title = '', $author = null, $access = 'public', $contentType = 'html', $publishDate = null) {
        if ($id != null && $id !== '') {
            $this->updatePage($slug, $content, $title, $author, $access, $contentType, $publishDate);
        } else {
            $this->insertPage($slug, $content, $title, $author, $access, $contentType, $publishDate);
        }
        return $this->getPage($slug);
    }

    public function updatePage($slug, $content, $title = '', $author = null, $access = 'public', $contentType = 'html', $publishDate = null) {
            $query = $this->db->prepare("UPDATE pages SET "
                                            ."content = :content, title = :title, author = :author, content_type = :content_type, "
                                            ."published_at = :published_at, updated_at = :updated_at, access = :access "
                                            ."WHERE slug = :slug");
            $query->execute([':slug' => $slug, ':content' => $content, ':author' => $author, ':access' => $access,
                             ':updated_at' => date("Y-m-d\TH:i"), ':title' => $title, ':content_type' => $contentType,
                             ':published_at' => $publishDate]);
            $page = $query->fetchObject();
            return $page;
    }

    public function insertPage($slug, $content, $title = '', $author = null, $access = 'public', $contentType = 'html', $publishDate = null) {
            $query = $this->db->prepare("INSERT INTO pages (slug, content, title, author, access, content_type, created_at, updated_at, published_at) "
                                ."VALUES (:slug, :content, :title, :author, :access, :content_type, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :published_at)");
            $query->execute([':slug' => $slug, 'content' => $content, ':title' => $title, ':author' => $author,
                             ':access' => $access, ':content_type' => $contentType, ':published_at' => $publishDate]);
            $page = $query->fetchObject();
            return $page;
    }

    public function deletePage($slug) {
            $query = $this->db->prepare("DELETE FROM pages WHERE slug = :slug");
            $query->execute([':slug' => $slug]);
            $result = $query->fetchObject();
            return $result;
    }

    public function getPage($slug) {
        try {
            $query = $this->db->prepare("SELECT * FROM pages WHERE slug = :slug");
            $query->execute([':slug' => $slug]);
            $page = $query->fetchObject();

            return $page;
        } catch(\Exception $e) {
            \error_log('Error getting page with slug '.$slug, $e);
        }
    }

    public function getPagesCount() {
        $query = $this->db->prepare("SELECT count(slug) as count FROM pages");
        $query->execute();
        return $query->fetchObject();
    }

    public function getPages($pageNo = 0, $pageSize = 10) {
        try {
            $query = $this->db->prepare("SELECT * FROM pages ORDER BY created_at DESC LIMIT :pageStart, :pageSize");
            $query->execute([':pageStart' => $pageNo * $pageSize, ':pageSize' => $pageSize]);
            $pages = $query->fetchAll(\PDO::FETCH_CLASS);

            return $pages;
        } catch(\Exception $e) {
            \error_log('Error getting page with slug '.$slug, $e);
        }
    }
    public function getPublicPages($pageNo = 0, $pageSize = 10) {
        try {
            $query = $this->db->prepare(
                "SELECT slug, title, author, published_at FROM pages "
                    ."WHERE access = 'public' AND published_at < CURRENT_TIMESTAMP "
                    ."ORDER BY published_at DESC LIMIT :pageStart, :pageSize");
            $query->execute([':pageStart' => $pageNo * $pageSize, ':pageSize' => $pageSize]);
            $pages = $query->fetchAll(\PDO::FETCH_CLASS);

            return $pages;
        } catch(\Exception $e) {
            \error_log('Error getting page with slug '.$slug, $e);
        }
    }
}

class Files {
    protected $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function saveUploadedFiles() {
        $count = count($_FILES['files']['name']);
        $result = [];
        for($i=0 ; $i < $count ; $i++) {
          $tmpFilePath = $_FILES['files']['tmp_name'][$i];
          if ($tmpFilePath != ""){
            $newFile = $this->saveFile($tmpFilePath, $_FILES['files']['name'][$i], $_FILES['files']['full_path'][$i]);
            array_push($result, $newFile);
          }
        }
        return $result;
    }

    public function renameFile($oldName, $newName) {
        if (\file_exists($oldName) && \is_writable($oldName)
                && $this->fileInStorage($oldName) && $this->fileInStorage($newName)) {
            $parent = \dirname($newName);
            if (!is_dir($parent) && !mkdir($parent, 01777, true)) {
                return "Cannot store to folder $parent";
            }
            if (\rename($oldName, $newName)) {
                return "File was renamed to $newName.";
            } else {
                return "Error renaming the file $oldName";
            }
        } else {
            return "File $oldName does not exist or is not writable.";
        }
    }

    public function deleteFiles($filename) {
        if (\file_exists($filename) && \is_writable($filename)) {
            if ($this->fileInStorage($filename) && \unlink($filename)) {
                return "File $filename has been deleted successfully.";
            } else {
                return "Error deleting the file $filename";
            }
        } else {
            return "File $filename does not exist or is not writable.";
        }
    }

    protected function fileInStorage($filename) {
        return strpos($filename, $this->config->filesPath) === 0 && strpos($filename, '/../') === false;
    }

    public function createBackup() {
        $rootPath = \realpath('.');
        $rootPath = \rtrim($rootPath, '\\/');
        $date = date("Y-m-d-H-i");
        $backupPath = $this->getStoragePath('backups')."/blite.$date.backup.zip";
        $zip = new \ZipArchive();
        $zip->open($backupPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($rootPath), \RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $name = $file->getFilename();
                if (\substr($name, 0, 6) != 'blite.' || \substr($name, -11) != '.backup.zip') {
                    $filePath = $file->getRealPath();
                    $relativePath = \substr($filePath, strlen($rootPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        $zip->close();
        return $backupPath;
    }

    public function listFiles() {
        $pageNo = isset($_GET['fno']) ? ($_GET['fno'] - 1) : 0;
        $itemsPerPage = isset($_GET['fco']) ? $_GET['fco'] : 20;

        $files = $this->scanFiles($this->config->filesPath);

        return array_slice($files, $pageNo * $itemsPerPage, $itemsPerPage);
    }

    public function saveFile($tmpFilePath, $fileName, $fullFilePath) {
        if ($fileName !== $fullFilePath) {
            $newFilePath = $this->getStoragePath(dirname($fullFilePath)).'/'.$fileName;
        } else {
            $newFilePath = $this->getStoragePath().'/'.$fileName;
        }
        if($this->fileInStorage($newFilePath) && move_uploaded_file($tmpFilePath, $newFilePath)) {
            \chmod($newFilePath, 0644);
            return $newFilePath;
        }
        throw new \Exception('Failed to save file: '.$fileName);
    }

    protected function scanFiles($path) {
        $files = \glob($path."/*", \GLOB_NOSORT);
        $dirs = \glob($path."/*", \GLOB_ONLYDIR|\GLOB_NOSORT);
        $files = \array_diff($files, $dirs);

        foreach($dirs as $dir) {
            $sub = $this->scanFiles($dir);
            $files = \array_merge($files, $sub);
        }
        return $files;
    }

    protected function getStoragePath($extra = '') {
        $path = $this->config->filesPath;
        if ($this->config->filesDailyFolders) {
            $year = base_convert(abs(date('y') - 24) % 36, 10, 36);
            $month = base_convert(date('m'), 10, 36);
            $day = base_convert(date('d'), 10, 36);
            $path = $path.'/'.$year.$month.$day;
        }
        if ($extra !== '') {
            $path = $path.'/'.$extra;
        }
        if (is_dir($path) || mkdir($path, 01777, true)) {
            return $path;
        }
        throw new \Exception('Failed to create storage path: '.$path);
    }
}

class JWT {

    protected $algorithms = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512',
    ];

    protected $key;
    protected $keys = [];
    protected $alg = 'HS512';
    protected $maxAge = 9990;

    public function __construct($key, string $alg = 'HS512', int $maxAge = 9990) {
        $this->key = $key;
        $this->alg = $alg;
        $this->maxAge = $maxAge;
    }

    public function encode(array $payload, array $header = []): string {
        $header = ['typ' => 'JWT', 'alg' => $this->alg] + $header;

        if (!isset($payload['exp'])) {
            $payload['exp'] = \time() + $this->maxAge;
        }

        $header = $this->urlSafeEncode($header);
        $payload = $this->urlSafeEncode($payload);
        $signature = $this->urlSafeEncode($this->sign($header . '.' . $payload));

        return $header . '.' . $payload . '.' . $signature;
    }

    public function decode(string $token, bool $verify = true): array {
        if (\substr_count($token, '.') < 2) {
            throw new \Exception('Invalid token: Incomplete!');
        }

        $token = \explode('.', $token, 3);
        if (!$verify) {
            return (array) $this->urlSafeDecode($token[1]);
        }

        if (!$this->verify($token[0] . '.' . $token[1], $token[2])) {
            throw new \Exception('Invalid token: Signature!');
        }

        $payload = (array) $this->urlSafeDecode($token[1]);

        $this->validateExpiry($payload);

        return $payload;
    }

    protected function sign(string $input): string {
        return \hash_hmac($this->algorithms[$this->alg], $input, $this->key, true);
    }

    protected function verify(string $input, string $signature): bool {
        $alg = $this->algorithms[$this->alg];
        return \hash_equals($this->urlSafeEncode(\hash_hmac($alg, $input, $this->key, true)), $signature);
    }

    protected function validateExpiry(array $payload) {
        if(\time() > $payload['exp']) {
            throw new \Exception('Invalid token: expired!');
        }
    }

    protected function validateLastJson() {
        if (\JSON_ERROR_NONE === \json_last_error()) {
            return;
        }
        throw new \Exception('JSON failed: ' . \json_last_error_msg());
    }

    protected function urlSafeEncode($data): string {
        if (\is_array($data)) {
            $data = \json_encode($data, \JSON_UNESCAPED_SLASHES);
            $this->validateLastJson();
        }

        return \rtrim(\strtr(\base64_encode($data), '+/', '-_'), '=');
    }

    protected function urlSafeDecode($data, bool $asJson = true) {
        if (!$asJson) {
            return \base64_decode(\strtr($data, '-_', '+/'));
        }

        $data = \json_decode(\base64_decode(\strtr($data, '-_', '+/')));
        $this->validateLastJson();

        return $data;
    }
}

class Updater {
    protected $config;
    protected $updateInterval = 3600 * 24;

    public function __construct($config) {
        $this->config = $config;
    }

    public function updateConfig($latestVersion = '0.9', $lastUpdateCheck = '0', $currentVersion = null) {
        $configFile = 'index.php';
        $fileContents = \file_get_contents($configFile);

        $configPatterns = [
            '/\'latestVersion\'\s?=>\s?\'(\d+\.\d+)\'/i',
            '/\'lastUpdateCheck\'\s?=>\s?\'(\d+)\'/i'
        ];
        $configReplacements = [
            "'latestVersion' => '$latestVersion'",
            "'lastUpdateCheck' => '$lastUpdateCheck'",
        ];
        if ($currentVersion != null) {
            $configPatterns[] = '/\'currentVersion\'\s?=>\s?\'(\d+\.\d+)\'/i';
            $configReplacements[] = "'currentVersion' => '$currentVersion'";
        }

        $newFileContents = \preg_replace($configPatterns, $configReplacements, $fileContents);
        \rename($configFile, $configFile.'.old');
        \file_put_contents($configFile, $newFileContents);
    }

    public function checkForUpdate() {
        if (!$this->config->autoUpdateEnabled) return false;

        if (empty($this->config->lastUpdateCheck) || $this->config->lastUpdateCheck < (\time() - $this->updateInterval)) {
            $latestTag = $this->getLatestTag();
            $this->updateConfig($latestTag, \time());
            if ($this->config->latestVersion != $latestTag) {
                $this->config->latestVersion = $latestTag;
                return $latestTag;
            }
        }
        return false;
    }

    public function getLatestTag() {
        $redirectUrl = $this->getRedirectUrl($this->config->repoUrl.'/releases/latest');

        return substr($redirectUrl, strpos($redirectUrl, '/tag/') + 5) ;
    }

    public function upgrade() {
        $releaseUrl = 'https://codeload.github.com/teacai/blite/zip/refs/tags/'.$this->config->latestVersion;
        $releaseFile = 'release.zip';
        $this->downloadToFile($releaseFile, $releaseUrl);

        if (\file_exists($releaseFile)) {
            $coreFile = 'blite.php';
            $adminFile = 'admin.php';
            $foundCore = false;
            $foundAdmin = false;
            $zip = new \ZipArchive();
            if ($zip->open($releaseFile) === true) {
                for($i = 0; $i < $zip->numFiles; $i++) {
                    if (substr($zip->getNameIndex($i), -9) == $coreFile) {
                        $foundCore = true;
                        \rename($coreFile, $coreFile.'.old');
                        file_put_contents($coreFile, $zip->getFromName($zip->getNameIndex($i)));
                    } elseif (substr($zip->getNameIndex($i), -9) == $adminFile) {
                        $foundAdmin = true;
                        \rename($adminFile, $adminFile.'.old');
                        file_put_contents($adminFile, $zip->getFromName($zip->getNameIndex($i)));
                    }
                }
            }
            $zip->close();
            if ($foundCore && $foundAdmin) {
                unlink($releaseFile);
                $this->config->currentVersion = $this->config->latestVersion;
                $this->updateConfig($this->config->latestVersion, \time(), $this->config->latestVersion);
                return 'Upgraded to v'.$this->config->latestVersion;
            } else {
                return 'Failed to process release file: '.$releaseFile;
            }
        } else {
            return 'Failed to download release file: '.$releaseUrl;
        }
    }

    public function getRedirectUrl($url) {
        try {
            $ch = \curl_init();
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
            \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);
            $response = \curl_exec($ch);
            $redirectUrl = \curl_getinfo($ch, \CURLINFO_EFFECTIVE_URL);
            \curl_close($ch);
            return $redirectUrl;
        } catch (\Exception $e) {}
        return $url;
    }

    public function downloadToFile($file, $url) {
        $ch = \curl_init();
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);

        $fp = \fopen($file, 'w');
        \curl_setopt($ch, \CURLOPT_FILE, $fp);
        \curl_exec($ch);
        \curl_close($ch);
        \fclose($fp);
    }
}

?>