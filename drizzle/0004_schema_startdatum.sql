ALTER TABLE `plans` ADD `starts_on` text;--> statement-breakpoint
-- Bestaande gepubliceerde schema's zijn ingegaan op de dag van publiceren.
UPDATE `plans` SET `starts_on` = date(`published_at`, 'unixepoch') WHERE `published_at` IS NOT NULL;
