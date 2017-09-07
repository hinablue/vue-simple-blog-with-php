<?php

namespace Blog\Model;

class Files extends \Blog\Model {

    protected $root = '00000000-9999-2222-3333-000000000000';

    public function add($data) {
        $this->db->beginTransaction();
        try {
            $id = $this->uuid($this->root, microtiem());

            $statement = $this->db->prepare('INSERT INTO `files` (`id`,`user_id`,`filename`,`url`) VALUES (:id,:user_id,:filename,:url)');
            $statement->bindParam(':id', $id, \PDO::PARAM_STR);
            $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
            $statement->bindParam(':filename', $data['filename'], \PDO::PARAM_STR);
            $statement->bindParam(':url', $data['url'], \PDO::PARAM_STR);
            if (false === $statement->execute()) {
                $this->db->rollBack();
            }
            $this->db->commit();
            return [
                'id' => $id,
                'url' => $data['url']
            ];
        } catch (\PDOException $e) {
            return false;
        }
    }
}