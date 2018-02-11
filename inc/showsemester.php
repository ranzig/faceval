<?php
include_once('mysql.php');

$semestersql = ""
	. "SELECT * "
	. " FROM semesters"
	. " WHERE active = 1"
	. " ORDER BY sem_order DESC"
	. " LIMIT 1"
	;

$result = mysql_query($semestersql);

if(!$result) {
	die("Database error: " . mysql_error());
}

$semester = mysql_fetch_assoc($result);
$sem_name = $semester['semester_name'];
$sem_year = $semester['semester_year'];

print "$sem_name $sem_year";
?>