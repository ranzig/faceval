<?php

$basedir = "/opt/gsw/faceval";

function print_univ_avg($sid, $oo, $colspan) {
	if($sid == '') {
		return;
	}

	if($colspan == '') {
		$colspan = 2;
	}

	$data_sql = ''
		. 'SELECT *'
		. ' FROM courses, surveys'
		. ' WHERE courses.course_id = surveys.course_id'
		. " AND semester_id = $sid"
		;

	if($oo == 1) {
		$data_sql .= ' AND courses.is_online = 1';
	} else if ($oo == 1) {
		$data_sql .= ' AND courses.is_online = 0';
	}

	include('mysql.php');

	$data_res = mysql_query($data_sql);

	if(!$data_res) {
		return;
	}

	$univ_count = mysql_num_rows($data_res);

	print <<<STARTROW

<tr class='lastrow'>
<td class='cenc' colspan='$colspan'><em>University Average</em></td>
<td class='rightc'>$univ_count</td>

STARTROW;

	$univsum_data = array();

	while($row = mysql_fetch_assoc($data_res)) {
		for($i = 1; $i <= 12; $i++) {
			$qkey = 'q' . $i;
			$answer = $row[$qkey];

			if($answer <= 0) {
				$univsum_data[$qkey]['skipped'] += 1;

				$answer = 0;
			}

			$univsum_data[$qkey]['total'] += $answer;
		}
	}

	$univsum_total = 0;
	$univsum_count = 0;

	for($i = 1; $i <= 12; $i++) {
		$qkey = 'q' . $i;
		$total = $univsum_data[$qkey]['total'];
		$count = $univ_count - $univsum_data[$qkey]['skipped'];

		if($count > 0) {
			$average = $total / $count;
		} else {
			$average = 0;
		}

		$univsum_total += $average;

		print "<td class='rightc'>" . round($average, 2) . "</td>\n";
	}

	$univsum_avg = ($univsum_total / 12);

	print "<td class='rightc'><strong>" . round($univsum_avg, 2) . "</strong></td>\n";
	print "</tr>\n";
}

function print_error($errors) {
	include('insidehead.php');

	print "<h4>I'm sorry, the following error(s) occurred:</h4>\n";
	print "<ul>\n";

	foreach($errors as $error) {
		print "<li>$error</li>\n";
	}
	print "</ul>\n";

	print "<p>\nClick the back button on your browser and try again.\n</p>\n";

	include('insidefoot.php');

	return;
}

function print_error_out($errors) {
	include('head.php');

	print <<<ENDIT
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

ENDIT;

	print "<h4>I'm sorry, the following error(s) occurred:</h4>\n";
	print "<ul>\n";

	foreach($errors as $error) {
		print "<li>$error</li>\n";
	}
	print "</ul>\n";

	print "<p>\nClick the back button on your browser and try again.\n</p>\n";
	print "</div>\n";

	include('foot.php');

	return;
}

function backup_db($comment = '') {
	$timestamp = time();
	$budir = $GLOBALS['basedir'] . "/backups";
	$outfile = "$budir/faceval_$timestamp.sql";

	if(!is_dir($budir)) {
		mkdir($budir, 0700);
	}

	include_once('mysql.php');

	global $db_user, $db_pass, $db_name;

	$command = "mysqldump -u $db_user -p$db_pass $db_name > $outfile";

	$return = system($command);

	if($return === false) {
		return false;
	} else {
		if($comment != '') {
			$comment = preg_replace('/[^:0-9a-zA-Z ]/', '', $comment);
			$backsql = ""
				. "INSERT INTO backups"
				. " (timestamp, comment)"
				. " VALUES "
				. "($timestamp, '$comment')"
				;
		} else {
			$backsql = ""
				. "INSERT INTO backups"
				. " (timestamp)"
				. " VALUES "
				. "($timestamp)"
				;
		}

		$backres = mysql_query($backsql);

		if(!$backres) {
			die("Database error (backup log): " . mysql_error());
		}
		return true;
	}
}

function restore_db($timestamp) {
	$budir = $GLOBALS['basedir'] . "/backups";
	$infile = "$budir/faceval_$timestamp.sql";

	include_once('mysql.php');

	global $db_user, $db_pass, $db_name;

	$return = system("mysql -u $db_user -p$db_pass $db_name < $infile");

	if($return === false) {
		return false;
	} else {
		return true;
	}
}

function unzip($zipfile, $destination) {
	if(!is_dir($destionation)) {
		mkdir($destination, 0700);
	}

	$zipres = zip_open($zipfile);

	while($file = zip_read($zipres)) {
		$name = $destination . '/' . strtolower(zip_entry_name($file));

		touch($name);

		$file_res = fopen($name, 'w+');

		fwrite($file_res, zip_entry_read($file));
		fclose($file_res);
	}
}

function check_email_address($email) {
	// First, we check that there's one @ symbol, 
	// and that the lengths are right.
	if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
		// Email invalid because wrong number of characters 
		// in one section or wrong number of @ symbols.
		return false;
	}

	// Split it into sections to make life easier
	$email_array = explode("@", $email);
	$local_array = explode(".", $email_array[0]);
	for ($i = 0; $i < sizeof($local_array); $i++) {
		if(
			!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])
		) {
			return false;
		}
	}

	// Check if domain is IP. If not, 
	// it should be valid domain name
	if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
		$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2) {
				return false; // Not enough parts to domain
		}
		for ($i = 0; $i < sizeof($domain_array); $i++) {
			if(
				!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])
			) {
				return false;
			}
		}
	}

	return true;
}
?>