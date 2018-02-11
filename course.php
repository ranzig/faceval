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


/* get the data */
$cid = $_GET['cid'];

if($cid == '') {
	print "<h4>Invalid Course ID</h4>\n";
} else {

	$datasql = ""
		. "SELECT *"
		. " FROM instructors, course_instructor, courses, surveys"
		. " WHERE instructors.instr_id = course_instructor.instr_id"
		. " AND course_instructor.course_id = courses.course_id"
		. " AND courses.course_id = surveys.course_id"
		. " AND courses.course_id = $cid"
		. $dsa
		;

	include_once("inc/mysql.php");

	$dataresult = mysql_query($datasql);

	if(!$dataresult) {
		die("Database error (data): " . mysql_error());
	}

	/* crunch the numbers */
	if(mysql_num_rows($dataresult) <= 0) {
		print "<h4>No data for this course</h4>\n";
	} else {
		$co_data = array();
		while($row = mysql_fetch_assoc($dataresult)) {
			$instr_name = $row['instr_lname'] . ", " . $row['instr_fname'];
			$instr_id = $row['instr_id'];

			$c_data['instructors'][$instr_id] = $instr_name;
			$c_data['name'] = $row['course_name'];
			if($row['is_online'] == 1) {
				$c_data['name'] .= " (online)";
			}
			$c_data['crn'] = $row['course_crn'];

			for($i = 1; $i <= 17; $i++) {
				$qkey = 'q' . $i;
				$answer = $row[$qkey];

				$c_data[$qkey][$answer] += 1;
			}

			$c_data['count'] += 1;
		}

/* fix counts for courses with two instructors -- turns out I don't need this
		if(count($c_data['instructors']) > 1) {
			$instr_count = count($c_data['instructors']);

			for($i = 1; $i <= 17; $i++) {
				$qkey = 'q' . $i;

				foreach($c_data[$qkey] as $answer) {
					$c_data[$qkey][$answer] /= $instr_count;
				}
			}

			$c_data['count'] /= $instr_count;
		} else {}
*/
/* now display the data */
		print "<h2>" . $c_data['name'] . " was taught by ";

		$instr_links = array();
		$instr_count = 0;
		foreach($c_data['instructors'] as $instr_id => $instr_name) {
			$link = "instructor.php?iid=$instr_id&sid=$sid";
			$text = "<a href='$link'>$instr_name</a>";

			array_push($instr_links, $text);
			$instr_count++;
		}

		for($i = 1; $i <= $instr_count; $i++) {
			if($i > 1 && $i < $instr_count) {
				print ", ";
			} else if($i > 1 && $i == $instr_count) {
				print ", and ";
			}

			print $instr_links[$i-1];
		}

		print "</h2>\n";

		$count = $c_data['count'];

		print "<h3>This course was evaluated by <strong>$count</strong> students:</h3>\n";

		print <<<TABLESTART

<table class='stats'>
<tr class='tablehead'>
<td>Question</td>
<td>Superior</td>
<td>Above</td>
<td>Average</td>
<td>Poor</td>
<td>Inadequate</td>
<td>No Response</td>
<td>Average</td>
</tr> 

TABLESTART;

		$altcolor = 0;

		$totals = array();
		$total_average = 0;

		for($i = 1; $i <= 12; $i++) {
			$qkey = 'q' . $i;
			$total = 0;

			if($altcolor % 2 == 0) {
				$trclass="nc";
			} else {
				$trclass="ac";
			}

			$altcolor++;

			print "<tr class='$trclass'>\n";
			print "<td class='cenc'>$i</td>\n";

			for($j = 0; $j < 6; $j++) {
				$index = ($j + 1) % 6;

				$number = $c_data[$qkey][$index];

				if($number == '') {
					$number = 0;
				}

				print "<td class='cenc'>" . $number . "</td>\n";

				$total += $number * $index;
				$totals[$index] += $number;
			}

			if($count == $c_data[$qkey][0]) {
				$average = 0;
			} else {
				$qcount = $count - $c_data[$qkey][0];
				$average = $total / $qcount;
			}

			$total_average += $average;

			print "<td class='cenc'><strong>" . round($average, 2) . "</strong></td>\n";
		}

		print "<tr class='lastrow'>\n<td class='cenc'>Overall</td>\n";

		for($i = 0; $i <6; $i++) {
			$index = ($i + 1) % 6;

			print "<td class='cenc'>" . $totals[$index] . "</td>\n";
		}

		$final = $total_average / 12;

		print "<td class='cenc'><strong>" . round($final, 2) . "</strong></td>\n";
		print "</tr>\n";

		/* now time for questions 13-17 */
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
				$lastquestions[$i]['data'][$j] = $c_data[$question][$j];
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
	} /* no rows */
} /* no cid */

print <<<ENDCONTENT2

</div>

ENDCONTENT2;

include('inc/insidefoot.php');
?><?php
?>