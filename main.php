<?php
include('inc/insidehead.php');

print <<<ENDCONTENT

<div id='content'>

<h2>Departments and Schools</h2>
<table class='stats'>
<tr class='tablehead'>
<td>Department</td>
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

/* now get list of departments */
include_once('inc/mysql.php');

$dept_sql = ""
	. "SELECT *"
	. " FROM departments"
	. " WHERE dept_id <> -1"
	. " ORDER BY dept_name"
	;

$dept_result = mysql_query($dept_sql);

if(!$dept_result) {
	die("Database error (dept): " . mysql_error());
}

/* determine class filter */
$oo = $_COOKIE['oo'];

if($oo != 1 && $oo != 2) {
	$oo = 0;
}

if($oo == 1) { $dsa = " AND courses.is_online = 1"; }
else if($oo == 2) { $dsa = " AND courses.is_online = 0"; }
else { $dsa = ""; }

/* now get the data we need */
$dept_count = array();
$dept_questions = array();
$dept_skipped = array();

for($i = 0; $i < 12; $i++) {
	$dept_questions[i] = array();
	$dept_skipped[i] = array();
}

$data_sql = ""
	. "SELECT *"
	. " FROM courses, surveys"
	. " WHERE courses.course_id = surveys.course_id"
	. " AND semester_id = $sid"
	. $dsa
	;

$data_result = mysql_query($data_sql);

if(!$data_result) {
	die("Database error (data): " . mysql_error());
}

/* crunch the numbers */
$univ_ques_totals = array();

while($line = mysql_fetch_assoc($data_result)) {
	$dept_id = $line['dept_id'];
	$hashkey = 'dept' . $dept_id;

	$dept_count[$hashkey] += 1;

	for($i = 0; $i < 17; $i++) {
		$question = 'q' . ($i + 1);
		$answer = $line[$question];

		if($answer <= 0) {
			$dept_skipped[$i][$hashkey] += 1;
		} else {
			$dept_questions[$i][$hashkey] += $answer;
		}

		$univ_ques_totals[$question][$answer] += 1;
	}
}

mysql_free_result($data_result);

/* display the data */
$altcolor = 1;

$uni_avg = array();
$uni_row_count = 0;
$uni_response_count = 0;

while($dept_info = mysql_fetch_assoc($dept_result)) {
	$dept_name = $dept_info['dept_name'];
	$dept_id = $dept_info['dept_id'];

	$hashkey = 'dept' . $dept_id;
	$dept_number = $dept_count[$hashkey];

	if($dept_number != '') {
		if($altcolor % 2 == 0) {
			$trclass = "ac";
		} else {
			$trclass = "nc";
		}
		$altcolor += 1;

		$uni_row_count += 1;
		$uni_response_count += $dept_number;

		$total = 0;

		$query = $_SERVER['QUERY_STRING'];

		if(($permissions[$hashkey] >= 2) || ($permissions['dept-1'] >= 2)) {
			if($query == '') {
				$row_txt = "<a href='department.php?did=$dept_id'>$dept_name</a>";
			} else {
				$row_txt = "<a href='department.php?$query&did=$dept_id'>$dept_name</a>";
			}
		} else {
			$row_txt = $dept_name;
		}

		print "<tr class='$trclass'>\n";
		print "<td>$row_txt</td>\n";
		$number = $dept_count[$hashkey];
		print "<td class='rightc'>" . $number . "</td>\n";

		for($i = 0; $i < 12; $i++) {
			if($dept_count[$hashkey] - $dept_skipped[$i][$hashkey] != 0) {
				$num = $dept_questions[$i][$hashkey];
				$den = $dept_count[$hashkey] - $dept_skipped[$i][$hashkey];
				$number = $num / $den;

				$total += $number;

				$uni_avg[$i]['total'] += $number;
				$uni_ave[$i]['skipped'] += $dept_skipped[$i][$hashkey];

				print "<td class='rightc'>" . round($number, 2) . "</td>\n";
			} else {
				print "<td class='rightc'>0 </td>\n";
			}
		}

		$number = $total / 12;
		print "<td class='rightc'><strong>" . round($number, 2) . "</strong></td>\n";

		print "</tr>";
	} else {
		// commented out since we don't want to print rows with no results
		/*
		print "<tr class='$trclass'>\n<td>" . $dept_name . "</td>\n<td class='rightc'>0</td>\n";
		for($i=0; $i < 12; $i++) {
			print "<td class='rightc'>0</td>\n";
		}
		print "<td class='rightc'>0</td>\n";
		*/
	}
}

