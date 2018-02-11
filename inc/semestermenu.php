<?php
include_once('mysql.php');

$semestersql = ""
	. "SELECT * "
	. " FROM semesters"
	. " WHERE active = 1"
	. " ORDER BY sem_order DESC"
	;

$result = mysql_query($semestersql);

if(!$result) {
	die("Database error: " . mysql_error());
}

while($semester = mysql_fetch_assoc($result)) {
	$sid = $semester['semester_id'];
	$sname = $semester['semester_name'];
	$syear = $semester['semester_year'];
	print <<<ENDSEMOPT
			<option value="$sid">$sname $syear</option>

ENDSEMOPT;
}
?>