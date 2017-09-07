<?php

namespace Blog;

use \Blog\Model\Users;

final class App {
    protected $path = [];
    protected $auth = NULL;

    public function __construct(\Blog\Vendor\Di $di) {
        $this->di = $di;
        $this->path = [
            'get' => [],
            'post' => [],
            'put' => [],
            'delete' => [],
            'options' => []
        ];
    }

    protected function response($res = []) {
        $datetime = gmdate("D, d M Y H:i:s").' GMT';
        header('Pragma: no-cache');
        header('Cache-Control: no-cache, private, no-store, must-revalidate, pre-check=0, post-check=0, max-age=0, max-stale=0');
        header('Last-Modified: ' . $datetime);
        header('X-Frame-Options: SAMEORIGIN');
        header('Content-Type: application/json;charset=utf-8');
        header('Expires: ' . $datetime);
        header('ETag: ' . md5($datetime));
        if (!isset($res[1])) {
            http_response_code(200);
        } else {
            http_response_code($res[1]);
        }
        echo json_encode($res[0], JSON_UNESCAPED_UNICODE);
    }

    public function run() {
        $method = strtolower($_SERVER['REQUEST_METHOD']);

        if ($method !== 'get' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return $this->response([[
                'status' => 'error',
                'messages' => 'Method Not Allow'
            ], 405]);
        }

        $url = urldecode(urlencode($_SERVER['REQUEST_URI']));

        if (substr($url, 0, 1) !== '/') {
            $url = '/' . $url;
        }

        if (!isset($this->path[$method])) {
            return $this->response([[
                'status' => 'error',
                'messages' => 'Method Not Allow'
            ], 405]);
        }

        $route = array_filter($this->path[$method], function ($route) use ($url) {
            if ($route['path'] === $url) {
                return true;
            } else {
                $path = preg_replace('/:([a-z_]+)/i', '?P<$1>', $route['path']);
                return preg_match('#^'.$path.'$#i', $url);
            }
        });

        if (count($route) === 0) {
            return $this->response([[
                'status' => 'error',
                'messages' => 'Method Not Allow'
            ], 405]);
        }
        // Fetch headers with auth.
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        if (isset($headers['Authorization'])) {
            $token = substr('Bearer ', '', $headers['Authorization']);
            $authorization = json_decode(openssl_decrypt(
                base64_decode($hash),
                $this->di->config->crypt['cipher'],
                $this->di->config->crypt['key'],
                OPENSSL_RAW_DATA,
                $this->di->config->crypt['iv']
            ));
            if (!is_null($authorization) && is_array($authorization) &&
                $authorization['ua'] === $headers['User-Agent'] &&
                $authorization['exp'] > time()
            ) {
                $users = new Users($this);
                if (false !== ($user = $users->findById($authorization['user_id']))) {
                    $this->auth = $user;
                }
            }
        }

        $route = array_shift($route);
        if (!is_callable($route['controller'])) {
            return $this->response([[
                'status' => 'error',
                'messages' => 'Method Not Allow'
            ], 405]);
        }
        if ($route['path'] === $url) {
            return $this->response(
                call_user_func($route['controller'], $this)
            );
        } else {
            $path = preg_replace('/:([a-z_]+)/i', '?P<$1>', $route['path']);
            if (preg_match_all('#^'.$path.'$#i', $url, $m)) {
                $parameters = array_reduce(array_keys($m), function($carry, $key) use ($m) {
                    if (!is_numeric($key)) {
                        $carry = array_merge($carry, [ $m[$key][0] ]);
                    }
                    return $carry;
                }, []);
                return $this->response(
                    call_user_func_array($route['controller'], array_merge([$this], $parameters))
                );
            } else {
                return $this->response(
                    call_user_func($route['controller'], $this)
                );
            }
        }
    }

    public function get($path, $controller) {
        if (empty($path) || !is_callable($controller)) {
            throw \Exception('Path cannot empty or callback is not an function.');
        }
        array_push($this->path['get'], [
            'path' => $path,
            'controller' => $controller
        ]);
        return $this;
    }
    public function post($path, $controller) {
        if (empty($path) || !is_callable($controller)) {
            throw \Exception('Path cannot empty or callback is not an function.');
        }
        array_push($this->path['post'], [
            'path' => $path,
            'controller' => $controller
        ]);
        return $this;
    }
    public function put($path, $controller) {
        if (empty($path) || !is_callable($controller)) {
            throw \Exception('Path cannot empty or callback is not an function.');
        }
        array_push($this->path['put'], [
            'path' => $path,
            'controller' => $controller
        ]);
        return $this;
    }
    public function delete($path, $controller) {
        if (empty($path) || !is_callable($controller)) {
            throw \Exception('Path cannot empty or callback is not an function.');
        }
        array_push($this->path['delete'], [
            'path' => $path,
            'controller' => $controller
        ]);
        return $this;
    }
    public function options($path, $controller) {
        if (empty($path) || !is_callable($controller)) {
            throw \Exception('Path cannot empty or callback is not an function.');
        }
        array_push($this->path['options'], [
            'path' => $path,
            'controller' => $controller
        ]);
        return $this;
    }

    // UUID v5.
    public function uuid($namespace, $name) {
        if (!self::is_valid($namespace)) return false;
        $nhex = str_replace(array('-','{','}'), '', $namespace);
        $nstr = '';
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
        }
        $hash = sha1($nstr . $name);
        return sprintf('%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            substr($hash, 20, 12)
        );
    }

    protected function is_valid($uuid) {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.
            '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
    }
}
