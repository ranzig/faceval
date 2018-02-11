<?php
include('inc/insidehead.php');

print <<<ENDCONTENT

<div id='content'>

ENDCONTENT;

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

/* get department id */
$did = $_GET['did'];
if($did == '') {
	print "<h4>Bad department ID</h4>\n";
} else {
	/* check permission level */
	$dept_perm = $permissions['dept' . $did];
	if($permissions['dept-1'] > $dept_perm) {
		$dept_perm = $permissions['dept-1'];
	}
	if($dept_perm >= 2) {
		/* get the data */
		$datasql = ""
			. "SELECT *"
			. " FROM instructors, course_instructor, courses, surveys"
			. " WHERE instructors.instr_id = course_instructor.instr_id"
			. " AND course_instructor.course_id = courses.course_id"
			. " AND courses.course_id = surveys.course_id"
			. " AND courses.semester_id = $sid"
			. $dsa
			. " ORDER BY instr_lname ASC"
			;

		include_once('inc/mysql.php');

		$dataresult = mysql_query($datasql);

		if(!$dataresult) {
			die("Database error (data): " . mysql_error());
		}

		/* crunch the numbers */
		$dept_info = array();
		while($row = mysql_fetch_assoc($dataresult)) {
			$dkey = $row['dept_id'];
			$ckey = $row['course_id'];
			$dept_info[$dkey]['courses'][$ckey]['name'] = $row['course_name'];
			$dept_info[$dkey]['courses'][$ckey]['name'] .= " (CRN " . $row['course_crn'] . ")";

			if($row['is_online'] == 1) {
				$dept_info[$dkey]['courses'][$ckey]['name'] .= " (online)";
			}

			$instr_id = $row['instr_id'];
			$instr_name = $row['instr_lname'] . ", " . $row['instr_fname'];

			$dept_info[$dkey]['courses'][$ckey]['instructors'][$instr_id] = $instr_name;

			for($i = 1; $i <= 17; $i++) {
				$qkey = 'q' . $i;
				$answer = $row[$qkey];

				if($answer == 0) {
					$dept_info[$dkey]['courses'][$ckey][$qkey]['skipped'] += 1;
					$dept_info[$dkey][$qkey]['skipped'] += 1;
					$dept_info['univ'][$qkey]['skipped'] += 1;
				} else {
					$dept_info[$dkey]['courses'][$ckey][$qkey]['total'] += $answer;
					$dept_info[$dkey][$qkey]['total'] += $answer;
					$dept_info['univ'][$qkey]['total'] += $answer;
				}

				$dept_info[$dkey][$qkey][$answer] += 1;
				if($i <= 12) {
					$dept_info[$dkey]['answers'][$answer] += 1;
				}
			}

			$dept_info['univ']['count'] += 1;
			$dept_info[$dkey]['count'] += 1;
			$dept_info[$dkey]['courses'][$ckey]['count'] += 1;
		}

		print <<<TABLEHEAD
<table class='stats'>
<tr class='tablehead'>
<td>Course</td>
<td>Instructor</td>
<td>#</td>
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

TABLEHEAD;

		$altcolor = 0;

		foreach($dept_info[$did]['courses'] as $cid => $row) {
			if($altcolor % 2 == 0) {
				$trclass = 'nc';
			} else {
				$trclass = 'ac';
			}
			$altcolor++;

			$linktxt = ""
				. "<a href='course.php?cid=$cid&sid=$sid'>"
				. $row['name']
				. "</a>"
				;

			print "<tr class='$trclass'>\n";

			print "<td>" . $linktxt . "</td>\n";
			print "<td>";

			$instructors = array();

			foreach($row['instructors'] as $instr_id => $instr_name) {
				$linktxt = ""
					. "<a href='instructor.php?iid=$instr_id&sid=$sid'>"
					. $instr_name
					. "</a>"
					;
				array_push($instructors, $linktxt);
			}

			print join(", ", $instructors);

			print "</td>\n";

			$c_count = $row['count'];

			print "<td class='rightc'>" . $c_count . "</td>\n";

			$total = 0;

			for($i = 1; $i <= 12; $i++) {
				$qkey = 'q' . $i;
				$skipped = $row[$qkey]['skipped'];
				$sum = $row[$qkey]['total'];

				if($skipped >= $c_count) {
					$number = 0;
				} else {
					$number = $sum / ($c_count - $skipped);
				}

				$total += $number;

				print "<td class='rightc'>" . round($number, 2) . "</td>\n";
			}

			$average = $total / 12;

			print "<td class='rightc'><strong>" . round($average, 2) . "</td>\n";

			print "</tr>\n";
		}

		$dept_count = $dept_info[$did]['count'];

		print "<tr class='lastrow'>\n";
		print "<td class='cenc' colspan='2'><em>Department Average</em></td>\n";
		print "<td class='rightc'>" . $dept_count . "</td>\n";

		$total = 0;

		for($i = 1; $i <= 12; $i++) {
			$qkey = 'q' . $i;
			$skipped = $dept_info[$did][$qkey]['skipped'];
			$sum = $dept_info[$did][$qkey]['total'];

			if($skipped >= $dept_count) {
				$number = 0;
			} else {
				$number = $sum / ($dept_count - $skipped);
			}

			$total += $number;

			print "<td class='rightc'>" . round($number, 2) . "</td>\n";
		}

		$average = $total / 12;

		print "<td class='rightc'><strong>" . round($average, 2) . "</strong></td>\n";

		print "</tr>\n";

		$univ_count = $dept_info['univ']['count'];

		print "<tr class='lastrow'>\n";
		print "<td class='cenc' colspan='2'><em>University Average</em></td>\n";
		print "<td class='rightc'>$univ_count</td>\n";

		$total = 0;

		for($i = 1; $i <= 12; $i++) {
			$qkey = 'q' . $i;
			$skipped = $dept_info['univ'][$qkey]['skipped'];
			$sum = $dept_info['univ'][$qkey]['total'];

			if($skipped >= $univ_count) {
				$number = 0;
			} else {
				$number = $sum / ($univ_count - $skipped);
			}

			$total += $number;

			print "<td class='rightc'>" . round($number, 2) . "</td>\n";
		}

		$average = $total / 12;

		print "<td class='rightc'><strong>" . round($average, 2) . "</td>\n";

		print "</tr>\n";

		print "</table>";

		print "\n<h2>Department Overall</h2>\n";
		print "<h3>$dept_count responses</h3>\n";

		print <<<TABLE2

<table class='stats'>
<tr class='tablehead'>
<td>Question</td>
<td>Superior</td>
<td>Above</td>
<td>Average</td>
<td>Poor</td>
<td>Inadequate</td>
<td>No response</td>
<td>Dept Avg</td>
</tr>

TABLE2;

		$altcolor = 0;
		$avetotal = 0;

		for($i = 1; $i <= 12; $i++) {
			$qkey = 'q' . $i;

			if($altcolor % 2 == 0) {
				$trclass = 'nc';
			} else {
				$trclass = 'ac';
			}
			$altcolor++;

			print "<tr class='$trclass'>\n";
			print "<td class='cenc'>$i</td>\n";

			$total = 0;

			for($j = 0; $j < 6; $j++) {
				$akey = ($j + 1) % 6;
				$number = $dept_info[$did][$qkey][$akey];

				if($number == '') {
					$number = 0;
				} else {}

				print "<td class='cenc'>$number</td>\n";

				$total += $number * $akey;
			}

			$skipped = $dept_info[$did][$qkey][0];

			if($skipped >= $dept_count) {
				$average = 0;
			} else {
				$average = $total / ($dept_count - $skipped);
			}

			print "<td class='cenc'><strong>" . round($average, 2) . "</td>\n";
			$avetotal += $average;

			print "</tr>\n";
		}

		print "<tr class='lastrow'>\n";
		print "<td class='cenc'>Overall</td>\n";

		$total = 0;

		for($i = 0; $i < 6; $i++) {
			$akey = ($i + 1) % 6;

			$number = $dept_info[$did]['answers'][$akey];

			if($number == '') {
				$number = 0;
			}

			$total += $number * $akey;

			print "<td class='cenc'>$number</td>\n";
		}

		$skipped = $dept_info[$did]['answers'][0];

/*
		if($skipped >= $dept_count) {
			$average = 0;
		} else {
			$average = $total / (($dept_count - $skipped) * 12);
		}
*/
		if($avetotal != 0) {
			$average = $avetotal / 12;
		} else {
			$average = 0;
		}

		print "<td class='cenc'><strong>" . round($average, 2) . "</td>\n";

		print "</tr>\n";

		print "</table>\n";

		/* Department averages for questions 13-17 */
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
				$lastquestions[$i]['data'][$j] = $dept_info[$did][$question][$j];
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
	} else {
		print "<h4>You do not have permission to view this department</h4>";
	} /* end permission check */
} /* end dept. ID check */

print <<<ENDCONTENT2

</div>

ENDCONTENT2;

include('inc/insidefoot.php');
?>
