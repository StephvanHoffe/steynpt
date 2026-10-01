CREATE TABLE `appointments` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`user_id` text NOT NULL,
	`type` text NOT NULL,
	`location` text NOT NULL,
	`starts_at` integer NOT NULL,
	`ends_at` integer NOT NULL,
	`status` text DEFAULT 'gepland' NOT NULL,
	`note` text,
	`cancelled_by` text,
	`cancelled_at` integer,
	`created_at` integer DEFAULT (unixepoch()) NOT NULL,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `appointments_start_idx` ON `appointments` (`starts_at`);--> statement-breakpoint
CREATE INDEX `appointments_user_idx` ON `appointments` (`user_id`);--> statement-breakpoint
CREATE TABLE `availability` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`weekday` integer NOT NULL,
	`start_time` text NOT NULL,
	`end_time` text NOT NULL,
	`location` text NOT NULL
);
--> statement-breakpoint
CREATE TABLE `blocked_periods` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`starts_at` integer NOT NULL,
	`ends_at` integer NOT NULL,
	`reason` text
);
--> statement-breakpoint
CREATE TABLE `measurements` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`user_id` text NOT NULL,
	`measured_at` integer NOT NULL,
	`weight` real,
	`body_fat` real,
	`muscle_mass` real,
	`waist` real,
	`hip` real,
	`chest` real,
	`arm` real,
	`thigh` real,
	`note` text,
	`created_at` integer DEFAULT (unixepoch()) NOT NULL,
	FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `measurements_user_idx` ON `measurements` (`user_id`,`measured_at`);--> statement-breakpoint
CREATE TABLE `settings` (
	`key` text PRIMARY KEY NOT NULL,
	`value` text NOT NULL
);
--> statement-breakpoint
DROP TABLE `point_transactions`;--> statement-breakpoint
DROP TABLE `redemptions`;--> statement-breakpoint
ALTER TABLE `users` ADD `referral_reward_at` integer;