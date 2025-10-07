TYPE=VIEW
query=select `cf`.`clearance_form_id` AS `clearance_form_id`,`cf`.`user_id` AS `user_id`,`cf`.`academic_year_id` AS `academic_year_id`,`cf`.`semester_id` AS `semester_id`,`cf`.`clearance_type` AS `clearance_type`,`cf`.`status` AS `status`,`cf`.`applied_at` AS `applied_at`,`cf`.`completed_at` AS `completed_at`,`cf`.`rejected_at` AS `rejected_at`,`cf`.`grace_period_ends` AS `grace_period_ends`,`ay`.`year` AS `academic_year`,`s`.`semester_name` AS `semester_name`,`cp`.`period_id` AS `clearance_period_id`,`cp`.`sector` AS `sector`,`cp`.`status` AS `period_status` from (((`online_clearance_db`.`clearance_forms` `cf` join `online_clearance_db`.`academic_years` `ay` on(`cf`.`academic_year_id` = `ay`.`academic_year_id`)) join `online_clearance_db`.`semesters` `s` on(`cf`.`semester_id` = `s`.`semester_id`)) left join `online_clearance_db`.`clearance_periods` `cp` on(`cf`.`academic_year_id` = `cp`.`academic_year_id` and `cf`.`semester_id` = `cp`.`semester_id` and `cf`.`clearance_type` = `cp`.`sector`))
md5=29767f340b0063f59348338560913a78
updatable=0
algorithm=0
definer_user=root
definer_host=localhost
suid=2
with_check_option=0
timestamp=0001757986179377117
create-version=2
source=SELECT \n    cf.clearance_form_id,\n    cf.user_id,\n    cf.academic_year_id,\n    cf.semester_id,\n    cf.clearance_type,\n    cf.status,\n    cf.applied_at,\n    cf.completed_at,\n    cf.rejected_at,\n    cf.grace_period_ends,\n    ay.year as academic_year,\n    s.semester_name,\n    cp.period_id as clearance_period_id,\n    cp.sector,\n    cp.status as period_status\nFROM `clearance_forms` cf\nJOIN `academic_years` ay ON cf.academic_year_id = ay.academic_year_id\nJOIN `semesters` s ON cf.semester_id = s.semester_id\nLEFT JOIN `clearance_periods` cp ON (\n    cf.academic_year_id = cp.academic_year_id \n    AND cf.semester_id = cp.semester_id \n    AND cf.clearance_type = cp.sector\n)
client_cs_name=cp850
connection_cl_name=cp850_general_ci
view_body_utf8=select `cf`.`clearance_form_id` AS `clearance_form_id`,`cf`.`user_id` AS `user_id`,`cf`.`academic_year_id` AS `academic_year_id`,`cf`.`semester_id` AS `semester_id`,`cf`.`clearance_type` AS `clearance_type`,`cf`.`status` AS `status`,`cf`.`applied_at` AS `applied_at`,`cf`.`completed_at` AS `completed_at`,`cf`.`rejected_at` AS `rejected_at`,`cf`.`grace_period_ends` AS `grace_period_ends`,`ay`.`year` AS `academic_year`,`s`.`semester_name` AS `semester_name`,`cp`.`period_id` AS `clearance_period_id`,`cp`.`sector` AS `sector`,`cp`.`status` AS `period_status` from (((`online_clearance_db`.`clearance_forms` `cf` join `online_clearance_db`.`academic_years` `ay` on(`cf`.`academic_year_id` = `ay`.`academic_year_id`)) join `online_clearance_db`.`semesters` `s` on(`cf`.`semester_id` = `s`.`semester_id`)) left join `online_clearance_db`.`clearance_periods` `cp` on(`cf`.`academic_year_id` = `cp`.`academic_year_id` and `cf`.`semester_id` = `cp`.`semester_id` and `cf`.`clearance_type` = `cp`.`sector`))
mariadb-version=100432
