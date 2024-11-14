<?php

$file = fopen('/var/log/DMZ/error.log', 'r');
if ($file === false) {
	echo "Failed to open the log.";
}
else {
	while (($line = fgets($file)) !== false) {
		echo "file has been opened.";
		echo $line;
	}
	fclose($file);
}



?>
