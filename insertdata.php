<?php
include('inc/functions.php');

/* get permissions */
include('inc/getperms.php');
if($highest_perm < 3) {
	print_error(array("You don't have permission to be here"));
}

/* check query string */
if(
	($_GET['sid'] == '')
	|| ($_GET['ct'] == '')
	|| ($_GET['ts'] == '')
) {
	print_error(array("Bad query string"));
	die;
} else {
	$sem_id = $_GET['sid'];
	$classtype = $_GET['ct'];
	$timestamp = $_GET['ts'];
}

/* get semester info */
include_once('inc/mysql.php');
$sem_sql = ""
	. "SELECT *"
	. " FROM semesters"
	. " WHERE semester_id = $sem_id"
	;

$sem_res = mysql_query($sem_sql);

if(!$sem_res) {
	die("Database error (semester info): " . mysql_error());
}

$row = mysql_fetch_assoc($sem_res);

$sem_name = $row['semester_name'];
$sem_year = $row['semester_year'];

/* start work */
$errors = array();
$info = array();

/* back up database first */
if(backup_db("$sem_name $sem_year $classtype: Before data insertion")) {
	array_push($info, "Database successfully backed up");
} else {
	array_push($errors, "Database backup failed");
}

$basedir = '/opt/gsw/faceval';
$workdir = "$basedir/archives/$timestamp" 
	. "_$sem_name" 
	. "_$sem_year"
	. "_$classtype"
	;

$ttable = "$workdir/ttable.txt";
$cctable = "$workdir/cctable.txt";
$surzip = "$workdir/surveys.zip";
$surdir = "$workdir/surveys";

/* get info for courses already in the database */
$cc_sql = ""
	. "SELECT course_crn, instr_id"
	. " FROM course_instructor, courses"
	. " WHERE course_instructor.course_id = courses.course_id"
	. " AND semester_id = $sem_id"
	;

if($classtype == 'online') {
	$cc_sql .= " AND is_online = 1";
	$is_online = 1;
} else {
	$cc_sql .= " AND is_online = 0";
	$is_online = 0;
}

$cc_res = mysql_query($cc_sql);

if(!$cc_res) {
	die("Database error (CRN check): " . mysql_error());
}

$db_crns = array();

while($row = mysql_fetch_assoc($cc_res)) {
	$crn = $row['course_crn'];
	$db_crns[$crn] = $row['instr_id'];
}

if(count($db_crns) > 0) {
	array_push($info, "Courses already in database: " . implode(", ", array_keys($db_crns)));
}

if(count($errors) > 0) {
	print_error($errors);
}

/* insert data into courses table */
$course_instructor = array();

$cs_add = array();

$cclines = file($cctable);
foreach($cclines as $line) {
	$line = rtrim($line);

	$parts = preg_split("/\t+/", $line);
	$crn = $parts[0];
	$dept_id = preg_replace("/^0+/", '', $parts[1]);
	$iid = $parts[2];
	$name = $parts[3];

	$pattern = "/" . $iid . "/";

	if((preg_match($pattern, $course_instructor[$crn]) <= 0) && ($db_crns[$crn] == '')) {
		if($course_instructor[$crn] != '') {
			$course_instructor[$crn] .= "," . $iid;
		} else {
			$course_instructor[$crn] = $iid;
		}

		array_push($cs_add, "($sem_id, $dept_id, '$crn', '$name', $is_online)");
//		array_push($info, "Adding $name (CRN #$crn) to the database");
	}
}

if(count($cs_add) <= 0) {
	array_push($info, "No courses to add");
} else {
	$course_sql = ""
		. "INSERT INTO courses"
		. " (semester_id, dept_id, course_crn, course_name, is_online)"
		. " VALUES "
		. implode(", ", $cs_add)
		;

	$course_res = mysql_query($course_sql);

	if(!$course_res) {
		die("Database error (insert courses): " . mysql_error());
	}

	$number = mysql_affected_rows();

	array_push($info, "$number courses added");
}

/* get mapping of CRN to course_id */
$crn_course = array();

$cc_sql = ""
	. "SELECT course_crn, course_id"
	. " FROM courses"
	. " WHERE semester_id = $sem_id"
	;

if($classtype == 'online') {
	$cc_sql .= " AND is_online = 1";
	$is_online = 1;
} else {
	$cc_sql .= " AND is_online = 0";
	$is_onle = 0;
}

$cc_res = mysql_query($cc_sql);

if(!$cc_res) {
	die("Database error (CRN check): " . mysql_error());
}

while($row = mysql_fetch_assoc($cc_res)) {
	$crn = $row['course_crn'];
	$cid = $row['course_id'];

	$crn_course[$crn] = $cid;
}

/* now insert instructor info using newly mapped $crn_course */
$course_instr = array();

$cclines = file($cctable);
foreach($cclines as $line) {
	$line = rtrim($line);

	$parts = preg_split("/\t+/", $line);
	$crn = $parts[0];

	if($crn != '') {
		$iid = $parts[2];
		$cid = $crn_course[$crn];

		/* check to make sure course/instructor isn't already in the table */
		$ci_check_sql = ""
			. 'SELECT count(*)'
			. ' FROM course_instructor'
			. ' WHERE course_id = ' . $cid
			. ' AND instr_id like \'' . $iid . '\''
			;
		$ci_check_res = mysql_query($ci_check_sql);
		if(!$ci_check_res) {
			die("Database error (Course/Instructor check): " . mysql_error());
		}

		$ci_check_count = mysql_result($ci_check_res, 0);

		if($db_crns[$crn] == '' && $ci_check_count <= 0) {
			$addition = "($cid, '$iid')";
			if(array_search($addition, $course_instr) === FALSE) {
				array_push($course_instr, $addition);
			}
		}
	}
}

