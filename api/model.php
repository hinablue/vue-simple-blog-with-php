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

    // Use UUID v5.
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