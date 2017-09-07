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
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) {
            return '';
        }
        return $text;
    }

    public function uuid($namespace, $name) {
        return $this->app->uuid($namespace, $name);
    }
}
