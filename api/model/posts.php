<?php

namespace Blog\Model;

use Blog\Model\UserPosts;

class Posts extends \Blog\Model {

    protected $root = '00000000-1111-2222-3333-000000000000';

    public function delete($post_id) {
        $status = 'deleted';
        $statement = $this->db->prepare('SELECT `id` FROM `posts` WHERE `id` = :posd_id AND `status` != :status LIMIT 1');
        $statement->bindParam(':posd_id', $posd_id, \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if (false === $statement->execute()) {
            return false;
        }
        if (false === ($post = $statement->fetch(\PDO::FETCH_ASSOC))) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $today = date('Y-m-d H:i:s');
            $statement = $this->db->prepare('UPDATE `posts` SET `status` = :deleted AND `updated_at` = :updated_at WHERE `id` = :posd_id AND `status` != :status LIMIT 1');
            $statement->bindParam(':posd_id', $post['id'], \PDO::PARAM_STR);
            $statement->bindParam(':updated_at', $today, \PDO::PARAM_STR);
            $statement->bindParam(':deleted', $status, \PDO::PARAM_STR);
            $statement->bindParam(':status', $status, \PDO::PARAM_STR);
            if (false === $statement->execute()) {
                $this->db->rollBack();
            }
            $user_posts = new UserPosts($this->app);
            if (false === $user_posts->updateByPostId($post['id'])) {
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
        $statement = $this->db->prepare('SELECT `id` FROM `posts` WHERE `id` = :posd_id AND `status` != :status LIMIT 1');
        $statement->bindParam(':posd_id', $data['id'], \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if (false === $statement->execute()) {
            return false;
        }
        if (false === ($post = $statement->fetch(\PDO::FETCH_ASSOC))) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $today = date('Y-m-d H:i:s');
            $prepare = ['UPDATE `posts` SET `status` = :status, `updated_at` = :updated_at'];
            if (isset($data['alias']) && !empty($data['alias'])) {
                array_push($prepare, ' `alias` = :alias');
            }
            if (isset($data['markdown']) && !empty($data['markdown'])) {
                array_push($prepare, ' `markdown` = :markdown, html = :html');
            }
            if (isset($data['title']) && !empty($data['title'])) {
                array_push($prepare, ' `title` = :title');
            }
            $status = isset($data['published']) && $data['published'] === true ? 'published' : 'draft';

            $prepare = implode(', ', $prepare) . ' WHERE id = :post_id LIMIT 1';

            $statement = $this->db->prepare($prepare);
            if (isset($data['alias']) && !empty($data['alias'])) {
                $statement->bindParam(':alias', $alias, \PDO::PARAM_STR);
            }
            if (isset($data['title']) && !empty($data['title'])) {
                $statement->bindParam(':title', $data['title'], \PDO::PARAM_STR);
            }
            if (isset($data['markdown']) && !empty($data['markdown'])) {
                $statement->bindParam(':markdown', $data['markdown'], \PDO::PARAM_STR);
                $statement->bindParam(':html', $data['html'], \PDO::PARAM_STR);
            }
            $statement->bindParam(':status', $status, \PDO::PARAM_STR);
            $statement->bindParam(':updated_at', $today, \PDO::PARAM_STR);
            $statement->bindParam(':post_id', $post['id'], \PDO::PARAM_STR);
            if (false === $statement->execute()) {
                $this->db->rollBack();
            }
            if (isset($data['files']) && is_array($data['files'])) {
                foreach($files as $file) {
                    $statement = $this->db->prepare('INSERT INTO `post_files` (`post_id`,`file_id`) VALUES (:post_id, :file_id)');
                    $statement->bindParam(':post_id', $post['id'], \PDO::PARAM_STR);
                    $statement->bindParam(':file_id', $file_id, \PDO::PARAM_STR);
                    if (false === $statement->execute()) {
                        $this->db->rollBack();
                    }
                }
            }
            $user_posts = new UserPosts($this->app);
            if (false === $user_posts->updateByPostId($post['id'])) {
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
            $alias = $this->getAlias($data['title']);
            if (empty($alias)) {
                $alias = $this->uuid($id, microtime());
            }

            $html = $data['html'];
            $status = isset($data['published']) && $data['published'] === true ? 'published' : 'draft';

            $statement = $this->db->prepare(
                'INSERT INTO `posts` (`id`,`alias`,`title`,`markdown`,`html`,`status`) VALUES' .
                ' (:id,:alias,:title,:markdown,:html,:status)'
            );
            $statement->bindParam(':id', $id, \PDO::PARAM_STR);
            $statement->bindParam(':alias', $alias, \PDO::PARAM_STR);
            $statement->bindParam(':title', $data['title'], \PDO::PARAM_STR);
            $statement->bindParam(':markdown', $data['markdown'], \PDO::PARAM_STR);
            $statement->bindParam(':html', $html, \PDO::PARAM_STR);
            $statement->bindParam(':status', $status, \PDO::PARAM_STR);
            if (false === $statement->execute()) {
                $this->db->rollBack();
            }

            if (isset($data['files']) && is_array($data['files'])) {
                foreach($files as $file) {
                    $statement = $this->db->prepare('INSERT INTO `post_files` (`post_id`,`file_id`) VALUES (:post_id, :file_id)');
                    $statement->bindParam(':post_id', $id, \PDO::PARAM_STR);
                    $statement->bindParam(':file_id', $file_id, \PDO::PARAM_STR);
                    if (false === $statement->execute()) {
                        $this->db->rollBack();
                    }
                }
            }

            $user_posts = new UserPosts($this->app);
            if (false === $user_posts->add($id, $this->app->auth['id'])) {
                $this->db->rollBack();
            }
            $this->db->commit();
            return $id;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function getByUserId($user_id) {
        $params = array_merge([
            'limit' => 10,
            'page' => 1,
            'user_id' => ''
        ], $params);

        if (empty($params['user_id'])) {
            return false;
        }

        $params['page'] = (int) $params['page'];
        $params['limit'] = (int) $params['limit'];

        $status = 'published';
        $posts = [];
        $statement = $this->db->prepare(
            implode(' ', [
                'SELECT COUNT(*) AS count FROM `user_posts` AS up',
                'INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id`',
                'WHERE `p`.`status` = :status AND `up`.`user_id` = :user_id'
            ])
        );
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        $statement->bindParam(':user_id', $params['user_id'], \PDO::PARAM_STR);
        if ($statement->execute()) {
            $posts = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
        }
        if (count($posts) === 0 || (int) $posts['count'] === 0) {
            return false;
        }

        $totalItems = (int) $totalItems;
        $totalPages = (int) ceil($totalItems / $params['limit']);

        if ($params['page'] > ceil($totalItems / $params['limit']) || $params['page'] < 1) {
            return false;
        }

        $offset = ($params['page'] - 1) * $params['limit'];
        $posts = [];
        $statement = $this->db->prepare(
            implode(' ', [
                'SELECT `p`.*, `u`.`alias` AS user_alias, `u`.`name` AS user_name,',
                'IFNULL((SELECT `f`.`url` FROM `files` AS f WHERE `f`.`user_id` = `u`.`id` AND `f`.`id` = `u`.`avatar` LIMIT 1), NULL) AS user_avatar,',
                'IFNULL((SELECT `f`.`url` FROM `files` AS f INNER JOIN `post_files` AS pf ON `pf`.`file_id` = `f`.`id` WHERE `pf`.`post_id` = `up`.`post_id` AND `f`.`user_id` = `up`.`user_id` LIMIT 1), NULL) AS cover',
                'FROM `user_posts` AS up',
                'INNER JOIN `users` AS u ON `u`.`id` = `up`.`user_id`',
                'INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id`',
                'WHERE `u`.`status` = :actived AND `p`.`status` = :published',
                'AND `up`.`user_id` = :user_id',
                'ORDER BY `p`.`created_at` DESC',
                'LIMIT :limit OFFSET :offset'
            ])
        );
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        $statement->bindParam(':user_id', $params['user_id'], \PDO::PARAM_STR);
        $statement->bindParam(':limit', $params['limit'], \PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, \PDO::PARAM_INT);
        if ($statement->execute()) {
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
                $row['text'] = str_replace(["\n", "\r", "\r\n"], ' ', strip_tags($row['html']));
                array_push($posts, $row);
            }
        }
        return count($posts) > 0 ? [
            'items' => $posts,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages
        ] : false;
    }

    public function getById($post_id) {
        $user_status = 'actived';
        $post_status = 'published';
        $statement = $this->db->prepare(
            implode(' ', [
                'SELECT `p`.*, `u`.`alias` AS user_alias, `u`.`name` AS user_name,',
                'IFNULL((SELECT `f`.`url` FROM `files` AS f WHERE `f`.`user_id` = `u`.`id` AND `f`.`id` = `u`.`avatar` LIMIT 1), NULL) AS user_avatar,',
                'IFNULL((SELECT `f`.`url` FROM `files` AS f INNER JOIN `post_files` AS pf ON `pf`.`file_id` = `f`.`id` WHERE `pf`.`post_id` = `up`.`post_id` AND `f`.`user_id` = `up`.`user_id` LIMIT 1), NULL) AS cover',
                'FROM `user_posts` AS up',
                'INNER JOIN `users` AS u ON `u`.`id` = `up`.`user_id`',
                'INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id`',
                'WHERE `u`.`status` = :actived AND `p`.`status` = :published',
                'AND `p`.`id` = :post_id',
                'LIMIT 1'
            ])
        );
        $statement->bindParam(':post_id', $post_id, \PDO::PARAM_STR);
        $statement->bindParam(':published', $post_status, \PDO::PARAM_STR);
        $statement->bindParam(':actived', $user_status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            $post = $statement->fetch(\PDO::FETCH_ASSOC);
            if ($post) {
                $post['cover'] = empty($post['cover']) ? '' : $post['cover'];
                $post['author'] = [
                    'name' => $post['user_name'],
                    'alias' => $post['user_alias'],
                    'avatar' => empty($post['user_avatar']) ? '' : $post['user_avatar']
                ];
                unset($post['user_name']);
                unset($post['user_alias']);
                unset($post['user_avatar']);

                return $post;
            }
        }
        return false;
    }
    public function getByAlias($alias) {
        $status = 'published';
        $statement = $this->db->prepare('SELECT `id` FROM `posts` WHERE `alias` = :alias AND `status` = :status LIMIT 1');
        $statement->bindParam(':alias', $alias, \PDO::PARAM_STR);
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            $post = $statement->fetch(\PDO::FETCH_ASSOC);
            return $this->getById($post['id']);
        }
        return false;
    }

    public function search($params = []) {
        $params = array_merge([
            'limit' => 10,
            'page' => 1,
            'q' => ''
        ], $params);

        if (empty($params['q'])) {
            return false;
        }

        $params['page'] = (int) $params['page'];
        $params['limit'] = (int) $params['limit'];

        $status = 'published';
        $posts = [];
        $statement = $this->db->prepare(
            implode(' ', [
                'SELECT COUNT(*) AS count FROM `user_posts` AS up',
                'INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id`',
                'WHERE `p`.`status` = :status AND `p`.`title` RLIKE :title'
            ])
        );
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        $statement->bindParam(':title', $params['q'], \PDO::PARAM_STR);
        if ($statement->execute()) {
            $posts = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
        }
        if (count($posts) === 0 || (int) $posts['count'] === 0) {
            return false;
        }

        $totalItems = (int) $totalItems;
        $totalPages = (int) ceil($totalItems / $params['limit']);

        if ($params['page'] > ceil($totalItems / $params['limit']) || $params['page'] < 1) {
            return false;
        }

        $offset = ($params['page'] - 1) * $params['limit'];
        $posts = [];
        $statement = $this->db->prepare(
            implode(' ', [
                'SELECT `p`.*, `u`.`alias` AS user_alias, `u`.`name` AS user_name,',
                'IFNULL((SELECT `f`.`url` FROM `files` AS f WHERE `f`.`user_id` = `u`.`id` AND `f`.`id` = `u`.`avatar` LIMIT 1), NULL) AS user_avatar,',
                'IFNULL((SELECT `f`.`url` FROM `files` AS f INNER JOIN `post_files` AS pf ON `pf`.`file_id` = `f`.`id` WHERE `pf`.`post_id` = `up`.`post_id` AND `f`.`user_id` = `up`.`user_id` LIMIT 1), NULL) AS cover',
                'FROM `user_posts` AS up',
                'INNER JOIN `users` AS u ON `u`.`id` = `up`.`user_id`',
                'INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id`',
                'WHERE `u`.`status` = :actived AND `p`.`status` = :published',
                'AND `p`.`title` RLIKE :title',
                'ORDER BY `p`.`created_at` DESC',
                'LIMIT :limit OFFSET :offset'
            ])
        );
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        $statement->bindParam(':title', $params['q'], \PDO::PARAM_STR);
        $statement->bindParam(':limit', $params['limit'], \PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, \PDO::PARAM_INT);
        if ($statement->execute()) {
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
                $row['text'] = str_replace(["\n", "\r", "\r\n"], ' ', strip_tags($row['html']));
                $row['cover'] = empty($row['cover']) ? '' : $row['cover'];
                $row['author'] = [
                    'name' => $row['user_name'],
                    'alias' => $row['user_alias'],
                    'avatar' => empty($row['user_avatar']) ? '' : $row['user_avatar']
                ];
                unset($row['user_name']);
                unset($row['user_alias']);
                unset($row['user_avatar']);
                array_push($posts, $row);
            }
        }
        return count($posts) > 0 ? [
            'items' => $posts,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages
        ] : false;
    }

    public function getByAuthor($params = []) {
        $params = array_merge([
            'limit' => 10,
            'page' => 1
        ], $params);

        $params['page'] = (int) $params['page'];
        $params['limit'] = (int) $params['limit'];

        $status = 'deleted';
        $posts = [];
        $statement = $this->db->prepare(
            implode(' ', [
                'SELECT COUNT(*) AS count FROM `user_posts` AS up',
                'INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id`',
                'WHERE `p`.`status` != :status AND `up`.`user_id` = :user_id'
            ])
        );
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
        if ($statement->execute()) {
            $posts = $statement->fetch(\PDO::FETCH_ASSOC);
        }

        if (count($posts) === 0 || $posts['count'] === 0) {
            return false;
        }

        $totalItems = (int) $posts['count'];
        $totalPages = (int) ceil($totalItems / $params['limit']);

        if ($params['page'] > $totalPages || $params['page'] < 1) {
            return false;
        }

        $offset = ($params['page'] - 1) * $params['limit'];
        $posts = [];
        $statement = $this->db->prepare(
            implode(' ', [
                'SELECT `p`.* FROM `user_posts` AS up',
                'INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id`',
                'WHERE `p`.`status` != :status AND `up`.`user_id` = :user_id',
                'ORDER BY `p`.`created_at` DESC',
                'LIMIT :limit OFFSET :offset'
            ])
        );
        $statement->bindParam(':status', $status, \PDO::PARAM_STR);
        $statement->bindParam(':user_id', $this->app->auth['id'], \PDO::PARAM_STR);
        $statement->bindParam(':limit', $params['limit'], \PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, \PDO::PARAM_INT);
        if ($statement->execute()) {
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
                array_push($posts, $row);
            }
        }
        return count($posts) > 0 ? [
            'items' => $posts,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages
        ] : false;
    }

    public function get($params = []) {
        $params = array_merge([
            'limit' => 10,
            'page' => 1
        ], $params);

        $params['page'] = (int) $params['page'];
        $params['limit'] = (int) $params['limit'];

        $post_status = 'published';
        $user_status = 'actived';
        $posts = [];
        $statement = $this->db->prepare(
            implode(' ', [
                'SELECT COUNT(*) AS count FROM `user_posts` AS up',
                'INNER JOIN `users` AS u ON `u`.`id` = `up`.`user_id`',
                'INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id`',
                'WHERE `u`.`status` = :actived AND `p`.`status` = :published'
            ])
        );
        $statement->bindParam(':actived', $user_status, \PDO::PARAM_STR);
        $statement->bindParam(':published', $post_status, \PDO::PARAM_STR);
        if ($statement->execute()) {
            $posts = $statement->fetch(\PDO::FETCH_ASSOC);
        }

        if (count($posts) === 0 || $posts['count'] === 0) {
            return false;
        }

        $totalItems = (int) $posts['count'];
        $totalPages = (int) ceil($totalItems / $params['limit']);

        if ($params['page'] > $totalPages || $params['page'] < 1) {
            return false;
        }

        $offset = ($params['page'] - 1) * $params['limit'];
        $posts = [];
        $statement = $this->db->prepare(
            implode(' ', [
                'SELECT `p`.*, `u`.`alias` AS user_alias, `u`.`name` AS user_name,',
                'IFNULL((SELECT `f`.`url` FROM `files` AS f WHERE `f`.`user_id` = `u`.`id` AND `f`.`id` = `u`.`avatar` LIMIT 1), NULL) AS user_avatar,',
                'IFNULL((SELECT `f`.`url` FROM `files` AS f INNER JOIN `post_files` AS pf ON `pf`.`file_id` = `f`.`id` WHERE `pf`.`post_id` = `up`.`post_id` AND `f`.`user_id` = `up`.`user_id` LIMIT 1), NULL) AS cover',
                'FROM `user_posts` AS up',
                'INNER JOIN `users` AS u ON `u`.`id` = `up`.`user_id`',
                'INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id`',
                'WHERE `u`.`status` = :actived AND `p`.`status` = :published',
                'ORDER BY `p`.`created_at` DESC',
                'LIMIT :limit OFFSET :offset'
            ])
        );
        $statement->bindParam(':actived', $user_status, \PDO::PARAM_STR);
        $statement->bindParam(':published', $post_status, \PDO::PARAM_STR);
        $statement->bindParam(':limit', $params['limit'], \PDO::PARAM_INT);
        $statement->bindParam(':offset', $offset, \PDO::PARAM_INT);
        if ($statement->execute()) {
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
                $row['text'] = str_replace(["\n", "\r", "\r\n"], ' ', strip_tags($row['html']));
                $row['cover'] = empty($row['cover']) ? '' : $row['cover'];
                $row['author'] = [
                    'name' => $row['user_name'],
                    'alias' => $row['user_alias'],
                    'avatar' => empty($row['user_avatar']) ? '' : $row['user_avatar']
                ];
                unset($row['user_name']);
                unset($row['user_alias']);
                unset($row['user_avatar']);
                array_push($posts, $row);
            }
        }
        return count($posts) > 0 ? [
            'items' => $posts,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages
        ] : false;
    }

    private function getAlias($alias) {
        $alias = $this->sulgify($alias);

        $statement = $this->db->prepare('SELECT `alias` FROM `posts` WHERE `alias` = :alias LIMIT 1');
        $statement->bindParam(':alias', $alias, \PDO::PARAM_STR);
        if ($statement->execute()) {
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
            if ($result && $result['alias'] === $alias) {
                $statement->closeCursor();

                $like_alias = $alias . '-%';
                $statement = $this->db->prepare('SELECT `alias` FROM `posts` WHERE `alias` LIKE :like_alias OR `alias` = :alias ORDER BY LENGTH(`alias`) DESC, `alias` DESC LIMIT 1');
                $statement->bindParam(':like_alias', $like_alias, \PDO::PARAM_STR);
                $statement->bindParam(':alias', $alias, \PDO::PARAM_STR);
                if ($statement->execute()) {
                    $result = $statement->fetch(\PDO::FETCH_ASSOC);
                    if ($result && !empty($result['alias'])) {
                        $counter = '-' . ((int) substr(strrchr($result['alias'], '-'), 1) + 1);
                        $alias = mb_substr($alias, 0, 200 - mb_strlen($counter)) . $counter;
                    }
                }
            }
        }

        return $alias;
    }
}
