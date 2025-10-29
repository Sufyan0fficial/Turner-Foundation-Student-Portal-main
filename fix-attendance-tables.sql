-- Fix attendance tables to add unique constraints
-- Run this in phpMyAdmin or MySQL command line

-- For tfsp_attendance_records table
ALTER TABLE wp_tfsp_attendance_records 
ADD UNIQUE KEY student_date (student_id, session_date);

-- For tfsp_attendance table  
ALTER TABLE wp_tfsp_attendance 
ADD UNIQUE KEY student_date (student_id, date);

-- Note: If you get "Duplicate entry" error, first remove duplicates:
-- DELETE t1 FROM wp_tfsp_attendance_records t1
-- INNER JOIN wp_tfsp_attendance_records t2 
-- WHERE t1.id > t2.id 
-- AND t1.student_id = t2.student_id 
-- AND t1.session_date = t2.session_date;

-- DELETE t1 FROM wp_tfsp_attendance t1
-- INNER JOIN wp_tfsp_attendance t2 
-- WHERE t1.id > t2.id 
-- AND t1.student_id = t2.student_id 
-- AND t1.date = t2.date;
