<?php

namespace Blog;

abstract class Model {

    protected $app;
    protected $db;

    const TRANSLITERATOR_ID = 'Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();';

    public function __construct(\Blog\App $app) {
        $this->app = $app;
        $this->di = $app->di;
        $this->db = $app->di->db;
    }

    public function sulgify($string = '') {
        if (empty($string)) {
            return '';
        } else {
            $transliterator = \Transliterator::create(self::TRANSLITERATOR_ID);
            $string = $transliterator->transliterate($string);
            $string = preg_replace('/[^a-z0-9 ]/', '', $string);
            $string = trim($string);
            $string = str_replace(' ', '-', $string);
            if (strlen($string) > 500) {
                $string = substr($string, 0, 499);
            }
            return $string;
        }
    }

    public function replace4bytes($string, $replace = '') {
        return preg_replace('%(?:
            \xF0[\x90-\xBF][\x80-\xBF]{2}        # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
        )%xs', $replace, $string);
    }

    public function uuid($namespace, $name) {
        return $this->app->uuid($namespace, $name);
    }
}
