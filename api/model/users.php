<?php

namespace Blog\Model;

use Blog\Model\Files;

class Users extends \Blog\Model {

    protected $root = '00000000-6666-2222-3333-000000000000';

    public function delete() {
        $status = 'actived';
        $statement = $this->db->prepare('SELECT `id` FROM `users` WHERE `id` = :user_id AND `status` = :status LIMIT 1');
        $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if (false === $statement->execute()) {
            return false;
        }
        if (false === $statement->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $today = date('Y-m-d H:i:s');
            $statement = $this->db->prepare('UPDATE `posts` SET `status` = :deleted AND `updated_at` = :updated_at WHERE `id` = :user_id AND `status` != :status LIMIT 1');
            $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
            $statement->bindParam(':updated_at', $today, \PDO::PARAM_STR);
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

    public function changePassword($data = []) {
        $status = 'actived';
        $statement = $this->db->prepare('SELECT `id` FROM `users` WHERE `id` = :user_id AND `status` = :status LIMIT 1');
        $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if (false === $statement->execute()) {
            return false;
        }
        if (false === $statement->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $password = password_hash($data['newPassword'], PASSWORD_BCRYPT);
            $today = date('Y-m-d H:i:s');
            $statement = $this->db->prepare('UPDATE `users` SET `password` = :password, `updated_at` = :updated_at WHERE `id` = :user_id LIMIT 1');
            $statement->bindParam(':updated_at', $today, \PDO::PARAM_STR);
            $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
            $statement->bindParam(':password', $password, \PDO::PARAM_STR);
            if (false === $statement->execute()) {
                $this->db->rollBack();
            }
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function update($data = []) {
        $status = 'actived';
        $statement = $this->db->prepare('SELECT `id` FROM `users` WHERE `id` = :user_id AND `status` = :status LIMIT 1');
        $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if (false === $statement->execute()) {
            return false;
        }
        if (false === $statement->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $today = date('Y-m-d H:i:s');
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

            $prepare = implode(', ', $prepare) . ' WHERE `id` = :user_id LIMIT 1';

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
            $statement->bindParam(':updated_at', $today, \PDO::PARAM_STR);
            $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);

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
            $id = $this->uuid($this->root, microtime());
            $alias = $this->sulgify($data['name']);
            if (empty($alias)) {
                $alias = $this->uuid($id, microtime());
            }

            $password = password_hash($data['password'], PASSWORD_BCRYPT);
            $status = 'actived';

            $statement = $this->db->prepare('INSERT INTO `users` (`id`,`alias`,`name`,`email`,`password`,`status`) VALUES (:id,:alias,:name,:email,:password,:status)');
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
            $user = $statement->fetch(\PDO::FETCH_ASSOC);
            if (empty($user['avatar'])) {
                $user['avatar'] = '';
            }
            return $user;
        }
        return false;
    }

    public function getByAlias($alias) {
        $status = 'actived';
        $statement = $this->db->prepare('SELECT `u`.* FROM `users` AS u WHERE `u`.`alias` = :alias AND `u`.`status` = :status LIMIT 1');
        $statement->bindParam(':alias', $alias, \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            $user = $statement->fetch(\PDO::FETCH_ASSOC);
            if (empty($user['avatar'])) {
                $user['avatar'] = '';
            }
            return $user;
        }
        return false;
    }

    public function getById($user_id) {
        $status = 'actived';
        $statement = $this->db->prepare(
            implode(' ', ['SELECT `u`.`id`,`u`.`alias`,`u`.`name`,`u`.`email`,`u`.`password`,',
            '`u`.`status`,`u`.`created_at`,`u`.`updated_at`, IFNULL(`f`.`url`, NULL) AS avatar',
            'FROM `users` AS u ',
            ' LEFT JOIN `files` AS f ON `f`.`user_id` = `u`.`id` AND `f`.`id` = `u`.`avatar`',
            'WHERE `u`.`id` = :user_id AND `u`.`status` = :status LIMIT 1'])
        );
        $statement->bindParam(':user_id', $user_id, \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            $user = $statement->fetch(\PDO::FETCH_ASSOC);
            if (empty($user['avatar'])) {
                $user['avatar'] = '';
            }
            return $user;
        }
        return false;
    }

    public function get($params = []) {
        $params = array_merge([
            'limit' => 10,
            'page' => 1
        ], $params);

        $users = [];
        $status = 'actived';
        $statement = $this->db->prepare('SELECT COUNT(`u`.`id`) AS count FROM `users` AS u WHERE `u`.`status` = :status');
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            $users = $statement->fetch(\PDO::FETCH_ASSOC);
        }
        if (count($users) === 0 || (int) $users['count'] === 0) {
            return false;
        }

        $totalItems = (int) $users['count'];
        $totalPages = ceil((int) $users['count'] / (int) $params['limit']);

        if ((int) $params['page'] > $totalPages || (int) $params['page'] < 1) {
            return false;
        }

        $offset = ((int) $params['page'] - 1) * (int) $params['limit'];
        $users = [];
        $statement = $this->db->prepare(
            implode(' ', ['SELECT `u`.`id`,`u`.`alias`,`u`.`name`,`u`.`email`,`u`.`password`,',
            '`u`.`status`,`u`.`created_at`,`u`.`updated_at`,',
            'IFNULL((SELECT `f`.`url` FROM `files` AS f WHERE `f`.`user_id` = `u`.`id` AND `f`.`id` = `u`.`avatar` LIMIT 1), NULL) AS avatar',
            'FROM `users` AS u',
            'WHERE `u`.`id` = :user_id AND `u`.`status` = :status LIMIT :limit OFFSET :offset'])
        );
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        $statement->bindParam(':limit', $params['limit'], \PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, \PDO::PARAM_INT);
        if ($statement->execute()) {
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
                if (empty($row['avatar'])) {
                    $row['avatar'] = '';
                }
                array_push($users, $row);
            }
        }
        return count($users) > 0 ? [
            'items' => $users,
            'totalItems' => (int) $totalItems,
            'totalPages' => (int) $totalPages
        ] : false;
    }
}