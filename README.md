# ccs7up
Updates CodeCharge Studio generated code to work with PHP 7.1+.

The last release of CodeCharge Studio v5.1.1.18992 in March of 2016 will generate PHP code compatible only up to PHP v7.0.33. After publishing your project from CodeCharge Studio to your web server, run this script to scan all PHP files and fix any unsupported or deprecated PHP commands or methods.

* Tested for PHP versions up to v8.0.8.
* Interactive usage with optional recursive folder scanning.
* Changes PHP 4 style constructors or methods that have the same name as the class they are defined in.
* Fixes CCSEvents bindings and illegal string offset errors.
* Replaces the deprecated each() function with foreach().
* Fixes casting string objects to an integer.
* Adjusts deprecated curly brace syntax for accessing array elements and string offsets.
* Replace any occurences of deprecated get_magic_quotes_gpc() function with a false constant.

<strong>Usage from CLI</strong>

Place the ccs7up.php file in your web root directory with proper permissions (do not give permission to your web user or group!), then from a command line execute:

Windows: php c:\xampp\htdocs\ccs7up.php 

Linux: php /var/www/ccs7up.php


Must be run from the CLI. Will not execute from a browser. Again, run <strong>after</strong> publishing from CodeCharge Studio to your web server.

<strong>Active Usage</strong>

To not be forced to run CCS7Up after every published update in your project, add the ccs7up_inc.php file to your CodeCharge Studio project and publish accordingly. In your Common.php file, at the top before any other includes are made, add:

```
//CCS7Up
if (version_compare(phpversion(), '7.1') >= 0) {
    include(RelativePath . "/ccs7up_inc.php");
}
```

Whenever a CodeCharge generated page is loaded, will look to see if the web server is running PHP v7.1+, and if so will scan to make sure the page has been updated by CCS7Up and update it if necessary. Another scan will be made to see if Common.php itself has also been updated by CCS7Up, and if not will go ahead and update the entire project's CodeCharge files.
