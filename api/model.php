<?php

namespace Blog;

abstract class Model {

    protected $app;
    protected $db;

    public function __construct(\Blog\App $app) {
        $this->app = $app;
        $this->di = $app->di;
        $this->db = $app->di->db;
    }

    public function sulgify ($string) {
        $string = preg_replace('~[^\pL\d]+~u', '-', $string);
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
        $string = preg_replace('~[^-\w]+~', '', $string);
        $string = trim($string, '-');
        $string = preg_replace('~-+~', '-', $string);
        $string = strtolower($string);
        if (empty($string)) {
            return '';
        }
        return $string;
    }

    public function uuid($namespace, $name) {
        return $this->app->uuid($namespace, $name);
    }
}
