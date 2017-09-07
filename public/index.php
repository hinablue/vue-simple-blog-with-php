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
        } catch (\Exception $e) {
            throw $e;
        }

        return $db;
    });

    $blog = new App($di);

    $blog->get('/', function($app) {
        $posts = new Posts($app);
        $results = $posts->get();

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
            $results = $posts->search(urldecode($_GET['q']));
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

    $blog->put('/entry/add', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $data = file_get_contents('php://input');

        $post = [
            'title' => '',
            'content' => ''
        ];

        $posts = new Posts($app);
        if ($posts->add($data)) {
            return [[
                'status' => 'ok',
                'messages' => 'Succeeded'
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

    $blog->get('/entry/(:alias[a-z0-9\-_]+)', function($di, $alias) {
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

    $blog->get('/user/(:alias[a-z0-9\-_]+)', function($di, $alias) {
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

    $blog->get('/user/(:alias[a-z0-9\-_]+)/entries', function($di, $alias) {
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
        $users = new Users($app);
        if (false === ($user = $users->getByEmail($data['email']))) {
            return [[
                'status' => 'error',
                'messages' => 'User not found'
            ], 404];
        }

        if (false === password_verify($data['password'], $user->password)) {
            return [[
                'status' => 'error',
                'messages' => 'User password or account is invalid'
            ], 405];
        }

        unset($user['password']);

        return [[
            'status' => 'ok',
            'messages' => 'User create succeeded',
            'results' => $user
        ], 200];
    });

    $blog->post('/(register|signup)', function($app) {
        $data = file_get_contents('php://input');

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
        return [[
            'status' => 'ok',
            'messages' => 'User create succeeded'
        ], 200];
    });

    $blog->post('/forgotpassword', function($app) {
        $data = file_get_contents('php://input');
    });

    $blog->post('/changepassword', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $data = file_get_contents('php://input');
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
        if (false === ($user = $users->getId($auth['user_id']))) {
            return [[
                'status' => 'error',
                'messages' => 'User not found'
            ], 404];
        }

        if (false === password_verify($data['oldPassword'], $user->password)) {
            return [[
                'status' => 'error',
                'messages' => 'User password is invalid'
            ], 405];
        }

        $data['user_id'] = $users->id;
        if (false === $users->changePassword($data)) {
            return [[
                'status' => 'error',
                'messages' => 'Method not allow'
            ], 405];
        }
        return [[
            'status' => 'ok',
            'messages' => 'User create succeeded'
        ], 200];
    });

    $blog->post('/profile', function($app) {
        if (is_null($app->auth)) {
            return [[
                'status' => 'error',
                'messages' => 'Need login'
            ], 403];
        }

        $data = file_get_contents('php://input');
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
        $users = new Users($app);
        $user = $users->getById($data);
        if (false === $user) {
            return [[
                'status' => 'error',
                'messages' => 'Method not allow'
            ], 405];
        }
        return [[
            'status' => 'ok',
            'messages' => 'Succeeded',
            'results' => $user
        ], 200];
    });

    echo $blog->run();
} catch (\Exception $e) {
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
