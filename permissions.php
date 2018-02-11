<?php
include('inc/getperms.php');
include('inc/functions.php');

if($highest_perm < 3) {
	print_error(array("You don't have permission to be here"));
	die;
}

if($_POST['check'] == 1) {
	modify_user();
} else if($_POST['check'] == 2) {
	add_user();
} else if($_POST['check'] == 3) {
	new_user();
} else {
	print_form(array());
}

function new_user() {
	$fname = $_POST['fname'];
	$lname = $_POST['lname'];
	$email = $_POST['email'];
	$iid = $_POST['instr_id'];
	$pass = $_POST['pass'];
	$perm = $_POST['plvl'];
	$did = $_POST['did'];

	$errors = array();
	$info = array();

	/* validate stuff */
	if($lname == '') {
		array_push($errors, "Last name is required");
	}

	if($iid == '') {
		array_push($errors, "Instructor ID is required");
	}

	if($pass == '') {
		array_push($errors, "Password is required");
	}

	if($perm == '') {
		array_push($errors, "Permission level is required");
	}

	if($perm == 3) {
		$did = -1;
	}

	if(preg_match("/[^a-zA-Z0-9]/", $iid)) {
		array_push($errors, "Instructor ID can only contain letters and numbers");
	}

	if(($email != '') && (!check_email_address($email))) {
		array_push($errors, "Email address is invalid");
	}

	if($perm == 3 || $perm == -1) {
		$did = -1;

		if($perm == -1) {
			/* remove other permissions */
			$fix_sql = ""
				. "DELETE"
				. " FROM permissions"
				. " WHERE instr_id LIKE '$iid'"
				;

			$fix_res = mysql_query($fix_sql);

			if(!$fix_res) {
				die("Database error (remove permissions): " . mysql_error());
			}
		}
	}

	$pass = md5($pass);
	$lname = mysql_real_escape_string($lname);
	$fname = mysql_real_escape_string($fname);
	$email = mysql_real_escape_string($email);

	$check_sql = ""
		. "SELECT *"
		. " FROM instructors"
		. " WHERE instr_id LIKE '$iid'"
		;

	$check_res = mysql_query($check_sql);

	if(!$check_res) {
		die("Database error (check instructor):" . mysql_error());
	}

	if(mysql_num_rows($check_res) > 0) {
		array_push($errors, "That Instructor ID is already in use");
	}

	if(count($errors) > 0) {
		print_error($errors);
		return;
	}

	/* add to instructor table */

	$user_sql = ""
		. "INSERT INTO instructors"
		. " (instr_id, instr_pass, instr_lname, instr_fname, instr_email)"
		. " VALUES"
		;
	if($email == '') {
		$user_sql .= ''
			. " ('$iid', '$pass', '$lname', '$fname', NULL)"
			;
	} else {
		$user_sql .= ''
			. " ('$iid', '$pass', '$lname', '$fname', '$email')"
			;
	}

	$user_res = mysql_query($user_sql);

	if(!$user_res) {
		die("Database error (add instructor): " . mysql_error());
	} else {
		array_push($info, "User $iid added to instructors");
	}

	/* add permissions */
	$perm_sql = ""
		. "INSERT INTO permissions"
		. " (instr_id, dept_id, perm_level)"
		. " VALUES"
		. " ('$iid', $did, $perm)"
		;

	$perm_res = mysql_query($perm_sql);

	if(!$perm_res) {
		die("Database error (add permission): " . mysql_error());
	} else {
		array_push($info, "User $iid given permission level $perm for department $did");
	}

	print_form($info);
}

function modify_user() {
	$iid = $_POST['instr_id'];
	$plvl = $_POST['plvl'];
	$did = $_POST['dept_id'];

	if($plvl == 3 || $plvl == -1) {
		$did = -1;

		if($plvl == -1) {
			/* remove other permissions */
			$fix_sql = ""
				. "DELETE"
				. " FROM permissions"
				. " WHERE instr_id LIKE '$iid'"
				;

			$fix_res = mysql_query($fix_sql);

			if(!$fix_res) {
				die("Database error (remove permissions): " . mysql_error());
			}
		}
	}

	$check_sql = ""
		. "SELECT *"
		. " FROM permissions"
		. " WHERE instr_id LIKE '$iid'"
		. " AND dept_id = $did"
		;

	$check_res = mysql_query($check_sql);

	if(!$check_res) {
		die("Database error (check instructor - modify): " . mysql_error());
	}

	if(mysql_num_rows($check_res) < 1) {
		add_user();
		return;
	}

	$upd_sql = ""
		. "UPDATE permissions"
		. " SET perm_level = $plvl"
		. " WHERE instr_id LIKE '$iid'"
		. " AND dept_id = $did"
		;

	$upd_res = mysql_query($upd_sql);

	if(!$upd_res) {
		die("Database error (add instructor): " . mysql_error());
	}

	$info_line = ""
		. "Permissions for instructor <strong>$iid</strong>"
		. " changed to <strong>$plvl</strong>"
		. " for department <strong>$did</strong>."
		;

	print_form(array($info_line));
}

