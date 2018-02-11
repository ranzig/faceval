<?php
$permissions = array();
$highest_perm = 0;

if($_COOKIE['instr_id'] != '') {
	$instr_id = $_COOKIE['instr_id'];

	include_once('inc/mysql.php');

	$permsql = ""
		. "SELECT *"
		. " FROM permissions"
		. " WHERE instr_id like '$instr_id'"
		. " ORDER BY perm_level DESC";
		;

	$perm_result = mysql_query($permsql);

	if(!$perm_result) {
		die("Database Error (permissions)");
	}

	while($record = mysql_fetch_assoc($perm_result)) {
		$perm_dept_id = $record['dept_id'];
		$perm_dept_lvl = $record['perm_level'];

		$permhash = 'dept' . $perm_dept_id;

		if($permissions[$permhash] < $perm_dept_lvl) {
			$permissions[$permhash] = $perm_dept_lvl;
		}

		if($record['perm_level'] > $highest_perm) {
			$highest_perm = $record['perm_level'];
		}
	}
} else {
	header("Location:./");
}
?>