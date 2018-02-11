<?php

if($_POST['check'] == 1) {
	process_form();
} else {
	print_form();
}

function process_form() {
	$crn = $_COOKIE['crn'];
	$gswid = $_COOKIE['gswid'];
	$cid = $_COOKIE['cid'];

	$info = array();

	array_push($info, "Course CRN: $crn");

	include('inc/functions.php');

	if(($crn == '') || ($gswid == '') || ($cid == '')) {
		$errors = array("Something went wrong.  Please <a href='survey.php'>validate</a> and resubmit the survey.");
		print_error_out($errors);
		die;
	}

	include_once('inc/mysql.php');

	/* insert data into surveys table */
	$timestamp = time();

	$colpart = "(course_id, timestamp";
	$datapart = "($cid, $timestamp";

	for($i=1; $i <= 17; $i++) {
		$key = 'q' . $i;
		$value = $_POST[$key];

		if(is_numeric($value)) {
			$colpart .= ", $key";
			$datapart .= ", $value";
		} else {
			$errors = array("Something went wrong.  Please <a href='survey.php'>validate</a> and resubmit the survey.");
			print_error_out($errors);
			die;
		}
	}

	$colpart .= ")";
	$datapart .= ")";

	$si_sql = ""
		. "INSERT INTO surveys"
		. " $colpart"
		. " VALUES"
		. " $datapart"
		;

	$si_res = mysql_query($si_sql);

	if(!$si_res) {
		die("Database error (survey data): " . mysql_error());
	}

	array_push($info, "Survey data added");

	/* insert comment */
	$comment = $_POST['comments'];

	if($comment != '') {
		if(get_magic_quotes_gpc()) {
			$comment = stripslashes($comment);
		}

		$comment = mysql_real_escape_string($comment);

		$ci_sql = ''
			. 'INSERT INTO comments'
			. ' (course_id, comment)'
			. ' VALUES'
			. " ($cid, \"$comment\")"
			;

		$ci_res = mysql_query($ci_sql);

		if(!$ci_res) {
			die("Database error (comment): " . mysql_error());
		}

		array_push($info, "Comment added: $comment");
	} else {
		array_push($info, "No comment to add");
	}

	/* update survey_log table set survey_complete = 1 */
	$slup_sql = ''
		. 'UPDATE survey_login'
		. ' SET'
		. " survey_complete = $timestamp"
		. ' WHERE'
		. " gswid = $gswid"
		. ' AND'
		. " course_id = $cid"
		;

	$slup_res = mysql_query($slup_sql);

	if(!$slup_res) {
		die("Database error (completion): " . mysql_error());
	}

	foreach(array_keys($_COOKIE) as $key) {
		setcookie($key, '');
	}

	array_push($info, "Survey Complete.  Thank you!");

	print <<<ENDRESULTS
<div id='head'>
	<table align='center'>
	<tr><td align='center'>
	<img src='img/seal.gif'/>
	</td><td align='left'>

	<p><strong>Georgia Southwestern State University</strong></p>
	<p><strong>Teaching Evaluation Survey</strong></p>
	</td></tr>
	</table>
</div>

<div id='content' align='left'>
<h1>Summary:</h1>

<p>
<ul>

ENDRESULTS;

	foreach($info as $line) {
		print "<li>$line</li>\n";
	}

	print <<<ENDRESULTS2
</ul>
</p>

</div>
ENDRESULTS2;
}

function print_form() {
	$crn = $_COOKIE['crn'];
	$gswid = $_COOKIE['gswid'];
	$cid = $_COOKIE['cid'];

	include('inc/functions.php');

	if(($crn == '') || ($gswid == '') || ($cid == '')) {
		$errors = array("You must <a href='survey.php'>validate</a> before you view the 	survey");
		print_error_out($errors);
		die;
	}

	$questions = array();

	/* get info for the survey */
	include('inc/mysql.php');

	$survey_sql = ""
		. "SELECT DISTINCT ques_no, ques_txt, answ_txt, answ_value"
		. " FROM survey_questions, survey_answers, courses"
		. " WHERE survey_questions.ques_id = survey_answers.ques_id"
		. " AND survey_questions.is_online = courses.is_online"
		. " AND courses.course_id = $cid"
		. " ORDER BY ques_no, answ_value"
		;

	$survey_res = mysql_query($survey_sql);

	if(!$survey_res) {
		die("Database error (survey): " . mysql_error());
	}

	while($row = mysql_fetch_assoc($survey_res)) {
		$ques_no = $row['ques_no'];
		$ques_txt = $row['ques_txt'];
		$answ_txt = $row['answ_txt'];
		$answ_value = $row['answ_value'];

		if(!is_array($questions[$ques_no])) {
			$questions[$ques_no] = array();
		}

		if(!is_array($questions[$ques_no]['answers'])) {
			$questions[$ques_no]['answers'] = array();
		}

		$questions[$ques_no]['question'] = $ques_txt;
		$questions[$ques_no]['answers'][$answ_value] = $answ_txt;
	}

	/* get info about the course */
	$course_sql = ""
		. "SELECT course_crn, course_name, instr_lname, instr_fname"
		. " FROM courses, course_instructor, instructors"
		. " WHERE courses.course_id = course_instructor.course_id"
		. " AND course_instructor.instr_id = instructors.instr_id"
		. " AND courses.course_id = $cid"
		;

	$course_res = mysql_query($course_sql);

	if(!$course_res) {
		die("Database error (course info): " . mysql_error());
	}

	$instructors = '';

	while($row = mysql_fetch_assoc($course_res)) {
		$course_crn = $row['course_crn'];
		$course_name = $row['course_name'];
		$instr_lname = $row['instr_lname'];
		$instr_fname = $row['instr_fname'];

		if($instr_names != '') {
			$instructors .= "; ";
		}

		$instructors .= $instr_lname . ", " . $instr_fname;
	}

	$thisdoc = $_SERVER['PHP_SELF'];

	include('inc/head.php');

	print <<<ENDCI
<div id='head'>
	<table align='center'>
	<tr><td align='center'>
	<img src='img/seal.gif'/>
	</td><td align='left'>

	<p><strong>Georgia Southwestern State University</strong></p>
	<p><strong>Teaching Evaluation Survey</strong></p>
	</td></tr>
	</table>
</div>

<div id='content' align='left'>
<p>
<b>Instructor(s):</b> $instructors
<br/><b>Course Name:</b> $course_name
<br/><b>Course CRN:</b> $course_crn
</p>

<form method='post' action='$thisdoc'>

ENDCI;

	/* print the form */
	foreach($questions as $ques_no => $question) {
		$ques_txt = $question['question'];
		$input_name = 'q' . $ques_no;

		print "<p>\n<b>$ques_no) $ques_txt</b>\n";

		print "<br/>\n<select name='$input_name'>\n";
		print "<option value='0'>No Response</option>\n";

		/* loop through answers array */
		foreach($question['answers'] as $value => $answ_txt) {
			print "<option value='$value'>$answ_txt</option>\n";
		}

		print "</select>\n</p>";
	}

	print <<<ENDLB

<p>
<b>Comments</b>
<br/>
<textarea name='comments' cols='70' rows='10'>
</textarea>
</p>

<input type='hidden' name='check' value='1'/>

<input type='submit' value='Submit'/>
<input type='reset' value='Clear'/>

</form>

</div>

ENDLB;

	include('inc/foot.php');

	print '<pre>';
	print_r($_COOKIE);
	print '</pre>';
}
?>