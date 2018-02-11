<?php
include('inc/insidehead.php');

print <<<ENDCONTENT

<div id='content'>

ENDCONTENT;

/* get id for instructor in question */
$instr = $_GET['iid'];

if($instr == "") {
	$instr = $_COOKIE['instr_id'];
}

/* get semester id */
$sid = $_GET['sid'];

if($sid == '') {
	include_once('inc/mysql.php');

	$sidsql = ""
		. "SELECT semester_id"
		. " FROM semesters"
		. " WHERE active = 1"
		. " ORDER BY semester_id DESC"
		. " LIMIT 1"
		;

	$sid_result = mysql_query($sidsql);

	if(!$sid_result) {
		die("Database Error (sid): " . mysql_error());
	}

	$sid_assoc = mysql_fetch_assoc($sid_result);

	$sid = $sid_assoc['semester_id'];

	mysql_free_result($sid_result);
}

/* determine class filter */
$oo = $_COOKIE['oo'];

if($oo != 1 && $oo != 2) {
	$oo = 0;
}

if($oo == 1) { $dsa = " AND courses.is_online = 1"; }
else if($oo == 2) { $dsa = " AND courses.is_online = 0"; }
else { $dsa = ""; }

/* get data for first part of table */
include_once('inc/mysql.php');

$course_sql = ""
	. "SELECT *"
	. " FROM instructors, course_instructor, courses, surveys"
	. " WHERE instructors.instr_id = course_instructor.instr_id"
	. " AND course_instructor.course_id = courses.course_id"
	. " AND course_instructor.course_id = surveys.course_id"
	. " AND instructors.instr_id LIKE '$instr'"
	. " AND courses.semester_id = $sid"
	. $dsa
	;

$course_result = mysql_query($course_sql);

if(!$course_result) {
	die("Database Error (course): " . mysql_error());
}

