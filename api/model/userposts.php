<?php

namespace Blog\Model;

use Users;

class UserPosts extends \Blog\Model {

	public function getByUserAlias($alias) {
		$users = new Users($this->di);
		if (false === ($user = $users->getByAlias($alias))) {
			return false;
		}

		$posts = [];
		$status = 'published';
		$statement = $this->db->prepare('SELECT `p`.* FROM `user_posts` AS up INNER JOIN `posts` AS p ON `p`.`id` = `up`.`post_id` AND `p`.`status` = :status WHERE `up`.`user_id` = :user_id LIMIT 10');
		$statement->bindParam(':user_id', $user_id, \PDO::PARAM_STR);
		$statement->bindParam(':status', $status, \PDO::PARAM_STR);
		if ($statement->execute()) {
			while ($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
				array_push($posts, $row);
			}
		}
		return count($posts) > 0 ? $posts : false;
	}

	public function add($user_id, $post_id) {
		$statement = $this->db->prepare('INSERT INTO `user_posts` (`user_id`,`post_id`) VALUES (:user_id,:post_id)');
		$statement->bindParam(':user_id', $user_id, \PDO::PARAM_STR);
		$statement->bindParam(':post_id', $post_id, \PDO::PARAM_STR);
		return $statement->execute();
	}

	public function updateByPostId($post_id) {
		$statement = $this->db->prepare('UPDATE `user_posts` set `updated_at` = :updated_at WHERE `post_id` = :post_id LIMIT 1');
		$statement->bindParam(':updated_at', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
		$statement->bindParam(':post_id', $post_id, \PDO::PARAM_STR);
		return $statement->execute();
	}

	public function updateByUserId($user_id) {
		$statement = $this->db->prepare('UPDATE `user_posts` set `updated_at` = :updated_at WHERE `user_id` = :user_id LIMIT 1');
		$statement->bindParam(':updated_at', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
		$statement->bindParam(':user_id', $user_id, \PDO::PARAM_STR);
		return $statement->execute();
	}
}