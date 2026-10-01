CREATE TABLE `intakes` (
	`user_id` text PRIMARY KEY NOT NULL,
	`data` text NOT NULL,
	`created_at` integer DEFAULT (unixepoch()) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch()) NOT NULL,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE TABLE `plans` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`user_id` text NOT NULL,
	`type` text NOT NULL,
	`status` text DEFAULT 'genereren' NOT NULL,
	`content` text,
	`ai_draft` text,
	`source` text DEFAULT 'ai' NOT NULL,
	`instruction` text,
	`error` text,
	`model` text,
	`created_at` integer DEFAULT (unixepoch()) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch()) NOT NULL,
	`published_at` integer,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `plans_user_idx` ON `plans` (`user_id`,`type`,`status`);