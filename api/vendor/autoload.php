<?php

spl_autoload_register(function($class) {
	$prefix = 'Blog';

	$base_dir = dirname(dirname( __FILE__ ));

	$len = strlen($prefix);

	if (strncmp($prefix, $class, $len) !== 0) {
		return;
	}

	$relative_class = strtolower(substr($class, $len));

	$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

	if (file_exists($file)) {
		require $file;
	}
});
