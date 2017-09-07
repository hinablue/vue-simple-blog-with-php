<?php

namespace Blog\Model;

class Users extends \Blog\Model {

    protected $root = '00000000-6666-2222-3333-000000000000';

    public function delete($user_id) {
        $status = 'deleted';
        $statement = $this->db->prepare('SELECT `id` FROM `users` WHERE `id` = :user_id AND `status` != :status LIMIT 1');
        $statement->bindParam(':user_id', $user_id, \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if (false === $statement->execute()) {
            return false;
        }
        if (false === $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('UPDATE `posts` SET `status` = :deleted AND `updated_at` = :updated_at WHERE `id` = :user_id AND `status` != :status LIMIT 1');
            $statement->bindParam(':user_id', $user_id, \PDO::PARAM_STR);
            $statement->bindParam(':updated_at', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
            $statement->bindParam(':deleted', $status, \PDO::PARAM_STR);
            $statement->bindParam(':status', $status, \PDO::PARAM_STR);
            if (false === $statement->execute()) {
                $this->db->rollBack();
            }
            $user_posts = new UserPosts();
            if (false === $user_posts->deleteByUserId($user_id)) {
                $this->db->rollBack();
            }
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function update($data = []) {
        $status = 'deleted';
        $statement = $this->db->prepare('SELECT `id` FROM `users` WHERE `id` = :user_id AND `status` != :status LIMIT 1');
        $statement->bindParam(':user_id', $data['user_id'], \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if (false === $statement->execute()) {
            return false;
        }
        if (false === $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $prepare = ['UPDATE `users` SET `updated_at` = :updated_at'];
            if (isset($data['alias']) && !empty($data['alias'])) {
                array_push($prepare, ' `alias` = :alias');
            }
            if (isset($data['name']) && !empty($data['name'])) {
                array_push($prepare, ' `name` = :name');
            }
            if (isset($data['email']) && !empty($data['email'])) {
                array_push($prepare, ' `email` = :email');
            }
            if (isset($data['password']) && !empty($data['password'])) {
                array_push($prepare, ' `password` = :password');
            }

            $prepare = impolode(', ', $prepare) . ' WHERE user_id = :user_id LIMIT 1';

            $statement = $this->db->prepare($prepare);
            if (isset($data['alias']) && !empty($data['alias'])) {
                $statement->bindParam(':alias', $alias, \PDO::PARAM_STR);
            }
            if (isset($data['name']) && !empty($data['name'])) {
                $statement->bindParam(':name', $data['name'], \PDO::PARAM_STR);
            }
            if (isset($data['email']) && !empty($data['email'])) {
                $statement->bindParam(':email', $data['email'], \PDO::PARAM_STR);
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $password = password_hash($data['password'], PASSWORD_BCRYPT);
                $statement->bindParam(':password', $password, \PDO::PARAM_STR);
            }
            $statement->bindParam(':updated_at', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
            $statement->bindParam(':user_id', $data['user_id'], \PDO::PARAM_STR);
            if (false === $statement->execute()) {
                $this->db->rollBack();
            }
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function add($data = []) {
        $this->db->beginTransaction();
        try {
            $id = $this->uuid($this->root, microtiem());
            $alias = $this->sulgify($data['name']);
            if (empty($alias)) {
                $alias = $this->uuid($id, microtiem());
            }

            $password = password_hash($data['password'], PASSWORD_BCRYPT);
            $status = 'actived';

            $statement = $this->db->prepare('INSERT INTO `users` (`id`,`alias`,`name`,`email`,`password`,`status`) VALUES (:id,:alias,:email,:password,:status)');
            $statement->bindParam(':id', $id, \PDO::PARAM_STR);
            $statement->bindParam(':alias', $alias, \PDO::PARAM_STR);
            $statement->bindParam(':name', $data['name'], \PDO::PARAM_STR);
            $statement->bindParam(':email', $data['email'], \PDO::PARAM_STR);
            $statement->bindParam(':password', $password, \PDO::PARAM_STR);
            $statement->bindParam(':status', $status, \PDO::PARAM_STR);
            if (false === $statement->execute()) {
                $this->db->rollBack();
            }
            $this->db->commit();
            return $id;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function getByEmail($email) {
        $status = 'actived';
        $statement = $this->db->prepare('SELECT `u`.* FROM `users` AS u WHERE `u`.`email` = :email AND `u`.`status` = :status LIMIT 1');
        $statement->bindParam(':email', $email, \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            return $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
        }
        return false;
    }

    public function getByAlias($alias) {
        $status = 'actived';
        $statement = $this->db->prepare('SELECT `u`.* FROM `users` AS u WHERE `u`.`alias` = :alias AND `u`.`status` = :status LIMIT 1');
        $statement->bindParam(':alias', $alias, \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            return $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
        }
        return false;
    }

    public function getById($user_id) {
        $status = 'actived';
        $statement = $this->db->prepare('SELECT `u`.* FROM `users` AS u WHERE `u`.`id` = :user_id AND `u`.`status` = :status LIMIT 1');
        $statement->bindParam(':user_id', $user_id, \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            return $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
        }
        return false;
    }

    public function get() {
        $users = [];
        $statement = $this->db->prepare('SELECT `u`.* FROM `users` AS u WHERE `u`.`status` = :status LIMIT 10');
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
                array_push($users, $row);
            }
        }
        return count($users) > 0 ? $users : false;
    }
}