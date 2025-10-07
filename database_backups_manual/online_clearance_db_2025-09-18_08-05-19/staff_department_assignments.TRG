TYPE=TRIGGERS
triggers='CREATE DEFINER=`root`@`localhost` TRIGGER sda_bi_program_head_only\nBEFORE INSERT ON staff_department_assignments\nFOR EACH ROW\nBEGIN\n  DECLARE staff_category VARCHAR(50);\n  \n  SELECT s.staff_category INTO staff_category\n  FROM staff s\n  WHERE s.employee_number = NEW.staff_id;\n  \n  IF staff_category != \'Program Head\' THEN\n    SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'Only Program Heads can be assigned to departments\';\n  END IF;\nEND' 'CREATE DEFINER=`root`@`localhost` TRIGGER sda_bi_sector_validation\nBEFORE INSERT ON staff_department_assignments\nFOR EACH ROW\nBEGIN\n  \n  \n  \n  SET NEW.sector_id = (SELECT sector_id FROM departments WHERE department_id = NEW.department_id);\nEND'
sql_modes=1098907648 1098907648
definers='root@localhost' 'root@localhost'
client_cs_names='cp850' 'cp850'
connection_cl_names='cp850_general_ci' 'cp850_general_ci'
db_cl_names='utf8mb4_unicode_ci' 'utf8mb4_unicode_ci'
created=1757542021134931 1757542021144336
