<?php

namespace Blog\Model;

use Users;

class Files extends \Blog\Model {

    protected $root = '00000000-9999-2222-3333-000000000000';

    public function add($data) {
        $this->db->beginTransaction();
        try {
            $id = $this->uuid($this->root, microtime());

            $statement = $this->db->prepare('INSERT INTO `files` (`id`,`user_id`,`filename`,`url`) VALUES (:id,:user_id,:filename,:url)');
            $statement->bindParam(':id', $id, \PDO::PARAM_STR);
            $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
            $statement->bindParam(':filename', $data['filename'], \PDO::PARAM_STR);
            $statement->bindParam(':url', $data['url'], \PDO::PARAM_STR);
            if (false === $statement->execute()) {
                $this->db->rollBack();
            }

            if ($data['isAvatar']) {
                $statement = $this->db->prepare('UPDATE `users` SET `avatar` = :avatar WHERE `id` = :user_id LIMIT 1');
                $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
                $statement->bindParam(':avatar', $id, \PDO::PARAM_STR);
                if (false === $statement->execute()) {
                    $this->db->rollBack();
                }
            }

            if ($data['isPost']) {
                $statement = $this->db->prepare('INSERT INTO `post_files` (`post_id`,`file_id`) VALUES (:post_id, :file_id)');
                $statement->bindParam(':post_id', $data['isPost'], \PDO::PARAM_STR);
                $statement->bindParam(':file_id', $id, \PDO::PARAM_STR);
                if (false === $statement->execute()) {
                    $this->db->rollBack();
                }
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