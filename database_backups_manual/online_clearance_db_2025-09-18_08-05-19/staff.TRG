TYPE=TRIGGERS
triggers='CREATE DEFINER=`root`@`localhost` TRIGGER staff_bi\nBEFORE INSERT ON staff\nFOR EACH ROW\nBEGIN\n  SET NEW.employee_number = UPPER(NEW.employee_number);\n  IF NEW.employee_number NOT REGEXP \'^LCA[0-9]{4}[A-Z]$\' THEN\n    SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Invalid employee number format (expected LCAXXXXP)\';\n  END IF;\nEND' 'CREATE DEFINER=`root`@`localhost` TRIGGER staff_bu\nBEFORE UPDATE ON staff\nFOR EACH ROW\nBEGIN\n  SET NEW.employee_number = UPPER(NEW.employee_number);\n  IF NEW.employee_number NOT REGEXP \'^LCA[0-9]{4}[A-Z]$\' THEN\n    SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Invalid employee number format (expected LCAXXXXP)\';\n  END IF;\nEND'
sql_modes=1098907648 1098907648
definers='root@localhost' 'root@localhost'
client_cs_names='cp850' 'cp850'
connection_cl_names='cp850_general_ci' 'cp850_general_ci'
db_cl_names='utf8mb4_unicode_ci' 'utf8mb4_unicode_ci'
created=1757532124579995 1757532124595222
