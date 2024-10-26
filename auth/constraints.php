<?php

// ======== Type Information =========
$TYPES = ['login', 'register'];

// ======== User Information =========
$USERNAME_MAX_LENGTH = 36;
$USERNAME_MIN_LENGTH = 3;
$USERNAME_PATTERN = '/^[a-zA-Z0-9_]+$/'; // only letters, numbers, periods and underscores.

// ======== Name Information =========
$NAME_MAX_LENGTH = 100;
$NAME_MIN_LENGTH = 3;
$NAME_PATTERN = "/^[a-zA-Z\s'-]+$/"; // only letters, spaces, apostrophes and hyphens.

// ======== Password Information =========
$PASSWORD_MAX_LENGTH = 256;
$PASSWORD_MIN_LENGTH = 4;
