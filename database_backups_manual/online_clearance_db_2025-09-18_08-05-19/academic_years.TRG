TYPE=TRIGGERS
triggers='CREATE DEFINER=`root`@`localhost` TRIGGER `ensure_single_active_academic_year` \nBEFORE UPDATE ON `academic_years`\nFOR EACH ROW\nBEGIN\n    IF NEW.is_active = 1 THEN\n        UPDATE `academic_years` SET `is_active` = 0 WHERE `academic_year_id` != NEW.academic_year_id;\n    END IF;\nEND'
sql_modes=524288
definers='root@localhost'
client_cs_names='utf8mb4'
connection_cl_names='utf8mb4_unicode_ci'
db_cl_names='utf8mb4_unicode_ci'
created=1755114496393471
