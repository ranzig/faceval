<?php
include('inc/getperms.php');
include('inc/functions.php');

if($highest_perm < 3) {
	print_error(array("You don't have permission to be here."));

	die;
}

if($_POST['check'] == 1) {
	process_form();
} else {
	print_form();
}

function process_form() {
	if($_POST['backup'] == 'true') {
		if(!backup_db("before a restore")) {
			print_error(array("Backup failed"));
			die;
		}
	}

	$timestamp = preg_replace('/[^0-9]/', '', $_POST['timestamp']);

	if(!restore_db($timestamp)) {
		print_error(array("Restore failed!"));
	}

	$current = date('M j, Y g:i:s a', $timestamp);

	include('inc/insidehead.php');

	print <<<RESULTS

<div id='content'>

<h1>Success!</h1>

<p>
The database has been rolled back to its status as of <strong>$current</strong>.
</p>

</div>

RESULTS;

	include('inc/insidefoot.php');
}

function print_form() {
	/* get info from backups db */
	include_once('inc/mysql.php');

	$bu_sql = ""
		. "SELECT *"
		. " FROM backups"
		;

	$bu_res = mysql_query($bu_sql);

	if(!$bu_res) {
		die("Database error (backup info): " . mysql_error());
	}

	$bucomm = array();

	while($row = mysql_fetch_assoc($bu_res)) {
		$timestamp = $row['timestamp'];
		$comment = $row['comment'];

		$bucomm[$timestamp] = $comment;
	}

	$basedir = "/opt/gsw/faceval";
	$budir = "$basedir/backups";
	$optiontext = "";
	$thisdoc = $_SERVER['PHP_SELF'];

	foreach(glob("$budir/faceval_*.sql") as $bufile) {
		$timestamp = preg_replace("/.*faceval_/", '', $bufile);
		$timestamp = preg_replace('/\.sql/', '', $timestamp);

		$text = date('M j, Y g:i:s a', $timestamp);

		if($bucomm[$timestamp] != '') {
			$comment = $bucomm[$timestamp];

			$text .= " -- $comment";
		}

		$optiontext .= "\n\t<option value='$timestamp'>$text</option>";
	}

	include('inc/insidehead.php');

	print <<<FORMPRINT

<div id='content'>

<h1>Database rollback</h1>

<p>
From here you can roll the database back to a specific date.  Select the date you want to roll back to and click "Roll Back".
</p>

<p>
<form method='post' action='$thisdoc'>
<input type='hidden' name='check' value='1'/>
<select name='timestamp'>$optiontext
</select><br/>
<input type='checkbox' name='backup' value='true' checked='true'/>
Backup before rollback<br/>
<input type='submit' value='Roll Back'/>
</form>
</p>

</div>

FORMPRINT;

	include('inc/insidefoot.php');
}
?>