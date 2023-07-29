<?php

// Load all classes in the 'classes' folder using glob
foreach ( glob( get_stylesheet_directory() . "/classes/class.*.php" ) as $filename ) {
	require_once $filename;
}