function add_user() {
	$iid = $_POST['instr_id'];
	$plvl = $_POST['plvl'];
	$did = $_POST['dept_id'];

	$check_sql = ""
		. "SELECT *"
		. " FROM permissions"
		. " WHERE instr_id LIKE '$iid'"
		. " AND dept_id = $did"
		;

	$check_res = mysql_query($check_sql);

	if(!$check_res) {
		die("Database error (check instructor - add): " . mysql_error());
	}

	if(mysql_num_rows($check_res) > 0) {
		modify_user();
		return;
	}

	$ins_sql = ""
		. "INSERT INTO permissions"
		. " (instr_id, dept_id, perm_level)"
		. " VALUES"
		. " ($iid, $did, $plvl)"
		;

	$ins_res = mysql_query($ins_sql);

	if(!$ins_res) {
		die("Database error (add instructor): " . mysql_error());
	}

	$info_line = ""
		. "Instructor <strong>$iid</strong>"
		. " given permission <strong>$plvl</strong>"
		. " on department <strong>$did</strong>"
		;

	print_form(array($info_line));
}

function print_form($info) {
	$thisdoc = $_SERVER['PHP_SELF'];

	/* get permissions */
	include_once('inc/mysql.php');

	$perm_sql = ""
		. "SELECT"
		. " permissions.instr_id, permissions.dept_id, perm_level"
		. ", instructors.instr_lname, instructors.instr_fname"
		. ", departments.dept_name"
		. " FROM permissions, instructors, departments"
		. " WHERE permissions.instr_id = instructors.instr_id"
		. " AND permissions.dept_id = departments.dept_id"
		. " ORDER BY instructors.instr_lname"
		;

	$perms = array();
	$depts = array();
	$depts_sorted = array();
	$instructors = array();

	$perm_res = mysql_query($perm_sql);

	if(!$perm_res) {
		die("Database error (get permissions): " . mysql_error());
	}

	while($row = mysql_fetch_assoc($perm_res)) {
		$iid = $row['instr_id'];
		$did = $row['dept_id'];
		$dname = $row['dept_name'];
		$plvl = $row['perm_level'];
		$lname = $row['instr_lname'];
		$fname = $row['instr_fname'];

		$name = $lname;

		if($fname != '') {
			$name .= ", $fname";
		}

		$instructors[$iid]['name'] = $name;

		if($did != -1 && (array_search($dname, $depts_sorted) === false)) {
			array_push($depts_sorted, $dname);
		}

		$depts[$dname] = $did;
		$perms[$did][$iid]['name'] = $name;

		$highperm = $perms[$did][$iid]['perm'];

		if(
			($highperm == '')
			|| ($highperm == 0)
			|| ($highperm < $plvl)
		) {
			$perms[$did][$iid]['perm'] = $plvl;
		}
	}

	sort($depts_sorted);

	/* get perms info */
	$perms_info = array();

	$perm_sql = ""
		. "SELECT *"
		. " FROM permission_desc"
		. " ORDER BY perm_level DESC"
		;

	$perm_res = mysql_query($perm_sql);

	if(!$perm_res) {
		die("Database error (permissions info): " . mysql_error());
	}

	$perm_sel = ""
		. "<select name='plvl'>\n"
		. "\t<option disabled='true' selected='true' value=''>"
		. "Permission Level"
		. "</option>\n"
		;

	$perm_sel2 = $perm_sel;

	while($row = mysql_fetch_assoc($perm_res)) {
		$plvl = $row['perm_level'];
		$pname = $row['perm_name'];
		$pdesc = $row['perm_descr'];

		$perms_info[$plvl]['name'] = $pname;
		$perms_info[$plvl]['desc'] = $pdesc;

		$perm_sel .= "\t<option value='$plvl'>$pname</option>\n";

		if($plvl != 3 && $plvl != 0) {
			$perm_sel2 .= "\t<option value='$plvl'>$pname</option>\n";
		}
	}

	$perm_sel .= "</select>";
	$perm_sel2 .= "</select>";

	/* create instructors select box */
	$isel_txt = ""
		. "<select name='instr_id'>\n"
		. "\t<option disabled='true' selected='true' value=''>"
		. "Instructor"
		. "</option>\n"
		;

	foreach($instructors as $iid => $instructor) {
		$iname = $instructor['name'];

		$isel_txt .= "\t<option value='$iid'>$iname</option>\n";
	}

	$isel_txt .= "</select>";

	/* create departments select box */
	$dept_sel = ""
		. "<select name='dept_id'>\n"
		. "\t<option selected='true' value='-1'>All Departments</option>\n"
		;

	foreach($depts_sorted as $dname) {
		$did = $depts[$dname];

		if($did == -1) {
			$seladd = " selected='true'";
		} else {
			$seladd = "";
		}

		$dept_sel .= "\t<option value='$did'>$dname</option>\n";
	}
	$dept_sel .= "</select>";

	/* now display the form */
	include('inc/insidehead.php');

	print <<<INTRO

<div id='content'>

<h1>Instructor permissions</h1>

<p>
On this page you can view and modify instructor permissions.  Note that "Instructor" permissions are not shown on this page, but "Administrator", "Department Head", and "No Access" are shown.
<p>

<p>
To view detailed descriptions of instructer permission levels, <a href='perm_desc.php' target='_new'>click here</a>.
</p>

<p>
To modify an instructor's permission level for a department, find that instructor's name under the department's heading and select the proper permission level.  If the instructor isn't listed explicitly for the department, use the "Add" form at the bottom of that department's heading to add him after you have checked to make sure he doesn't have global permissions.
</p>

<p>You can create new users with this form (<strong>bold</strong> fields are required):</p>

<p>
<form method='post' action='$thisdoc'>
<input type='hidden' name='check' value='3'>
First Name:</strong> <input name='fname'/><br/>
<strong>Last Name:</strong> <input name='lname'/><br/>
Email address: <input name='email'/><br/>
<strong>Instructor ID:</strong> <input name='instr_id'/><br/>
<strong>Password:</strong> <input type='password' name='pass'/><br/>
<strong>Permission level:</strong> $perm_sel<br/>
<strong>Department:</strong> $dept_sel<br/>
<input type='submit' value='New User'/>
</form>
</p>

</p>

<hr width='75%'>

INTRO;

	if(count($info) > 0) {
		print "<h2>Information</h2>\n<ul>";

		foreach($info as $line) {
			print "<li>$line</li>\n";
		}

		print "</ul>\n<hr width='75%'>\n";
	}

	/* first Global (All Departments) permissions */
	print "<h2>Global</h2>\n";
	print "<h3>\n<em>New \"Administrator\" and \"No Access\" permissions must be added here, as they have no meaning on a per-department basis.</em>\n</h3>\n";
	print "<div class='left'>\n<ul>\n";

	$did = -1;

	foreach($perms[$did] as $iid => $instructor) {
		$iname = $instructor['name'];
		$plvl = $instructor['perm'];
		$pname = $perms_info[$plvl]['name'];

		if(($plvl > 1 || $plvl == 0) && ($iid != 'admin')) {
			$fname = "form" . md5("$iid$did");

			$seltxt = ""
				. "<select name='plvl'"
				. " onchange='document.$fname.submit()'>\n"
				;

			foreach($perms_info as $iplvl => $perm_info) {
				$ipname = $perm_info['name'];

				$seltxt .= "\t<option value='$iplvl'";
				if($iplvl == $plvl) {
					$seltxt .= " selected='true'";
				}
				$seltxt .= ">$ipname</option>\n";
			}

			$seltxt .= "</select>\n";

			print "<li>";
			print "<form method='post' name='$fname' action='$thisdoc'>";
			print "<input type='hidden' name='check' value='1'/>";
			print "<input type='hidden' name='instr_id' value='$iid'/>";
			print "<input type='hidden' name='dept_id' value='$did'/>";
			print "$seltxt $iname";
			print "</form>";
			print "</li>\n";
		}
	}

	print "</ul>\n";
	print "</div>\n";

	print "<p>\n";
	print "<form method='post'>";
	print "<input type='hidden' name='check' value='2'/>";
	print "<input type='hidden' name='dept_id' value='$did'/>";
	print "<input type='submit' value='Add'/> ";
	print "$isel_txt";
	print " as $perm_sel";
	print "</form>";
	print "</p>\n";

	/* then for each department */
	foreach($depts_sorted as $dname) {
		$did = $depts[$dname];

		print "\n<hr width='50%'>\n";
		print "<h2>$dname</h2>\n<div class='left'>\n<ul>\n";

		foreach($perms[$did] as $iid => $instructor) {
			$iname = $instructor['name'];
			$plvl = $instructor['perm'];
			$pname = $perms_info[$plvl]['name'];

			if(($plvl > 1 || $plvl == 0) && ($iid != 'admin')) {
				$fname = "form" . md5("$iid$did");

				$seltxt = ""
					. "<select name='plvl'"
					. " onchange='document.$fname.submit()'>\n"
					;

				foreach($perms_info as $iplvl => $perm_info) {
					$ipname = $perm_info['name'];

					$seltxt .= "\t<option value='$iplvl'";
					if($iplvl == $plvl) {
						$seltxt .= " selected='true'";
					}
					$seltxt .= ">$ipname</option>\n";
				}

				$seltxt .= "</select>\n";

				print "<li>";
				print "<form method='post' name='$fname' action='$thisdoc'>";
				print "<input type='hidden' name='check' value='1'/>";
				print "<input type='hidden' name='instr_id' value='$iid'/>";
				print "<input type='hidden' name='dept_id' value='$did'/>";
				print "$seltxt $iname";
				print "</form>";
				print "</li>\n";
			}
		}

		print "</ul>\n";
		print "</div>\n";

		print "<p>\n";
		print "<form method='post'>";
		print "<input type='hidden' name='check' value='2'/>";
		print "<input type='hidden' name='dept_id' value='$did'/>";
		print "<input type='submit' value='Add'/> ";
		print "$isel_txt";
		print " as $perm_sel2";
		print "</form>";
		print "</p>\n";
	}

	print "</div>\n";

	include('inc/insidefoot.php');
}
?>