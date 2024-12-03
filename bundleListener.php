<?php
// Directories to monitor
$root = '/home/alen/git/IT490';
$directories = [
    '/home/alen/git/IT490/login',
]; // Add directories as needed

// Path to the ini file
$iniFile = '/var/log/config.ini';

// Load existing config or initialize
$config = file_exists($iniFile) ? parse_ini_file($iniFile, true) : [];

// Adds exisiting files to the config file
foreach ($directories as $dir) {
    $section = basename($dir);

    if (!isset($config[$section])) {
        $config[$section] = [];
        $config[$section]['file'] = [];
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $filename) {
        // Skip unwanted files
        if (should_skip_file($filename)) {
            continue;
        }

        $filePath = $dir . '/' . $filename;
        $relativePath = str_replace($root, '', $filePath);

        if (!in_array($relativePath, $config[$section])) {
            $config[$section][] = $relativePath;
        }
    }
}

file_put_contents($iniFile, build_ini_string($config));


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

            // Skip unwanted files
            if (should_skip_file($filename)) {
                continue;
            }

            // Full path to the new file
            $filePath = $dir . '/' . $filename; 

            $section = basename($dir);

            // Make the file path 
            $relativePath = str_replace('/home/alen/git/IT490', '', $filePath);

            // Initialize the section if it doesn't exist
            if (!isset($config[$section])) {
                $config[$section] = [];
                $config[$section]['file'] = [];
            }

            // Ensure the file is not already in the config
            if (!in_array($relativePath, $config[$section])) {
                $config[$section]['file'][] = $relativePath;
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
    foreach ($assoc_arr as $sectionName => $sectionData) {
        $content .= "[$sectionName]\n";
        foreach ($sectionData as $key => $values) {
            if (is_array($values)) {
                foreach ($values as $value) {
                    $content .= $key . "[] = " . $value . "\n";
                }
            } else {
                $content .= "$key = $values\n";
            }
        }
        $content .= "\n";
    }
    return $content;
}

// Helper function to see if a file should be skipped
function should_skip_file($filename) {
    if ($filename[0] === '.') {
        return true;
    }

    // Skip files ending with unwanted extensions
    $fileTypes = array('.swp');
    foreach ($fileTypes as $ext) {
        if (substr($filename, -strlen($ext)) === $ext) {
            return true;
        }
    }

    return false;
}
?>
