TYPE=TRIGGERS
triggers='CREATE DEFINER=`root`@`localhost` TRIGGER generate_clearance_form_id\nBEFORE INSERT ON clearance_forms\nFOR EACH ROW\nBEGIN\n    DECLARE next_id INT;\n    DECLARE year_part CHAR(4);\n\n    SET year_part = YEAR(CURDATE());\n\n    SELECT COALESCE(\n             MAX(\n               CAST(SUBSTRING_INDEX(clearance_form_id, \'-\', -1) AS UNSIGNED)\n             ),\n             0\n           ) + 1\n      INTO next_id\n      FROM clearance_forms\n      WHERE clearance_form_id LIKE CONCAT(\'CF-\', year_part, \'-%\');\n\n    SET NEW.clearance_form_id =\n         CONCAT(\'CF-\', year_part, \'-\', LPAD(next_id,5,\'0\'));\nEND'
sql_modes=1098907648
definers='root@localhost'
client_cs_names='utf8mb4'
connection_cl_names='utf8mb4_unicode_ci'
db_cl_names='utf8mb4_unicode_ci'
created=1755580269903575
