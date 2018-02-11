<?php
include('inc/functions.php');

/* get permissions */
include('inc/getperms.php');
$highest_perm = 3;
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
	#$sem_id = 16;
	#$classtype = 'regular';
	#$timestamp = 1401452104;
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

$basedir = '/opt/gsw/faceval';
$workdir = "$basedir/archives/$timestamp" 
	. "_$sem_name" 
	. "_$sem_year"
	. "_$classtype"
	;

$ttable = "$workdir/ttable.txt";
$cctable = "$workdir/cctable.txt";
$surzip = "$workdir/surveys.zip";

if(backup_db("$sem_name $sem_year $classtype: Before file verification")) {
	array_push($info, "Database successfully backed up");
} else {
	array_push($errors, "Database backup failed");
}

/* unzip the zip */
$surdir = "$workdir/surveys";
if(is_dir($surdir)) {
	foreach(glob("$surdir/*") as $file) {
		unlink($file);
	}

	rmdir($surdir);
}
unzip($surzip, "$surdir");

/* check cctable entries vs. survey files in surveys directory */
$crns = array();
$cctlines = file($cctable);

foreach($cctlines as $line) {
	$line = rtrim($line);

	$parts = preg_split("/\t+/", $line);
	$crn = $parts[0];
	$dept = preg_replace("/^0+/", '', $parts[1]);
	$iid = $parts[2];
	$name = $parts[3];

	if(preg_match("/[^0-9]/", $crn)){
		array_push($errors, "Bad line format or bad CRN in CRN-Course table: $line");
	}

	if($crn != '') {
		$crns[$crn] = $iid;

		if(!is_readable("$surdir/$crn.txt")) {
			array_push($errors, "No text file for $crn, $name");
		}
	}
}

/* check survey files vs. cctable entries */
foreach(glob("$surdir/*.txt") as $file) {
	$crn = preg_replace("/^.*\//", '', $file);
	$crn = preg_replace("/\.txt/", '', $crn);

	if(preg_match("/[^0-9]/", $crn) || !preg_match("/\.txt/", $file)) {
		array_push($errors, "Bad survey file format: $crn.txt");
	}

	if($crns[$crn] == '') {
		array_push($errors, "No entry for $crn found in CRN-Course table");
	} else {}
}

/* check database to see what courses are already in the database */
if(count($crns) > 0) {
	include_once('inc/mysql.php');

	$cc_sql = ""
		. "SELECT *"
		. " FROM courses"
		. " WHERE semester_id = $sem_id"
		/*
		. " AND (course_crn = "
		. implode(" OR course_crn = ", array_keys($crns))
		. ")"
		 */
		;

	if($classtype == "online") {
		$cc_sql .= " AND is_online = 1";
	} else {
		$cc_sql .= " AND is_online = 0";
	}

	$cc_res = mysql_query($cc_sql);

	if(!$cc_res) {
		die("Database error (CRN check): " . mysql_error(). "<br>" . $cc_sql);
	}

	while($row = mysql_fetch_assoc($cc_res)) {
			$crn = $row['course_crn'];
		array_push($info, "Database entry found for CRN $crn");
	}
} else {
	array_push($errors, "No courses to add");
}

/* check instructor file */
$ttlines = file($ttable);

foreach($ttlines as $line) {
	$line = rtrim($line);

	$parts = preg_split("/\t+/", $line);
	$iid = trim($parts[0]);
	$dept_id = trim(preg_replace("/^0+/", '', $parts[1]));
	$instr_name = $parts[2];
	$instr_lname = preg_replace("/\, .*/", '', $instr_name);
	$instr_fname = preg_replace("/.*\, /", '', $instr_name);

	if(
		($iid == '')
		|| ($dept_id == '')
		|| ($instr_name == '')
		|| ($instr_lname == '')
		//|| ($instr_fname == '')
		|| (preg_match("/[^0-9]/", $iid))
		|| (preg_match("/[^0-9]/", $dept_id))
	) {
		array_push($errors, "Bad line format or invalid data in Teachers table: $line");
	} else {
		include_once('inc/mysql.php');

		$ic_sql = ""
			. "SELECT perm_level"
			. " FROM permissions"
			. " WHERE instr_id LIKE '$instr_id'"
			. " AND dept_id = $dept_id"
			. " AND perm_level >= 1"
			;

		$ic_res = mysql_query($ic_sql);

		if(!$ic_res) {
			die("Database error (instructor check): " . mysql_error());
		}

		if(mysql_num_rows($ic_res) < 1) {
			/* check if instructor exists in instructors table */
			$ic2_sql = ""
				. "SELECT *"
				. " FROM instructors"
				. " WHERE instr_id LIKE '$instr_id'"
				;

			$ic2_res = mysql_query($ic2_sql);

			if(!$ic2_res) {
				die("Database error (instructor $instr_id): " . mysql_error());
			}

			/* if not, add instructor */
			if(mysql_num_rows($ic2_res) < 1) {
				$add_sql = '';
				if($instr_fname != '') {
					$add_sql = ""
						. "INSERT INTO instructors"
						. " (instr_id, instr_pass, instr_lname, instr_fname)"
						. " VALUES"
						. " ('$instr_id', MD5('gsweval'), '$instr_lname', '$instr_fname')"
						;
				} else {
					$add_sql = ''
						. 'INSERT INTO instructors'
						. ' (instr_id, instr_pass, instr_lname)'
						. ' VALUES'
						. " ('$instr_id', MD5('gsweval'), '$instr_lname')"
						;

					$add_res = mysql_query($add_sql);

					if(!$add_res) {
						die("Database error (add instructor $instr_id): " . mysql_error());
					} else {
						array_push($info, "Instructor added: $line");
					}
				}
			}

			/* in either case, add permission 1 */
			$perm_sql = ""
				. "INSERT INTO permissions"
				. " (instr_id, dept_id, perm_level)"
				. " VALUES"
				. " ('$instr_id', $dept_id, 1)"
				;

			$perm_res = mysql_query($perm_sql);

			if(!$perm_res) {
				die("Database error (add permissions $instr_id): " . mysql_error());
			} else {
				array_push($info, "Gave $instr_lname, $instr_fname permission 1 on department $dept_id");
			}
		}
	}
}

if(count($errors) > 0) {
	print_error($errors);
	die;
}

$semname = $sem_name;
$semyear = $sem_year;

include('inc/insidehead.php');

print <<<SUCCESS

<h1>Success!</h1>

<p>
Your data for <strong>$classtype</strong> classes in <strong>$semname of $semyear</strong> has been verified.
</p>
<p>
Please <a href='insertdata.php?sid=$sem_id&ct=$classtype&ts=$timestamp'>Click here</a> to insert the data into the database.
</p>

<hr width='50%'/>

SUCCESS;

if(count($info) > 0) {
	print "<h2>Information:</h2>\n<ul>\n";

	foreach($info as $line) {
		print "<li>\n$line\n</li>\n";
	}

	print "</ul>";
}

include('inc/insidefoot.php');
?>