if(mysql_num_rows($course_result) == 0) {
	print "<h4>No evaluations submitted for this instructor</h4>";
} else {
	/* crunch data */
	$instr_total = mysql_num_rows($course_result);
	$instr_data = array();
	$instr_data['departments'] = array();

	while($row = mysql_fetch_assoc($course_result)) {
		$coursetxt = $row['course_name'] . " (CRN " . $row['course_crn'] . ")";
		$coursekey = 'course' . $row['course_id'];
		if($row['is_online'] == 1) {
			$coursetxt .= " (online)";
		}
		$survey_dept = 'dept' . $row['dept_id'];
		$instr_data['name'] = $row['instr_lname'] . ", " . $row['instr_fname'];
		$instr_data['courses'][$coursekey]['name'] = $coursetxt;
		$instr_data['courses'][$coursekey]['cid'] = $row['course_id'];
		$instr_data['courses'][$coursekey]['count'] += 1;
		$instr_data['count'] += 1;

		for($i = 0; $i < 17; $i++) {
			$qkey = 'q' . ($i + 1);
			$akey = $row[$qkey];

			if($akey == 0) {
				$instr_data['courses'][$coursekey]['skipped'][$qkey] += 1;
				$instr_data['skipped'][$qkey] += 1;
			} else {
				$instr_data['courses'][$coursekey][$qkey] += $akey;
				$instr_data[$qkey] += $akey;
			}

			$instr_data['answers'][$qkey][$akey] += 1;
		}

		if(array_search($survey_dept, $instr_data['departments']) === false) {
			array_push($instr_data['departments'], $survey_dept);
		}
	}

	$classcount = count($instr_data['courses']);

	print "<p><strong>" . $instr_data['name'] . "</strong> taught <strong>$classcount</strong> classes:</p>\n";

	/* now display first part of table */

	print <<<TABLE1START

<table class='stats'>
<tr class='tablehead'>
<td>Course</td>
<td>Response</td>
<td>R1</td>
<td>R2</td>
<td>R3</td>
<td>R4</td>
<td>R5</td>
<td>R6</td>
<td>R7</td>
<td>R8</td>
<td>R9</td>
<td>R10</td>
<td>R11</td>
<td>R12</td>
<td>Avg</td>
</tr>

TABLE1START;

	$cids = array();

	$altcolor = 0;

	foreach($instr_data['courses'] as $courseinfo) {
		$coursename = $courseinfo['name'];
		$cid = $courseinfo['cid'];

		array_push($cids, $cid);

		if($altcolor % 2 == 0) {
			$trclass = 'nc';
		} else {
			$trclass = 'ac';
		}
		$altcolor++;

		print "<tr class='$trclass'>\n";
		print "<td><a href='course.php?cid=$cid&sid=$sid'>" . $coursename . "</a></td>\n";
		print "<td class='rightc'>" . $courseinfo['count'] . "</td>\n";

		$total = 0;

		for($i = 1; $i <= 12; $i++) {
			$qkey = 'q' . $i;
			$qtotal = $courseinfo[$qkey];
			$count = $courseinfo['count'];
			$skipped = $courseinfo['skipped'][$qkey];
			if($count == $skipped) {
				$number = 0;
			} else {
				$number = $qtotal / ($count - $skipped);
			}

			$total += $number;

			print "<td class='rightc'>" . round($number, 2) . "</td>\n";
		}

		$average = $total / 12;

		print "<td class='rightc'><strong>" . round($average, 2) . "</strong></td>\n";

		print "</tr>\n";
	}

	print "<tr class='lastrow'>\n<td>Instructor Average</td>\n";
	$count = $instr_data['count'];
	print "<td class='rightc'>$count</td>\n";

	$total = 0;

	for($i = 1; $i <= 12; $i++) {
		$qkey = 'q' . $i;
		$qtotal = $instr_data[$qkey];
		$skipped = $instr_data['skipped'][$qkey];
		if($count == $skipped) {
			$number = 0;
		} else {
			$number = $qtotal / ($count - $skipped);
		}

		$total += $number;

		print "<td class='rightc'>" . round($number, 2) . "</td>\n";
	}

	$average = $total / 12;

	print "<td class='rightc'><strong>" . round($average, 2) . "</strong></td>\n";

	print "</tr>";

	/* now department average(s) and university average */

	/* get data */
	$generalsql = ""
		. "SELECT *"
		. " FROM courses, surveys, departments"
		. " WHERE courses.course_id = surveys.course_id"
		. " AND courses.dept_id = departments.dept_id"
		. " AND semester_id = $sid"
		. $dsa
		;

	$generalresult = mysql_query($generalsql);

	if(!$generalresult) {
		die("Database error (general): " . mysql_error());
	}

	/* Crunch data */
	$geninfo = array();

	while($row = mysql_fetch_assoc($generalresult)) {
		$hashkey = 'dept' . $row['dept_id'];

		$geninfo[$hashkey]['name'] = $row['dept_name'];
		$geninfo[$hashkey]['count'] += 1;
		$geninfo['univ']['count'] += 1;

		for($i = 1; $i <= 17; $i++) {
			$qkey = 'q' . $i;

			if($row[$qkey] == 0) {
				$geninfo[$hashkey]['skipped'][$qkey] += 1;
				$geninfo['univ']['skipped'][$qkey] += 1;
			} else {
				$geninfo[$hashkey][$qkey] += $row[$qkey];
				$geninfo['univ'][$qkey] += $row[$qkey];
			}
		}
	}

	/* display data */

	foreach($instr_data['departments'] as $hashkey) {
		$name = $geninfo[$hashkey]['name'];
		$count = $geninfo[$hashkey]['count'];
		$total = 0;

		print "<tr class='lastrow'>\n<td><em>$name average</em></td>\n";
		print "<td class='rightc'>$count</td>\n";

		for($i = 1; $i <= 12; $i++) {
			$qkey = 'q' . $i;
			$q_sum = $geninfo[$hashkey][$qkey];
			$q_skipped = $geninfo[$hashkey]['skipped'][$qkey];

			if($count == $q_skipped) {
				$number = 0;
			} else {
				$number = $q_sum / ($count - $q_skipped);
			}

			$total += $number;

			print "<td class='rightc'>" . round($number, 2) . "</td>\n";
		}

		$average = $total / 12;

		print "<td class='righc'><strong>" . round($average, 2) . "</strong></td>\n";

		print "</tr>\n";
	}

	$count = $geninfo['univ']['count'];

	print "<tr class='lastrow'>\n<td><em>University average</em></td>\n";
	print "<td class='rightc'>$count</td>\n";

	$total = 0;

	for($i = 1; $i <= 12; $i++) {
		$qkey = 'q' . $i;
		$q_sum = $geninfo['univ'][$qkey];
		$q_skipped = $geninfo['univ']['skipped'][$qkey];

		if($count == $q_skipped) {
			$number = 0;
		} else {
			$number = $q_sum / ($count - $q_skipped);
		}

		$total += $number;

		print "<td class='rightc'>" . round($number, 2) . "</td>\n";
	}

	$average = $total / 12;

	print "<td class='rightc'><strong>" . round($average, 2) . "</strong></td>\n";
	print "</tr>\n";

	print "</table>\n";

	/* questions 13-17 */
	$lastquestions = array();

	$lastquestions[0]['question'] = 'Would you consider taking another course with this instructor?';
	$lastquestions[0]['answers'] = array("Yes", "No", "", "", "", "No Response");
	$lastquestions[0]['data'] = array(0, 0, 0, 0, 0);

	$lastquestions[1]['question'] = "What is your class standing?";
	$lastquestions[1]['answers'] = array("Freshman", "Sophomore", "Junior", "Senior", "Graduate", "No Response");
	$lastquestions[1]['data'] = array(0, 0, 0, 0, 0);

	$lastquestions[2]['question'] = "What is  your estimated overal GPA for all post-high school study completed for credit?";
	$lastquestions[2]['answers'] = array("3 - 4.00", "2 - 2.99", "1 - 1.99", "0 - 0.99", "No GPA", "No Response");
	$lastquestions[2]['data'] = array(0, 0, 0, 0, 0);

	$lastquestions[3]['question'] = "What is your current grade in this course?";
	$lastquestions[3]['answers'] = array("A", "B", "C", "D", "F", "No Response");
	$lastquestions[3]['data'] = array(0, 0, 0, 0, 0);

	$lastquestions[4]['question'] = "How many class meetings have you missed in this course?";
	$lastquestions[4]['answers'] = array("0 - 2", "3 - 5", "6 - 8", "8+", "", "No Response");
	$lastquestions[4]['data'] = array(0, 0, 0, 0, 0);

	for($i = 0; $i < 5; $i++) {
		$question = 'q' . ($i + 13);

		for($j = 0; $j < 6; $j++) {
			$lastquestions[$i]['data'][$j] = $instr_data['answers'][$question][$j];
		}
	}

	print "<table>\n<tr>\n<td width='68%'>&nbsp</td>\n<td width=32%>&nbsp</td>\n</tr>\n";

	for($i = 0; $i < 5; $i++) {
		$qnum = $i + 13;
		$qtext = $lastquestions[$i]['question'];
		$total = array_sum($lastquestions[$i]['data']);

		print "<tr>\n<td>$qnum) $qtext</td>\n";

		print "<td>";

		print "\n\t<table class='small'>\n";

		for($j = 0; $j < 6; $j++) {
			$dataindex = ($j + 1) % 6;
			$answer = $lastquestions[$i]['answers'][$j];

			if($answer != "") {
				$count = $lastquestions[$i]['data'][$dataindex];
				$percent = ($count / $total) * 100;

				print "\t<tr>\n\t<td class='rightc'>$answer</td>\n";
				print "\t<td><img src='img/graphbar.php?c=$dataindex&h=$percent&r=1'/>";
				print " (" . round($percent, 2) . "%)";
				print "</td>\n\t</tr>\n";
			}
		}

		print "\t</table>";
		print "\n</tr>\n";
	}

	print "</table>\n";

	/* now get comments and display them */
	$commsql = ""
		. "SELECT *"
		. " FROM comments, courses"
		. " WHERE comments.course_id = courses.course_id "
		. " AND ( comments.course_id = "
		. implode(" OR comments.course_id = ", $cids)
		. ")"
		;

	$comresult = mysql_query($commsql);

	if(!$comresult) {
		die("Database error (comments): " . mysql_error());
	}

	if(mysql_num_rows($comresult) > 0) {
		$cominfo = array();

		while($row = mysql_fetch_assoc($comresult)) {
			$hashkey = "course" . $row['course_id'];
			$cname = $row['course_name'];
			$ccrn = $row['course_crn'];
			$cominfo[$hashkey]['name'] = "$cname (CRN #$ccrn)";
			if(!is_array($cominfo[$hashkey]['comments'])) {
				$cominfo[$hashkey]['comments'] = array();
			}
			array_push($cominfo[$hashkey]['comments'], nl2br($row['comment']));
		}

		print "\n<h1>Comments:</h1>\n";

		foreach($cominfo as $course) {
			print "\n<h2>" . $course['name'] . ":</h2>\n";

			print ""
				. "\n<p>\n"
				. join("</p>\n<hr width='50%'>\n<p>", $course['comments'])
				. "\n</p>\n"
				;
		}
	} /* end of no comments */
} /* end of no rows */
print <<<ENDCONTENT1

</div>

ENDCONTENT1;

include('inc/insidefoot.php');
?>