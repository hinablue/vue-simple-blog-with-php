CREATE TABLE `vue-blog`.`users` (
`id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`alias` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`name` VARCHAR(50) NOT NULL,
`email` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`password` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`avatar` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
`status` ENUM('actived','deleted') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'actived',
`created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
UNIQUE (`alias`),
UNIQUE (`email`),
INDEX (`status`),
INDEX (`created_at`, `updated_at`))
ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT = 'User table';

CREATE TABLE `vue-blog`.`files` (
`id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`user_id` VARCHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`filename` VARCHAR(500) NOT NULL,
`url` VARCHAR(1000) NOT NULL,
`created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
INDEX (`created_at`, `updated_at`))
ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT = 'Files Table';

CREATE TABLE `vue-blog`.`posts` (
`id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`alias` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`title` VARCHAR(200) NOT NULL,
`markdown` MEDIUMTEXT NOT NULL,
`html` MEDIUMTEXT NOT NULL,
`status` ENUM('draft','published','deleted') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'draft',
`created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
INDEX (`created_at`, `updated_at`),
INDEX (`status`),
UNIQUE (`alias`))
ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT = 'Posts Table';

CREATE TABLE `vue-blog`.`user_posts` (
`user_id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`post_id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`user_id`, `post_id`),
INDEX (`created_at`, `updated_at`))
ENGINE = InnoDB COMMENT = 'User Posts Table';

CREATE TABLE `vue-blog`.`post_files` (
`post_id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`file_id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`file_id`, `post_id`),
INDEX (`created_at`, `updated_at`))
ENGINE = InnoDB COMMENT = 'Post Files Table';
