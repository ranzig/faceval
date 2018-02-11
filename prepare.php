<?php
include('inc/getperms.php');
include('inc/functions.php');

if($highest_perm < 3) {
	print_error(array("You don't have permission to be here"));
	die;
}

$pcheck = $_POST['check'];

if($pcheck == 1) {
	process_form();
} else {
	print_form(array());
}

function print_form($errors) {
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

	/* display the form */
	include('inc/insidehead.php');

	print <<<INTRO

<div id='content'>

<h1>Prepare online surveys</h1>

<p>
On this page, you can prepare the system to accept logins for
<a href='survey.php'>taking the survey online</a>.  You'll need a special
data file.  If you haven't already, please <a href='#prep'>read the
information</a> regarding this file.
<p>

<p>
<form enctype='multipart/form-data' action='$thisdoc' method='post'>
<input type='hidden' name='check' value='1'/>
<input type='hidden' name='MAX_FILE_SISE' value='5242800'/>

Data file: <input name='dfile' type='file'/>
<br/>

Translation table:
<select name='translation'>
	<option value='none'>None</option>
	<option value='default' selected='true'>Default</option>
	<option value='file' id='fileoption'>File (specify below)</option>
</select>
<br/>
<input name='transfile' type='file' onchange="document.getElementById('fileoption').selected = true;"/>
<br/>

Class type: <select name='oo'>
	<option disabled='true' selected='true'>Select</option>
	<option value='1'>Online</option>
	<option value='2'>Regular</option>
</select>
<br/>

<input type='radio' name='semester' value='existing' id='sem_exist'/>Existing semester: 
	<select name='sem_id' onchange="document.getElementById('sem_exist').checked = true;">$semopt
	</select>
<br/>

<input type='radio' name='semester' value='new' id='sem_new'/>New Semester: 
	<select name='sem_name' onchange="document.getElementById('sem_new').checked = true;">
		<option value='Fall'>Fall</option>
		<option value='Spring'>Spring</option>
	</select> 
	<input name='sem_year' value='$thisyear' size='5' onchange="document.getElementById('sem_new').checked = true;"/>
<br/>

Cutoff time: <input name='cutoff' value='30' size='4'/> days
<br/>

<input type='checkbox' name='clear' value='yes' checked='true'/> Clear out old data
<br/>

<input type='submit' value='Prepare'/>

<hr width='50%'/>

<a name='prep'/>
<h1>Preparation</h1>

<div class='left'>

<a name='datafile'/>
<h2>Data file</h2>

<p>
The data file is a plain text file of <strong>tab-delimited</strong>
about the students that will be taking the survey and the classes they're
for which they're taking the survey.  The first line of this file should
<strong>not contain field names</strong>.  The fields are:

<ul>
<li>
<em>Student ID#</em> - The student's 9-digit GSW ID#.
</li>
<li>
<em>Course CRN</em> - The Course Registration Number for the course
</li>
<li>
<em>Course Name</em> - The shortened name of the course, like "CIS 1000".
</li>
<li>
<em>Department ID</em> - The ID number for the department under which the course is taught.  Please keep in mind that this is the department ID for <em>this faculty evaluation system</em>.  If you pulled this info from another system which has a different department ID setup, you will need a <a href='#translation'>translation table</a> so the ID numbers get squared away properly.
</li>
<li>
<em>Instructor ID(s)</em> - The GSW ID#(s) of the instructor(s) teaching the course.  If the course was taught by only one instructor (which is usually the case), this value is just a 9-digit number.  If there are more instructors, this value is a <em>semicolon-separated</em> list of 9-digit numbers.
</li>
<li>
<em>Instructor Name</em> - The Name of the instructor in "lastname, firstname" format (like "Hackett, Royce" or "Hackett, R.").  If the course was taught by more than one instructor, this value is a <em>semicolon-separated</em> list of instructor names.  Note that the number of instructor IDs must match the number of instructor names for any row in the file.
</li>
</ul>
This file will most likely be fairly sizable -- one line for each student/class pairing.  Also, there will be a lot of duplicated data.  For instance, if a student was in 3 classes this semester, there will be three rows associated with his student ID#.  You should also note that, while there is a lot of duplicated data, each student/class pairing is unique.  Here's what the data file should look like:
</p>

<p>
<img src='img/surveyprep.png'/>
</p>

<hr width='50%'/>

<a name='translation'/>
<h2>Department ID translation table</h2>

<p>
The translation table is an (optional) data file that maps department ID numbers from a separate system to department IDs on this system.  The <a href='default_translation.txt' target='_new'>default translation table</a> is used for translating from Banner department IDs.  This file has two <strong>tab-delimitted fields</strong>:
<ul>
<li>
<em>Source ID number</em> - The ID number from the other system.
</li>
<li>
<em>Destination ID number</em> - The ID number from this system that matches the department from the source system.
</li>
</ul>
Note that the numbers in the example below are padded with zeros.  That's not actually neccessary -- it'll work just fine if the numbers are non-padded integers.  Here's what the file should look like:
</p>

<p>
<img src='img/translation.png'/>
</p>

</div>
INTRO;

	print "</div>\n\n";

	include('inc/insidefoot.php');
}
?>