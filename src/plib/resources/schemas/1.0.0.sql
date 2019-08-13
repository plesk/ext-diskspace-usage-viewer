CREATE TABLE `files` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `path` TINYTEXT NOT NULL,
    `size` INTEGER NOT NULL
);

CREATE INDEX `idx_size` ON `files` (`size`);
