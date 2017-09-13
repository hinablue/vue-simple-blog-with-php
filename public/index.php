<?php

define('ROOT', dirname( dirname( __FILE__ ) ) );
define('DS', DIRECTORY_SEPARATOR);
define('APPLICATION', ROOT . DS . 'api');

require APPLICATION . DS . 'vendor' . DS . 'autoload.php';

use Blog\Vendor\Di;
use Blog\App;

use Blog\Model\Users;
use Blog\Model\UserPosts;
use Blog\Model\Files;
use Blog\Model\Posts;
use Blog\Model\PostFiles;

$di = new Di();

try {
    if (!file_exists(APPLICATION . DS . 'logs')) {
        mkdir(APPLICATION . DS . 'logs', 0755, true);
    }

    $di->set('config', function() {
        require APPLICATION . DS . 'configs' . DS . 'config.php';
        return (object) $configs;
    });

    $di->set('db', function($di) {
        try {
            $db = new PDO(
                implode('', [
                    'mysql:host=',
                    $di->config->database['host'],
                    ';dbname=',
                    $di->config->database['database']
                ]),
                $di->config->database['username'],
                $di->config->database['password'],
                [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8mb4\'',
                    \PDO::ATTR_CASE => \PDO::CASE_LOWER
                ]
            );
            return $db;
        } catch (\Exception $e) {
            throw $e;
        }
    });

    $blog = new App($di);

    $blog->setPrefix('/api');

    $blog->get('/', function($app) {
        $posts = new Posts($app);

        $results = $posts->get([
            'page' => isset($_GET['page']) ? (int) $_GET['page'] : 1
        ]);

        if ($results) {
            return [[
                'status' => 'ok',
                'messages' => 'Succeeded',
                'results' => $results
            ], 200];
        } else {
            return [[
                'status' => 'error',
                'messages' => 'Posts not found'
            ], 404];
        }
    });

    $blog->get('/search', function($app) {
        if (isset($_GET['q']) && !empty($_GET['q'])) {
            $posts = new Posts($app);
            $results = $posts->search([
                'page' => isset($_GET['page']) ? (int) $_GET['page'] : 1,
                'q' => urldecode($_GET['q'])
            ]);
            if ($results) {
                return [[
                    'status' => 'ok',
                    'messages' => 'Succeeded',
                    'results' => $results
                ], 200];
            } else {
                return [[
                    'status' => 'error',
                    'messages' => 'Posts not found'
                ], 404];
            }
        } else {
            return [[
                'status' => 'error',
                'messages' => 'Posts not found'
            ], 404];
        }
    });

    $blog->get('/entry/(:alias[a-z0-9\-_]+)', function($app, $alias) {
        $posts = new Posts($app);
        $results = $posts->getByAlias($alias);
        if ($results) {
            return [[
                'status' => 'ok',
                'messages' => 'Succeeded',
                'results' => $results
            ], 200];
        } else {
            return [[
                'status' => 'error',
                'messages' => 'Posts not found'
            ], 404];
        }
    });

    $blog->get('/user/(:alias[a-z0-9\-_]+)', function($app, $alias) {
        $users = new Users($app);
        $results = $users->getByAlias($alias);
        if ($results) {
            return [[
                'status' => 'ok',
                'messages' => 'Succeeded',
                'results' => $results
            ], 200];
        } else {
            return [[
                'status' => 'error',
                'messages' => 'Posts not found'
            ], 404];
        }
    });

    $blog->get('/user/(:alias[a-z0-9\-_]+)/entries', function($app, $alias) {
        $user_posts = new UserPosts($app);
        $results = $user_posts->getByUserAlias($alias);
        if ($results) {
            return [[
                'status' => 'ok',
                'messages' => 'Succeeded',
                'results' => $results
            ], 200];
        } else {
            return [[
                'status' => 'error',
                'messages' => 'Posts not found'
            ], 404];
        }
    });

    $blog->post('/(signin|login)', function($app) {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        if (empty($data) || !is_array($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Required data missing'
            ], 405];
        }
        $users = new Users($app);
        if (false === ($user = $users->getByEmail($data['email']))) {
            return [[
                'status' => 'error',
                'messages' => 'User not found'
            ], 404];
        }

        if (false === password_verify($data['password'], $user['password'])) {
            return [[
                'status' => 'error',
                'messages' => 'User password or account is invalid'
            ], 405];
        }

        unset($user['password']);

        return [[
            'status' => 'ok',
            'messages' => 'User login succeeded',
            'results' => $user,
            'authorization' => $user['id']
        ], 200];
    });

    $blog->post('/(register|signup)', function($app) {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        if (empty($data) || !is_array($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Required data missing'
            ], 405];
        }

        if (!isset($data['name']) ||
            empty($data['name'])
        ) {
            return [[
                'status' => 'error',
                'messages' => 'User name is empty'
            ], 405];
        }
        if (!isset($data['email']) ||
            empty($data['email'])
        ) {
            return [[
                'status' => 'error',
                'messages' => 'User email is empty'
            ], 405];
        }
        if (!isset($data['password']) ||
            empty($data['password'])
        ) {
            return [[
                'status' => 'error',
                'messages' => 'User password is empty'
            ], 405];
        }

        $users = new Users($app);
        if (false === $users->add($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Method not allow'
            ], 405];
        }
        $user = $users->getByEmail($data['email']);
        unset($user['password']);

        return [[
            'status' => 'ok',
            'messages' => 'User create succeeded',
            'results' => $user,
            'authorization' => $user['id']
        ], 200];
    });

    $blog->post('/forgotpassword', function($app) {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        if (empty($data) || !is_array($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Required data missing'
            ], 405];
        }
    });

    $blog->post('/change_password', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        if (empty($data) || !is_array($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Required data missing'
            ], 405];
        }
        if (!isset($data['oldPassword']) ||
            empty($data['oldPassword'])
        ) {
            return [[
                'status' => 'error',
                'messages' => 'User oldPassword is empty'
            ], 405];
        }
        if (!isset($data['newPassword']) ||
            empty($data['newPassword'])
        ) {
            return [[
                'status' => 'error',
                'messages' => 'User newPassword is empty'
            ], 405];
        }

        $users = new Users($app);
        if (false === ($user = $users->getById($app->auth['id']))) {
            return [[
                'status' => 'error',
                'messages' => 'User not found'
            ], 404];
        }

        if (false === password_verify($data['oldPassword'], $user['password'])) {
            return [[
                'status' => 'error',
                'messages' => 'User password is invalid'
            ], 405];
        }

        if (false === $users->changePassword($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Method not allow'
            ], 405];
        }
        return [[
            'status' => 'ok',
            'messages' => 'User change password succeeded'
        ], 200];
    });

    // Need login below.
    $blog->put('/entry/add', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        if (empty($data) || !is_array($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Required data missing'
            ], 405];
        }

        if (!isset($data['title']) || empty($data['title'])) {
            return [[
                'status' => 'error',
                'messages' => 'Story title is empty'
            ], 405];
        }
        if (!isset($data['markdown']) || empty($data['markdown']) ||
            !isset($data['html']) || empty($data['html'])
        ) {
            return [[
                'status' => 'error',
                'messages' => 'Story content is empty'
            ], 405];
        }

        $posts = new Posts($app);
        if (false !== ($post_id = $posts->add($data))) {
            return [[
                'status' => 'ok',
                'messages' => 'Succeeded',
                'results' => $post_id
            ], 200];
        } else {
            return [[
                'status' => 'error',
                'messages' => 'Posts create failed'
            ], 405];
        }
    });

    $blog->post('/entry/update', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        if (empty($data) || !is_array($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Required data missing'
            ], 405];
        }
        $posts = new Posts($app);
        if ($posts->update($data)) {
            return [[
                'status' => 'ok',
                'messages' => 'Succeeded'
            ], 200];
        } else {
            return [[
                'status' => 'error',
                'messages' => 'Posts update failed'
            ], 405];
        }
    });

    $blog->delete('/entry/delete', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        if (empty($data) || !is_array($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Required data missing'
            ], 405];
        }
        $posts = new Posts($app);
        if ($posts->delete($data['id'])) {
            return [[
                'status' => 'ok',
                'messages' => 'Succeeded'
            ], 200];
        } else {
            return [[
                'status' => 'error',
                'messages' => 'Posts delete failed'
            ], 405];
        }
    });

    $blog->get('/my/entries', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $posts = new Posts($app);
        $results = $posts->getByAuthor([
            'page' => isset($_GET['page']) ? (int) $_GET['page'] : 1
        ]);

        if ($results) {
            return [[
                'status' => 'ok',
                'messages' => 'Succeeded',
                'results' => $results
            ], 200];
        } else {
            return [[
                'status' => 'error',
                'messages' => 'Posts not found'
            ], 404];
        }
    });

    $blog->post('/profile', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        if (empty($data) || !is_array($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Required data missing'
            ], 405];
        }
        $users = new Users($app);
        $results = $users->update($data);
        if (false === $results) {
            return [[
                'status' => 'error',
                'messages' => 'Method not allow'
            ], 405];
        }
        return [[
            'status' => 'ok',
            'messages' => 'User update succeeded'
        ], 200];
    });

    $blog->get('/profile', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $users = new Users($app);
        $user = $users->getById($app->auth['id']);
        if (false === $user) {
            return [[
                'status' => 'error',
                'messages' => 'Method not allow'
            ], 405];
        }
        unset($user['password']);

        return [[
            'status' => 'ok',
            'messages' => 'Succeeded',
            'results' => $user
        ], 200];
    });

    $blog->get('/auth_check', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }
        $users = new Users($app);
        if (false === ($user = $users->getById($app->auth['id']))) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }
        unset($user['password']);

        return [[
            'status' => 'ok',
            'messages' => 'Succeeded',
            'authorization' => $user['id']
        ], 200];
    });

    $blog->post('/fileuploader', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        if (!isset($_FILES['file']) ||
            count($_FILES['file']) === 0
        ) {
            return [[
                'status' => 'error',
                'messages' => 'No file to upload'
            ], 405];
        }

        // Limit to 5MB file.
        if ($_FILES['file']['size'] > 5 * 1024 * 1204) {
            return [[
                'status' => 'error',
                'messages' => 'File is too large (over 5MB)'
            ], 405];
        }

        switch(strtolower($_FILES['file']['type'])) {
            // Allowed file types
            case 'image/png':
            case 'image/gif':
            case 'image/jpeg':
            case 'image/pjpeg':
            case 'text/plain':
            case 'text/html':
            case 'application/x-zip-compressed':
            case 'application/pdf':
            case 'video/mp4':
                break;
            default:
                return [[
                    'status' => 'error',
                    'messages' => 'File type is not allow'
                ], 405];
        }

        $directory = ROOT . DS . 'public' . DS . 'data' . DS . 'files' . DS . date('mY') . DS . date('d');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $filename = $_FILES['file']['name'];
        $extension = substr($filename, strrpos($filename, '.'));
        $new_filename = $app->uuid('00000000-5566-5566-5566-000000000000', microtime()) . $extension;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $directory . DS . $new_filename)) {
            $files = new Files($app);
            if (false !== ($file = $files->add([
                'filename' => $filename,
                'url' => DS . 'data' . DS . 'files' . DS . date('mY') . DS . date('d') . DS . $new_filename,
                'isAvatar' => isset($_POST['isAvatar']),
                'isPost' => isset($_POST['isPost']) && !empty($_POST['isPost']) ? $_POST['isPost'] : false
            ]))) {
                return [[
                    'status' => 'ok',
                    'messages' => 'Succeeded',
                    'results' => $file
                ], 200];
            }
        }
        return [[
            'status' => 'error',
            'messages' => 'File upload failed'
        ], 405];
    });

    echo $blog->run();
} catch (\Exception $e) {
    file_put_contents(APPLICATION . DS . 'logs' . DS . 'api.log', $e->getMessage().PHP_EOL.$e->getTraceAsString().PHP_EOL, FILE_APPEND);

    $datetime = gmdate("D, d M Y H:i:s").' GMT';
    header('Pragma: no-cache');
    header('Cache-Control: no-cache, private, no-store, must-revalidate, pre-check=0, post-check=0, max-age=0, max-stale=0');
    header('Last-Modified: ' . $datetime);
    header('X-Frame-Options: SAMEORIGIN');
    header('Content-Type: application/json;charset=utf-8');
    header('Expires: ' . $datetime);
    header('ETag: ' . md5($datetime));
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'messages' => 'Service Unavailable'
    ], JSON_UNESCAPED_UNICODE);
}
