<?php
include('inc/functions.php');
include('inc/getperms.php');

if($highest_perm < 3) {
	print_error(array("You're not an administrator"));

	die;
}

include('inc/insidehead.php');

print <<<ENDCONTENT

<div id='content'>

<h2>System tasks</h2>

<p><a href='upload.php'>Upload new data</a></p>
<p><a href='backup.php'>Backup database</a></p>
<p><a href='rollback.php'>Roll back database</a></p>
<p><a href='masq.php'>Masquerade as a user</a></p>
<p><a href='password.php'>Reset user password</a></p>
<p><a href='permissions.php'>Manage permissions</a></p>
<p><a href='prepare.php'>Prepare online surveys</a></p>

</div>

ENDCONTENT;

include('inc/insidefoot.php');
?>