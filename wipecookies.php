<?php
foreach(array_keys($_COOKIE) as $key) {
	setcookie($key, '');
}

print_r($_COOKIE);
?>