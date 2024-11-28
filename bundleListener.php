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
$watchDescriptors = [];
foreach ($directories as $dir) {
    $wd = inotify_add_watch($inotify, $dir, IN_CREATE | IN_MOVED_TO);
    $watchDescriptors[$wd] = $dir;
}

// Non-blocking mode
stream_set_blocking($inotify, 0);

// Repeating it so we can add it as a systemD service to always be listening 
while (true) {
    $events = inotify_read($inotify);
    if ($events) {
        foreach ($events as $event) {
            $wd = $event['wd'];
            // Get the directory from the watch descriptor
            $dir = $watchDescriptors[$wd]; 
            $filename = $event['name'];
            // Full path to the new file
            $filePath = $dir . '/' . $filename; 

            $section = basename($dir);

            // Make the file path 
            $relativePath = str_replace('/home/alen/git/IT490', '', $filePath);

            // Initialize the section if it doesn't exist
            if (!isset($config[$section])) {
                $config[$section] = [];
            }

            // Ensure the file is not already in the config
            if (!in_array($relativePath, $config[$section])) {
                $config[$section][] = $relativePath;
                file_put_contents($iniFile, build_ini_string($config));
            }
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
