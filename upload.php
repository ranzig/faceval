<?php

$basedir = "/opt/gsw/faceval/archives";

if($_POST['check'] == 1) {
	process_form();
} else {
	print_form(array());
}

function process_form() {
	include('inc/getperms.php');
	include('inc/functions.php');

	$basedir = $GLOBALS['basedir'];
	$lock = $GLOBALS['lock'];

	$timestamp = time();

	$highperm = 0;

	foreach($permissions as $perm) {
		if($perm > $highperm) {
			$highperm = $perm;
		}
	}

	$errors = array();

	if($highperm < 3) {
		array_push($errors, "You don't have permission to upload data");
	}

	if($_POST['oo'] == 1) {
		$classtype = 'online';
	} else if($_POST['oo'] == 2) {
		$classtype = 'regular';
	} else {
		array_push($errors, "You didn't select a class type");
		$classtype = '';
	}

	if($_POST['semester'] == '') {
		array_push($errors, "You didn't select a semester");
	}

	$file_errors = array();

	foreach($_FILES as $key => $file) {
		if($file['error'] != 0) {
			$fname = $file['name'];
			$ferror = $file['error'];
			array_push($errors, "Error uploading $fname ($key): $ferror");

			if(file_exists($lock)) { unlink($lock); }
		}
	}

	if(count($errors) > 0) {
		print_error($errors);

		return;
	}

	include_once('inc/mysql.php');

	if($_POST['semester'] == 'existing') {
		$semid = $_POST['sem_id'];

		$cesm_sql = ""
			. "SELECT *"
			. " FROM semesters"
			. " WHERE semester_id = $semid"
			;

		$cesm_res = mysql_query($cesm_sql);

		if(!$cesm_res) {
			die("Database error (semester check): " . mysql_error());
		}

		$row = mysql_fetch_assoc($cesm_res);

		$semname = $row['semester_name'];
		$semyear = $row['semester_year'];
	} else {
		$semname = $_POST['sem_name'];
		$semyear = $_POST['sem_year'];
		$semorder = $_POST['sem_order'];

		$semyear = preg_replace('/[^0-9]/', "", $semyear);

		$csem_sql = ""
			. "SELECT *"
			. " FROM semesters"
			. " WHERE semester_name LIKE '$semname'"
			. " AND semester_year = $semyear"
			;

		$csem_res = mysql_query($csem_sql);

		if(!$csem_res) {
			die("Database error (semester check): " . mysql_error());
		}

		if(mysql_num_rows($csem_res) == 0) {
			$sidsql = ""
				. "SHOW TABLE STATUS LIKE 'semesters'"
				;

			$sidres = mysql_query($sidsql);

			if(!$sidres) {
				die("Database error (semester id): " . mysql_error());
			}

			$row = mysql_fetch_assoc($sidres);

			$semid = $row['Auto_increment'];

			$nsem_sql = ""
				. "INSERT INTO semesters"
				. " (sem_order, semester_name, semester_year, active)"
				. " VALUES ($semorder, '$semname', $semyear, 0)"
				;

			$nsem_res = mysql_query($nsem_sql);

			if(!$nsem_res) {
				die("Database error (insert semester): " . mysql_error());
			}
		} else {
			$row = mysql_fetch_assoc($csem_res);
			$semid = $row['semester_id'];
			$semname = $row['semester_name'];
			$semyear = $row['semester_year'];
		}
	}

	$folder = ""
		. $timestamp
		. "_" . $semname
		. "_" . $semyear
		. "_" . $classtype
		;
	$workdir = "$basedir/$folder";

	if(!is_dir($workdir)) {
		mkdir($workdir, 0777, true);
	}

	foreach($_FILES as $key => $file) {
		if($key == 'surveys') {
			$ext = ".zip";
		} else {
			$ext = ".txt";
		}

		move_uploaded_file($file['tmp_name'], "$workdir/$key$ext");
	}

	include('inc/insidehead.php');
	print <<<CONTENT

<h1>Success!</h1>

<p>
Your survey data files for <strong>$classtype</strong> classes in <strong>$semname of $semyear</strong> have been uploaded.
</p>
<p>
If the above information (semester and class type) is correct, <a href='verify.php?sid=$semid&ct=$classtype&ts=$timestamp'>click here</a> to verify the integrity of the data.
</p>
<p>
If anything is incorrect, <a onclick="history.go(-1);return false;" href="#">click here</a> to go back and make sure you filled in the form correctly.
</p>

CONTENT;
	include('inc/insidefoot.php');

}

