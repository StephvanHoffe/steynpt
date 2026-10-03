ALTER TABLE `plans` ADD `renew_on` text;--> statement-breakpoint
-- Bestaande gepubliceerde schema's: nieuw schema na de looptijd van het trainingsschema, voeding na 4 weken.
UPDATE `plans` SET `renew_on` = date(`published_at`, 'unixepoch', '+' || (7 * coalesce(json_extract(`content`, '$.durationWeeks'), 4)) || ' days') WHERE `status` = 'gepubliceerd' AND `published_at` IS NOT NULL;