if(count($course_instr) <= 0) {
	array_push($info, "No new course instructors to add");
} else {
	$instr_sql = ""
		. "INSERT INTO course_instructor"
		. " (course_id, instr_id)"
		. " VALUES "
		. implode(", ", $course_instr)
		;

	$instr_res = mysql_query($instr_sql);

	if(!$instr_res) {
		die("Database error (course_instructor): " . mysql_error());
	}

	$number = mysql_affected_rows();
	array_push($info, "$number Course to Instructor mappings added");
}

/* now insert survey data */
$surveydata = array();
$comments = array();

$translate['a'] = '1';
$translate['b'] = '2';
$translate['c'] = '3';
$translate['d'] = '4';
$translate['e'] = '5';
$translate['1'] = '1';
$translate['2'] = '2';
$translate['3'] = '3';
$translate['4'] = '4';
$translate['5'] = '5';
$translate['6'] = '0';
$translate['0'] = '0';
$translate['.'] = '0';
$translate[' '] = '0';

foreach(glob("$surdir/*.txt") as $surfile) {
	$surlines = file($surfile);

	$crn = preg_replace("/.*\//", '', $surfile);
	$crn = preg_replace("/.txt/", '', $crn);

	$cid = $crn_course[$crn];

	foreach($surlines as $line) {
		$line = rtrim($line);

		$linequestions = array();

		if($classtype == 'online') {
			$parts = preg_split("//", $line);
		} else {
			$parts = preg_split("//", $line);
		}

		if($parts[0] != "No" && $parts[1] != ''){
			for($i = 1; $i <= 16; $i++) {
				$qkey = 'q' . $i;
				$answer = $parts[$i];
				if(!is_numeric($answer)) {
					$answer = strtolower($answer);
				}
				if($answer == '') {
					$answer = 0;
				}
				$linequestions[$qkey] = $translate[$answer];
			}

			if($classtype != 'online') {
				$answer = $parts[17];
				if(!is_numeric($answer)) {
					$answer = strtolower($answer);
				}
				if($answer == '') {
					$answer = 0;
				}
				$linequestions['q17'] = $translate[$answer];

				$com_num = 18;
			} else {
				$linequestions['q17'] = 0;
				$com_num = 17;
			}

			$surveyadd = ""
				. "($cid, "
				. implode(", ", $linequestions)
				. ")"
				;

			array_push($surveydata, $surveyadd);

			if($parts[$com_num] != '') {
				$comment = $parts[$com_num];
				$comment = preg_replace("/\'/", '\\\'', $comment);
				array_push($comments, "($cid, '$comment')");
			}
		}
	}
}

if(count($surveydata) > 0) {
	$number = 0;

	foreach($surveydata as $survey) {
		$sur_sql = ""
			. "INSERT INTO surveys"
			. " (course_id, q1, q2, q3, q4, q5, q6, q7, q8,"
			. " q9, q10, q11, q12, q13, q14, q15, q16, q17)"
			. " VALUES "
			. $survey
			;

		$sur_res = mysql_query($sur_sql);

		if(!$sur_res) {
			print "$survey\n<br/>";
			die("Database error (surveys): " . mysql_error());
		}

		$number += 1;
	}

	array_push($info, "$number surveys added");
} else {
	array_push($info, "No survey data to add");
}

//print implode(", ", $comments);

/* what's wrong with inserting comments?
if(count($comments) > 0) {
	$comm_sql = ""
		. "INSERT INTO comments"
		. " (course_id, comment)"
		. " VALUES "
		. implode(', ', $comments)"
		;

	$comm_res = mysql_query($comm_sql);

	if(!$comm_res) {
		die("Database error (comments): " . mysql_error());
	}

	$number = mysql_affected_rows();

	array_push($info, "$number comments added");
} else {
	array_push($info, "No course comments to add");
}
*/

/* last little bit */
if(count($errors) > 0) {
	print_error($errors);
	die;
}

/* update semesters table */
$su_sql = ""
	. "UPDATE semesters"
	. " SET active = 1"
	. " WHERE semester_id = $sem_id"
	;

$su_res = mysql_query($su_sql);

if(!$su_res) {
	die("Database error (semester info): " . mysql_error());
}

include('inc/insidehead.php');

print <<<THEEND

<h1>Success!</h1>

<p>
You've successfully added data for <strong>$classtype</strong> classes in <strong>$sem_name of $sem_year</strong>.
</p>
<p>
<a href='main.php?sid=$sem_id'>Click here</a> to view the new information.
</p>

THEEND;

if(count($info) > 0) {
	print "<h2>Information</h2>\n";
	print "<ul>\n";
	foreach($info as $line) {
		print "<li>\n$line\n</li>\n";
	}
	print "</ul>\n";
}

include('inc/insidefoot.php');
?>