function print_form($errors) {
	include('inc/insidehead.php');
	print "<div id='content'>\n";

	$highperm = 0;

	foreach($permissions as $perm) {
		if($perm > $highperm) {
			$highperm = $perm;
		}
	}

	if($highperm < 3) {
		print "<h4>You don't have permission to upload data</h4>\n";
		include("inc/insidefoot.php");
		return;
	}

	$updir = $GLOBALS['updir'];
	$lock = $GLOBALS['lock'];

	$thisdoc = $_SERVER['PHP_SELF'];

	$semopt = "";

	$semsql = ""
		. "SELECT *"
		. " FROM semesters"
		. " ORDER BY sem_order DESC"
		;

	include_once('inc/mysql.php');

	$semres = mysql_query($semsql);

	if(!$semres) {
		die("Database error (semesters): " . mysql_error());
	}

	$semorder = 0;
	$semcount = 0;

	while($row = mysql_fetch_assoc($semres)) {
		$opttxt = $row['semester_name'] . " " . $row['semester_year'];
		$optvalue = $row['semester_id'];
		$sodb = $row['sem_order'];

		if($semorder < ($sodb + 1)) {
			$semorder = $sodb + 1;
		}

		if($semcount == 0) {
			$selected = " selected='true'";
		} else {
			$selected = "";
		}
		$semcount++;

		$semopt .= "\n<option value='$optvalue'$selected>$opttxt</option>";
	}

	print <<<FORM

<h1>Upload files</h1>

<p>
After <a href="#prep">preparing your files</a>, use this form to upload them.
</p>

<p>
<form enctype='multipart/form-data' action='$thisdoc' method='post'>
<input type='hidden' name='check' value='1'/>
<input type='hidden' name='sem_order' value='$semorder'/>
<input type='hidden' name='MAX_FILE_SIZE' value='5242880'/>
Teachers Table: <input name='ttable' type='file'/><br/>
CRN-Course table: <input name='cctable' type='file'/><br/>
Surveys zip: <input name='surveys' type='file'/><br/>
Class type: <select name='oo'>
	<option disabled='true' selected='true'>Select</option>
	<option value='1'>Online</option>
	<option value='2'>Regular</option>
</select><br/>
<input type='radio' name='semester' value='existing' id='sem_exist'/>Existing semester: 
	<select name='sem_id' onchange="document.getElementById('sem_exist').checked = true;">$semopt
	</select><br/>
<input type='radio' name='semester' value='new' id='sem_new'/>New Semester: 
	<select name='sem_name' onchange="document.getElementById('sem_new').checked = true;">
		<option value='Fall'>Fall</option>
		<option value='Spring'>Spring</option>
	</select> 
	<input name='sem_year' value='$thisyear' size='5' onchange="document.getElementById('sem_new').checked = true;"/><br/>
<input type='submit' value='upload'/>
</form>
</p>

<hr width='50%'/>

<a name='prep'/>
<h1>File preparation</h1>

<div class='left'>

<h2>Teachers table</h2>

<p>
The teachers table is a plain text file of <strong>tab-delimited</strong> information about the class instructors. The first line of this file should <strong>not contain field names</strong>, but should instead contain actual data.  The fields are:
<ul>
<li>
<em>Instructor ID</em> - The instructor's GSW ID number
</li>
<li>
<em>Department ID</em> - The ID number of the department under which the course was taught
</li>
<li>
<em>Instructor name</em> - The name of the instructor <strong>in "lastname, firstname" format</strong>.
</li>
</ul>
Note that if an instructor taught classes for more than one department, there may be more than one row in the teachers table file attributed to that instructor.  Here's an example of a teachers table file:
</p>
<p>
<img src='img/teachers.png'/>
</p>

<h2>CRN-Course table</h3>

<p>
The CRN-Course table is a plain text file of <strong>tab-delimted</strong> info about a the classes taught in a given semester.  Just like the teachers table, the first line of this file should <strong>not contain field names</strong>.  The fields in this file are:
<ul>
<li>
<em>Course CRN</em> - The CRN for the course
</li>
<li>
<em>Department ID</em> - The ID number for the department under which the course was taught
</li>
<li>
<em>Instructor ID</em> - The ID number for the instructor that taught the course
</li>
<li>
<em>Class name</em> - The shortened form of the name of the class, like "CIS 1000".
</li>
</ul>
Note that if a class was taught by more than one instructor, that CRN may be in the table more than once.  Also, if an instructor taught more than one class (as they often do), there will be more than one entry in the table for that instructor.  Here's an example of the CRN-Course table file:
</p>
<p>
<img src='img/crn-course.png'/>
</p>

<h2>Surveys zip file</h2>

<p>
The surveys zip file contains plain text files, which in turn contain information about the surveys taken for each class.  Each text file should be named with the CRN of the class and have a ".txt" extension.
</p>

<p>
Here's how to create the zip file:
<ol>
<li>
Install a zip compression utility like <a href='http://www.7-zip.org/'>7-Zip</a> or <a href='http://www.winzip.com/'>WinZip</a>.  I personally prefer 7-Zip because it's completely free and it's easy to use.
</li>
<li>
Open the directory that has the survey files.  Make sure this directory contains <em>only survey text files</em>, and not either of the table files or any other foreign files.  Hit ctrl-A to select all the files:
<p>
<img src='img/zipit-1.png'/>
</p>
</li>
<li>
Right-click on any one of the selected files.  From the "7-Zip" submenu, click "Add to '&lt;filename&gt;.zip'" (note that "&lt;filename&gt;" will be replaced with the name of your current directory).  If you're using WinZip, the menu items have different names, but the functionality is the same:
<p>
<img src='img/zipit-2.png'/>
</p>
</li>
<li>
You're done!  There should be a file in your current directory that ends in ".zip"; that's the file you need to upload:
<p>
<img src='img/zipit-3.png'/>
</p>
</li>
</p>
</div>

FORM;

	print "</div>";
	include("inc/insidefoot.php");
}
?>
