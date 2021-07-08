# ccs7up
Updates CodeCharge Studio generated code to work with PHP 7.1+.

The last release of CodeCharge Studio v5.1.1.18992 in March of 2018 will generate PHP code compatible only up to PHP v7.0.33. After publishing your project from CodeCharge Studio to your web server, run this script to scan all PHP files and fix any unsupported or deprecated PHP commands or methods.

* Tested for PHP versions up to v8.0.8.
* Interactive usage with optional recursive folder scanning.
* Changes PHP 4 style constructors or methods that have the same name as the class they are defined in.
* Fixes CCSEvents bindings and illegal string offset errors.
* Alters the deprecated each() function with foreach().
* Fixes casting string objects to an integer.
* Adjusts deprecated curly brace syntax for accessing array elements and string offsets.
* Replace any occurences of deprecated get_magic_quotes_gpc() function with a false constant.

<strong>Usage</strong>

Place the ccs7up.php file in your web root directory with proper permissions (do not give permission to your web user or group!), then from a command line execute:

Windows: php c:\xampp\htdocs\ccs7up.php 

Linux: php /var/www/ccs7up.php


Must be run from the CLI. Will not execute from a browser. Again, run <strong>after</strong> publishing from CodeCharge Studio to your web server.
