TYPE=VIEW
query=select `cp`.`period_id` AS `period_id`,`cp`.`academic_year_id` AS `academic_year_id`,`cp`.`semester_id` AS `semester_id`,`cp`.`sector` AS `sector`,`cp`.`status` AS `status`,`cp`.`start_date` AS `start_date`,`cp`.`end_date` AS `end_date`,`ay`.`year` AS `academic_year`,`s`.`semester_name` AS `semester_name` from ((`online_clearance_db`.`clearance_periods` `cp` join `online_clearance_db`.`academic_years` `ay` on(`cp`.`academic_year_id` = `ay`.`academic_year_id`)) join `online_clearance_db`.`semesters` `s` on(`cp`.`semester_id` = `s`.`semester_id`)) where `cp`.`status` in (\'Ongoing\',\'Paused\')
md5=3deaeac39213e4df785878ae22ad4ae2
updatable=1
algorithm=0
definer_user=root
definer_host=localhost
suid=2
with_check_option=0
timestamp=0001757986179373120
create-version=2
source=SELECT \n    cp.period_id,\n    cp.academic_year_id,\n    cp.semester_id,\n    cp.sector,\n    cp.status,\n    cp.start_date,\n    cp.end_date,\n    ay.year as academic_year,\n    s.semester_name\nFROM `clearance_periods` cp\nJOIN `academic_years` ay ON cp.academic_year_id = ay.academic_year_id\nJOIN `semesters` s ON cp.semester_id = s.semester_id\nWHERE cp.status IN (\'Ongoing\', \'Paused\')
client_cs_name=cp850
connection_cl_name=cp850_general_ci
view_body_utf8=select `cp`.`period_id` AS `period_id`,`cp`.`academic_year_id` AS `academic_year_id`,`cp`.`semester_id` AS `semester_id`,`cp`.`sector` AS `sector`,`cp`.`status` AS `status`,`cp`.`start_date` AS `start_date`,`cp`.`end_date` AS `end_date`,`ay`.`year` AS `academic_year`,`s`.`semester_name` AS `semester_name` from ((`online_clearance_db`.`clearance_periods` `cp` join `online_clearance_db`.`academic_years` `ay` on(`cp`.`academic_year_id` = `ay`.`academic_year_id`)) join `online_clearance_db`.`semesters` `s` on(`cp`.`semester_id` = `s`.`semester_id`)) where `cp`.`status` in (\'Ongoing\',\'Paused\')
mariadb-version=100432
