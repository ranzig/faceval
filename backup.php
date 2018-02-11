<?php
include('inc/getperms.php');
include('inc/functions.php');

if($highest_perm < 3) {
	print_error(array("You don't have permission to be here!"));
	die;
}

if($_POST['check'] == 1) {
	process_form();
} else {
	print_form();
}

function process_form() {
	$comment = $_POST['comment'];
	if($comment == '') { $comment = 'Manual backup'; }

	if(!backup_db($comment)) {
		print_error(array("Backup failed!"));
		die;
	}

	include('inc/insidehead.php');

	print <<<RESULTS

<div id='content'>

<h1>Success!</h1>

<p>
The database has been backed up.  That was easy, wasn't it?
</p>

</div>

RESULTS;

	include('inc/insidefoot.php');
}

function print_form() {
	include('inc/insidehead.php');

	$thisdoc = $_SERVER['PHP_SELF'];

	print <<<FORM

<div id='content'>

<p>
<form action='$thisdoc' method='post'>
<input type='hidden' name='check' value='1'/>
Backup comment: <input name='comment'/>
<input type='submit' value='Backup'/>
</form>
</p>

</div>

FORM;

	include('inc/insidefoot.php');
}
?>