mysql_free_result($dept_result);

/*
print <<<ENDCONTENT1

<tr class='lastrow'>
<td>University Averages</td>

ENDCONTENT1;

// university averages row 
print "<td class='rightc'>$uni_response_count</td>\n";

$total = 0;

for($i = 0; $i < 12; $i++) {
	if($uni_row_count == 0) {
		$number = 0;
	} else {
		$number = $uni_avg[$i]['total'] / ($uni_row_count - $uni_avg[$i]['skipped']);
	}

	$total += $number;

	print "<td class='rightc'>" . round($number, 2) . "</td>\n";
}

$uni_total_ave = $total / 12;

print "<td class='rightc'><strong>" . round($uni_total_ave, 2) . "</strong></td>\n";

print "</tr>\n"
*/

include_once('inc/functions.php');
print_univ_avg($sid, $oo, 1);

print "</table>";

/* University overall info */
print <<<ENDCONTENT2


<h2>University Overall</h2>
<h3>Total responses: $uni_response_count</h3>

<table class='stats'>
<tr class='tablehead'>
<td>Question</td>
<td>Superior</td>
<td>Above Average</td>
<td>Average</td>
<td>Poor</td>
<td>Inadequate</td>
<td>No Response</td>
<td>Univ. Average</td>
</tr>

ENDCONTENT2;

$altcolor = 0;

for($i = 0; $i < 12; $i++) {
	if($altcolor % 2 == 0) {
		$trclass = "ac";
	} else {
		$trclass = "nc";
	}
	$altcolor += 1;

	$q_num = $i + 1;

	print "<tr class='$trclass'>\n<td class='cenc'>$q_num</td>\n";

	for($j = 0; $j < 6; $j++) {
		$question = 'q' . ($q_num);
		$answer = ($j + 1) % 6;

		$number = $univ_ques_totals[$question][$answer];

		if($number == '') { $number = 0; }

		print "<td class='cenc'>$number</td>\n";
	}

	if($uni_row_count == 0) {
		$number = 0;
	} else {
		$number = $uni_avg[$i]['total'] / ($uni_row_count - $uni_avg[$i]['skipped']);
	}

	print "<td class='cenc'>" . round($number, 2) . "</td>\n";

	print "</tr>\n";
}

print "<tr class='lastrow'>\n<td class='cenc'>Overall</td>\n";

for($i = 0; $i < 6; $i++) {
	$answer = ($i + 1) % 6;

	$number = 0;

	for($j = 0; $j < 12; $j++) {
		$question = 'q' . ($j + 1);

		$number += $univ_ques_totals[$question][$answer];
	}

	print "<td class='cenc'>$number</td>\n";
}

print "<td class='cenc'>$uni_response_count</td>\n";

print "</tr>\n";
print "</table>\n\n";

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
		if($univ_ques_totals[$question][$j] == 0) {
			$lastquestions[$i]['data'][$j] == 0;
		} else {
			$lastquestions[$i]['data'][$j] = $univ_ques_totals[$question][$j];
		}
	}
}

/* University overall responses to questions 13-17 */

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
			if($total == 0) {
				$percent = 0;
			} else {
				$percent = ($count / $total) * 100;
			}

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

print "</div>\n";

include('inc/insidefoot.php');
?>
