<?php
include('inc/getperms.php');
include('inc/functions.php');

if($highest_perm < 3) {
	print_error(array("You don't have permission to be here."));
	die;
}

if(!backup_db("Manual backup")){
	print_error(array("Backup failed!"));
} else {
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
?>