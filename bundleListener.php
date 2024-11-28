<?php
// Directories to monitor
$directories = ['/home/alen/git/IT490/login']; // Add directories as needed

// Path to the ini file
$iniFile = '/var/log/config.ini';

// Load existing config or initialize
$config = file_exists($iniFile) ? parse_ini_file($iniFile, true) : [];

// Initialize inotify
$inotify = inotify_init();

// Add watches
foreach ($directories as $dir) {
    inotify_add_watch($inotify, $dir, IN_CREATE | IN_MOVED_TO);
}

// Non-blocking mode
stream_set_blocking($inotify, 0);

// Repeating it so we can add it as a systemD service to always be listening 
while (true) {
    $events = inotify_read($inotify);
    if ($events) {
        foreach ($events as $event) {
            $dir = $event['name'] ? dirname($event['name']) : '';
            $section = basename($dir);
            $filePath = $dir . '/' . $event['name'];
            $config[$section][] = $filePath;
            file_put_contents($iniFile, build_ini_string($config));
        }
    }
    // To prevent it from constantly checking
    usleep(500000);
}

// Helper function to build ini string to add to the config file
function build_ini_string($assoc_arr) {
    $content = '';
    foreach ($assoc_arr as $key => $items) {
        $content .= "[$key]\n" . implode("\n", array_unique($items)) . "\n\n";
    }
    return $content;
}
?>
