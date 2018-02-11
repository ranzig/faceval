<?php

if($_POST['check'] == 1) {
	process_form();
} else {
	print_form(array());
}

function process_form() {
	$gswid = $_POST['gswid'];
	$crn = $_POST['crn'];
	$cid = "";
	$errors = array();

	$gswid = preg_replace('/[^0-9]/', '', $gswid);
	$crn = preg_replace('/[^0-9]/', '', $crn);

	if(strlen($gswid) != 9) {
		$error = "Your GSW ID# should be exactly 9 digits long.";
		array_push($errors, $error);
	}

	if($gswid == '') {
		$gswid = 0;
	}

	if(count($errors) < 1 ) {
		$timestamp = time();
		$lc_sql = ""
			. 'SELECT *'
			. ' FROM survey_login, courses'
			. ' WHERE survey_login.course_id = courses.course_id'
			. " AND gswid = $gswid"
			. " AND course_crn like '$crn'"
			. ' AND survey_complete = 0'
			. ' AND ('
			. " cutoff >= $timestamp"
			. ' OR'
			. ' cutoff = 0'
			. ' )'
			;

		include_once('inc/mysql.php');

		$result = mysql_query($lc_sql);

		if(!$result) {
			die("Database error (validate student): " . mysql_error());
		}

		if(mysql_numrows($result) < 1) {
			$error = ""
				. "This ID# and CRN could not be validated.  There are several"
				. " reasons this could happen:\n<ul>"
				. "\n<li>There's a typo in either the ID# or the CRN</li>"
				. "\n<li>You've already submitted your survey for this class</li>"
				. "\n<li>Time has expired to submit a survey for this class</li>"
				. "\n<li>You're not registered as a student in this class</li>"
				. "\n<li>This class is not set up for online surveys.  If"
				. " this is the case, you should recieve a hard-copy (paper)"
				. " survey during class near the end of the semester.</li>"
				. "\n</ul>"
				;

			array_push($errors, $error);
		} else {
			$row = mysql_fetch_assoc($result);
			$cid = $row['course_id'];
		}
	}

	if(count($errors) > 0) {
		print_form($errors);
		return;
	} else {
//array_push($errors, "All clear");
//print_form($errors);

		/* set cookies */
		setcookie('gswid', $gswid);
		setcookie('crn', $crn);
		setcookie('cid', $cid);

		/* print forward to survey */
		print <<<FORWARD
<html>
<head>
<title>Redirecting...</title>
<meta http-equiv="refresh" content="0;surview.php"/>
</head>
<body>

<h1>Redirecting to survey for course number $crn</h1>

<p>
You shouldn't see this page very long, if at all... but if you do, 
<a href='surview.php'>click here</a> to continue on to the survey.
</p>

</body>
</html>

FORWARD;
	}
}

function print_form($errors) {
	include('inc/head.php');

	$thisdoc = $_SERVER['PHP_SELF'];

	if(count($errors) >= 1) {
		$errortxt = ""
			. "\n\t<tr><td align='left' colspan='2'>"
			. "\n<font color='red'>"
			. "\n\t<p><strong>Validation problems:</strong>"
			. "\n\t\t<ul>\n\t\t\t<li>\n\t\t\t\t"
			. join("\n\t\t\t</li>\n\t\t\t<li>\n\t\t\t\t", $errors)
			. "\n\t\t\t</li>\n\t\t</ul>\n\t</p>"
			. "\n</font>"
			. "\n\t</td></tr>"
			;
	} else { $errortxt = ""; }

	if($_POST['gswid'] != '') {
		$gswid = $_POST['gswid'];
	}

	print <<<END
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

<div id='content'>
	<p>
	Please input your 
	<a href='https://rain.gsw.edu/pls/prod7x/bwwkgsid.P_GetSSN' target='_new'>
	GSW ID#</a> and the CRN of the course you are evaluating.  If you don't
	know the course CRN, please contact your instructor.
	</p>

	<p>
	Your GSW ID# is <em>only</em> used to validate you as a student in the
	class, and is not tied to your survey results in any way.  Teaching
	Evaluation Survey results are <em>completely anonymous</em>.  Please 
	note that you will have to come back to this page and validate once 
	for each class you wish to evaluate.
	</p>

	<table id='login'>
	<form method='post' action='$thisdoc'>
	$errortxt<tr>
	<td align='right'>
	GSW ID#:
	</td><td align='left'>
	<input size='10' name='gswid' value='$gswid'/>
	</td>
	</tr>
	<tr>
	<td align='right'>
	Course CRN:
	</td><td align='left'>
	<input size='10' name='crn'/>
	</td>
	</tr>
	<tr>
	<td colspan='2' align='center'>
	Your login information is sent over an encrypted connection<br/>
	<input type='hidden' name='check' value='1'/>
	<input type='submit' value='Submit'/>
	</td>
	</tr>
	</form>
	</table>
</div>

END;

	include('inc/foot.php');
}