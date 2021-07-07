<?php

/*
Updates PHP files created by CodeCharge Studio v5.1.1.18992 to work with PHP v7.1+.

Place this file into your web root folder, then from the CLI run: php ccs7up.php

*/

// Exit if script is not called from a command line.
if (!isset($_SERVER['SERVER_SOFTWARE']) and (php_sapi_name() == 'cli' or (is_numeric($_SERVER['argc']) and $_SERVER['argc'] > 0))) {
  for ($i = 0; $i < 2; $i++) echo "\n"; //pretty spacing
}
else {
  echo "Sorry. This script must be run from the command line. Goodbye."; exit;
}

echo "This script will look for PHP files generated by CodeCharge Studio and update them to work with PHP v7.1+.\n";
echo "Is this okay? [y/N] ";
$handle = fopen("php://stdin","r");
$okay = fgets($handle);
fclose($handle);
if (trim($okay) != 'y') {
    echo "\nGoodbye!\n\n";
    exit;
}

echo "\nEnter the CodeCharge Studio project folder or subfolder.\nEx: MyProject.com or MyProject.com/admin\n: ";
$handle = fopen("php://stdin","r");
$folder = fgets($handle);
fclose($handle);

$projectFolder = getcwd() . "/" . trim($folder) . "/";
if (!is_dir($projectFolder)) {
  echo "\nProject folder not found! Goodbye.\n\n"; exit;
}

echo "\nWould you like to scan " . $projectFolder . " recursively? [y/N] ";
$handle = fopen("php://stdin","r");
$recursive = strtoupper(trim(fgets($handle)));
fclose($handle);
if (trim($recursive) != 'Y' and trim($recursive) != 'N') {
  echo "\nGoodbye!\n\n"; exit;
}

// How many PHP files will be scanned?
if ($recursive == 'Y') {
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectFolder, RecursiveDirectoryIterator::SKIP_DOTS));
}
else {
  $iterator = new FilesystemIterator($projectFolder, FilesystemIterator::SKIP_DOTS);
}
echo "\n" . number_format(iterator_count($iterator)) . " files will be scanned. Continue? [y/N] ";
$handle = fopen("php://stdin","r");
$okay = fgets($handle);
fclose($handle);
if (trim($okay) != 'y') {
    echo "\nGoodbye!\n\n";
    exit;
}

echo "\nWeb server PHP version found: " . phpversion() . "\n"; sleep(1);
echo "Looking for PHP files in " . $projectFolder . "\n\n"; sleep(1);

$PHPFileCount = 0;
$PHPFileWithChanges = 0;
$TotalChanges = 0;

// Populate an array of all files and directories (recursive) found.
if ($recursive == 'Y') {
  $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectFolder, RecursiveDirectoryIterator::SKIP_DOTS));
}
else {
  $rii = new FilesystemIterator($projectFolder, FilesystemIterator::SKIP_DOTS);
}
$files = array(); 
foreach ($rii as $file) {
    if ($file->isDir()) { 
        continue;
    }
    $files[] = $file->getPathname(); 
}

// Now go through each file found.
foreach ($files as $dirKey => $dirVal) {

  // We only care about PHP files.
  if (strtoupper(pathinfo($dirVal, PATHINFO_EXTENSION)) == "PHP") {

    $PHPFile = $dirVal;

    if (basename($PHPFile) == basename($_SERVER["SCRIPT_FILENAME"])) {
      continue; //skip this script obviously.
    }

    $PHPFileCount++;

    echo "Found PHP file " . $PHPFile . ". \n"; 
    
    // Scan the file.
    $changeCount = fixPHP($PHPFile);
    
    if ($changeCount == 1) {
      $PHPFileWithChanges++;
      echo $changeCount . " change made. \n\n";
    }
    elseif ($changeCount > 1) {
      $PHPFileWithChanges++;
      echo number_format($changeCount) . " changes made. \n\n";
    }
    else {
      echo "No changes made. \n\n";
    }

    $TotalChanges = $TotalChanges + $changeCount;

    time_nanosleep(0, 100000000); //pause one tenth a sec for effect. one qtr: 250000000. one half: 500000000
  
  } //end of if a PHP file found

} //end of foreach $dir


// Display the results of the scan.
echo "All done. " . number_format($PHPFileCount) . " PHP files scanned. " . number_format($TotalChanges) . " changes made in " . number_format($PHPFileWithChanges) . " files. Goodbye!\n\n";
exit;


// ** Local Functions **

function fixPHP($PHPFile)
{

  //Makes the necessary corrections.

  $changesCounter = 0;

  //Look for 'class' or 'class extends class' in the given file.
  $str = file_get_contents($PHPFile);
  $str_len = strlen($str);
  $str_result = null;
  $offset = 0;
  $offset_prev = 0;
  $offset1 = 0;
  $offset2 = 0;
  $replaced_content = null;
  $classFound = false;
  $classCounter = 0;
  $pattern = "/(class[\s\r\n]+[a-z_0-9]+[\n\r\s]+extends[\s\r\n]+[a-z_0-9]+[\s\r\n]*{|class[\s\r\n]+[a-z_0-9]+[\s\r\n]*{)/i";

  while (preg_match( $pattern, $str, $matches, PREG_OFFSET_CAPTURE, $offset) && $offset < $str_len ) {

    //Classes found in the file! Set the flag.
    $classFound = true;
    
    //Increment counter.
    $classCounter++;

    //Get the class name found.
    //Ex: class clsTemplate
    //Ex: class Tpl {
    //Ex: class TreePlainFormatter extends JsonFormatter {
    $res = select_min_match($matches);
    $temp = $matches[$res][0];
		
    //Next clean up what was found.
    //Ex: clsTemplate
    //Ex: Tpl
    //Ex: TreePlainFormatter
    $temp = str_replace(array("\r", "\n", "{"), " ", $temp);
    $temp = str_replace("  ", " ", $temp);
    $tokens = explode(" ", $temp);
    $class_name = $tokens[1];
    $parent_class_name = null;
    if ($tokens[2] == "extends") {
      $parent_class_name = $tokens[3];
    }

		//Move the inter-file pointer to the start of our matching string found.
    $new_offset = $matches[$res][1];
		$offset1 = $new_offset + 1;
		if (strlen($str_result) == 0) {
			$str_result = substr($str, 0, $new_offset);
		}

		//Get the location of the next class found, if any, so that only the 
    //current class found is processed.
		if (preg_match($pattern, $str, $matches1, PREG_OFFSET_CAPTURE, $offset1)) {
			//Found another class.
			$res1 = select_min_match($matches1);
			$next_offset = $matches1[$res1][1];
			$string_to_process = substr($str, $new_offset, $next_offset - $new_offset);
		}
		else {
			//There isn't another class definition, so use the rest of the input string.
			$string_to_process = substr($str, $new_offset);
			$next_offset = $str_len;
		}

		//Look for functions within the class using the same name as the class.
    //Ref: https://www.php.net/manual/en/migration70.deprecated.php#:~:text=PHP%204%20style%20constructors%20(methods,construct()%20method%20are%20unaffected
		if (preg_match('/function[ ]+' . $class_name . '[(]/i', $string_to_process)) {
			$string_to_process = preg_replace('/function[ ]+' . $class_name . '[(]/i', "function __construct(", $string_to_process);
			if ($string_to_process  == NULL) {
				echo "Fatal error attempting to change class " . $class_name . " function constructor! \n"; return;
			}
      else {
        echo "Fixed class " . $class_name . " function constructor. \n";
        //$change_made = true;
        $changesCounter++;
      }
		}

		//Look for class parents using the same name as the class, and change to a constructor. Same as above.
		if ($parent_class_name and preg_match('/parent::' . $parent_class_name . '[(]/i', $string_to_process)) {
			$string_to_process = preg_replace('/parent::' . $parent_class_name . '[(]/i', "parent::__construct(", $string_to_process);
			if ($string_to_process  == NULL) {
				echo "Fatal error attempting to change parent class " . $parent_class_name . " function constructor! \n"; return;
			}
      else {
        echo "Fixed parent class " . $parent_class_name . " function constructor. \n";
        //$change_made = true;
        $changesCounter++;
      }
		}

    //Move our pointer to the next occurence (or end of file).
    $offset = $next_offset;
    $str_result .= $string_to_process;

  } //end of while 'class' found

  //No 'class' found in file, so we'll reload the entire file contents. If there were classes found in the file,
  //then we'll have the new altered contents already.
  if (!$classFound) {
    $str_result = $str;
  }
  
  //Look for [on its own line]: $CCSEvents then all characters case-insensitive all the way to a semicolon (end of PHP line)
  # This looks to be in error. Check into that semicolon location!
  //Replace with: $CCSEvents = array();
  $str_result = preg_replace('/([\r\n]+[\s]*)(\$CCSEvents[\s]*);/i', '$1$2 = array();', $str_result, -1, $occurences);
  if ($occurences) {
    echo "Fixed " . $occurences . " occurences of \$CCSEvents not properly declared as an array.\n";
    $changesCounter++;
  }

  //Look for: public $CCSEvents all the way to a semicolon (end of PHP line)
  //Ex: public $CCSEvents;
  //Replace with: public $CCSEvents = array();
  $str_result = preg_replace('/(public[\s]+\$CCSEvents[\s]*[;])/i', 'public \$CCSEvents = array();', $str_result, -1, $occurences);
  if ($occurences) {
    echo "Fixed " . $occurences . " occurences of public \$CCSEvents not properly declared as an array.\n";
    $changesCounter++;
  }

  //Look for: public $CCSEvents = then all the way to a semicolon (end of PHP line)
  //Ex: public $CCSEvents = ""
  //Replace with: public $CCSEvents = array();
  $str_result = preg_replace('/(public[\s]+\$CCSEvents[\s]*=[\s]*""[\s]*[;])/i', 'public \$CCSEvents = array();', $str_result, -1, $occurences);
  if ($occurences) {
    echo "Fixed " . $occurences . " occurences of public \$CCSEvents = \"\" not properly declared as an array.\n";
    $changesCounter++;
  }

  //Look for [on its own line]: $CCSEvents = then all the way to a semicolon (end of PHP line)
  //Ex: $CCSEvents = ""
  //Replace with: $CCSEvents = array();
  $str_result= preg_replace('/([\r\n]+)(\$CCSEvents[\s]*=[\s]*""[\s]*;)/i', '$1\$CCSEvents = array();', $str_result, -1, $occurences);
  if ($occurences) {
    echo "Fixed " . $occurences . " occurences of \$CCSEvents = \"\" not properly declared as an array.\n";
    $changesCounter++;
  }

  //Look for: var $CCSEvents = "" then all the way to a semicolon (end of PHP line)
  //Ex: var $CCSEvents = ""
  //Replace with: var $CCSEvents = array();
  $str_result=preg_replace('/(var[\s]+\$CCSEvents[\s]*=[\s]*""[\s]*[;])/i', 'var \$CCSEvents = array();', $str_result, -1, $occurences);
  if ($occurences) {
    echo "Fixed " . $occurences . " occurences of var \$CCSEvents = \"\" not properly declared as an array.\n";
    $changesCounter++;
  }

  //Look for: $this->CCSEvents = "" then all the way to a semicolon (end of PHP line)
  //Ex: $this->CCSEvents = ""
  //Replace with: $this->CCSEvents = array();
  $str_result=preg_replace('/(\$this->CCSEvents[\s]*=[\s]*""[\s]*;)/i', '\$this->CCSEvents = array();', $str_result, -1, $occurences);
  if ($occurences) {
    echo "Fixed " . $occurences . " occurences of \$this->CCSEvents = \"\" not properly declared as an array.\n";
    $changesCounter++;
  }

  //The CodeCharge generated Template.php file uses deprecated each() functions.
  //See: https://www.php.net/manual/en/function.each.php
  //Replace accordingly.
  if (preg_match('/Template.php$/i', $PHPFile)) {
    
    $each1_pattern = preg_quote('while (list($key,) = each($searching_array))', '/');
    if (preg_match( '/' . $each1_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $each1_pattern . '/i', 'foreach($searching_array as $key => $dontcare)', $str_result, 1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of each(\$searching_array) with foreach.\n";
        $changesCounter++;
      }
    }

    $each2_pattern = preg_quote('while(list($key, $value) = each($this->blocks[$block_name]))', '/');
    if (preg_match( '/' . $each2_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $each2_pattern . '/i', 'foreach($this->blocks[$block_name] as $key => $value)', $str_result, 1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of each(\$this->blocks) with foreach.\n";
        $changesCounter++;
      }
    }

    $each3_pattern = preg_quote('while(list($key, $value) = each($this->globals))', '/');
    if (preg_match('/' . $each3_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $each3_pattern . '/i', 'foreach($this->globals as $key => $value)', $str_result, 1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of each(\$this->globals) with foreach.\n";
        $changesCounter++;
      }
    }

  } //end of if Template.php

  //Replace each functions in Classes.php.
  if (preg_match('/Classes.php$/i', $PHPFile)) {
    $each4_pattern = preg_quote('while ($blnResult && list ($key, $Parameter) = each ($this->Parameters)) 
      {','/');
    if (preg_match( '/' . $each4_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $each4_pattern . '/i', 'foreach($this->Parameters as $key => $Parameter) {
        if(!$blnResult)
          continue;', $str_result, 1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of each (\$this->Parameters) with foreach.\n";
        $changesCounter++;
      }
    }
  } //end of if Classes.php

  //Fix casting string to int in DB_Adapter::PageCount().
  if (preg_match('/db_adapter.php$/i', $PHPFile)) {
    $ceil_pattern = preg_quote('return $this->PageSize && $this->RecordsCount != "CCS not counted" ? ceil($this->RecordsCount', '/');
    if (preg_match( '/' . $ceil_pattern . '/i' , $str_result)) {
      $str_result = preg_replace('/' . $ceil_pattern . '/i', 'return $this->PageSize && $this->RecordsCount != "CCS not counted" ? ceil((int)$this->RecordsCount', $str_result, 1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of ceil(\$this->RecordsCount) with ceil(int)\$this->RecordsCount.\n";
        $changesCounter++;
      }
    }
  } //end of if db_adapter.php

  //The Common file has a function CCGetListValues with variables that all need to be defined as arrays.
  if (preg_match('/(common.php|commonserv.php)$/i', $PHPFile)) {
		$str222 = 'function CCGetListValues(&$db, $sql, $where = "", $order_by = "", $bound_column = "", $text_column = "", $dbformat = "", $datatype = "", $errorclass = "", $fieldname = "", $DSType = dsSQL)
{
    $errors = new clsErrors();
    $values = ';
    $str222_pattern = preg_quote($str222, '/');
    if (preg_match('/' . $str222_pattern . '("")' . '/i', $str_result)) {
      $str_result = preg_replace( '/' . $str222_pattern . '("")' . '/i', $str222 . "array()", $str_result, 1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of CCGetListValues() variable definitions with arrays.\n";
        $changesCounter++;
      }
    }
  } //end of if Common file.

  //Replace deprecated array curly braces in Services.php.
  //See: https://wiki.php.net/rfc/deprecate_curly_braces_array_access
  if (preg_match('/Services.php$/i', $PHPFile)) {
    
    $curly1_pattern = preg_quote('$utf8{0}', '/');
    if (preg_match( '/' . $curly1_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly1_pattern . '/i', '$utf8[0]', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$utf8{0} with \$utf8[0].\n";
        $changesCounter++;
      }
    }

    $curly2_pattern = preg_quote('$utf8{1}', '/');
    if (preg_match( '/' . $curly2_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly2_pattern . '/i', '$utf8[1]', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$utf8{1} with \$utf8[1].\n";
        $changesCounter++;
      }
    }

    $curly3_pattern = preg_quote('$utf8{2}', '/');
    if (preg_match( '/' . $curly3_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly3_pattern . '/i', '$utf8[2]', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$utf8{2} with \$utf8[2].\n";
        $changesCounter++;
      }
    }

    $curly4_pattern = preg_quote('$utf16{0}', '/');
    if (preg_match( '/' . $curly4_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly4_pattern . '/i', '$utf16[0]', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$utf16{0} with \$utf16[0].\n";
        $changesCounter++;
      }
    }

    $curly5_pattern = preg_quote('$utf16{1}', '/');
    if (preg_match( '/' . $curly5_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly5_pattern . '/i', '$utf16[1]', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$utf16{1} with \$utf16[1].\n";
        $changesCounter++;
      }
    }

    $curly6_pattern = preg_quote('$var{$c}', '/');
    if (preg_match( '/' . $curly6_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly6_pattern . '/i', '$var[$c]', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$var{\$c} with \$var[\$c].\n";
        $changesCounter++;
      }
    }

    $curly7_pattern = preg_quote('$var{$c + 1}', '/');
    if (preg_match( '/' . $curly7_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly7_pattern . '/i', '$var[$c] + 1', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$var{\$c + 1} with \$var[\$c] + 1.\n";
        $changesCounter++;
      }
    }

    $curly8_pattern = preg_quote('$var{$c + 2}', '/');
    if (preg_match( '/' . $curly8_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly8_pattern . '/i', '$var[$c] + 2', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$var{\$c + 2} with \$var[\$c] + 2.\n";
        $changesCounter++;
      }
    }

    $curly9_pattern = preg_quote('$var{$c + 3}', '/');
    if (preg_match( '/' . $curly9_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly9_pattern . '/i', '$var[$c] + 3', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$var{\$c + 3} with \$var[\$c] + 3.\n";
        $changesCounter++;
      }
    }

    $curly10_pattern = preg_quote('$var{$c + 4}', '/');
    if (preg_match( '/' . $curly10_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly10_pattern . '/i', '$var[$c] + 4', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$var{\$c + 4} with \$var[\$c] + 4.\n";
        $changesCounter++;
      }
    }

    $curly11_pattern = preg_quote('$var{$c + 5}', '/');
    if (preg_match( '/' . $curly11_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly11_pattern . '/i', '$var[$c] + 5', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$var{\$c + 5} with \$var[\$c] + 5.\n";
        $changesCounter++;
      }
    }

    $curly12_pattern = preg_quote('$chrs{$c}', '/');
    if (preg_match( '/' . $curly12_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly12_pattern . '/i', '$chrs[$c]', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$chrs{\$c} with \$chrs[\$c].\n";
        $changesCounter++;
      }
    }

    $curly13_pattern = preg_quote('$chrs{++$c}', '/');
    if (preg_match( '/' . $curly13_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly13_pattern . '/i', '$chrs[++$c]', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$chrs{++\$c} with \$chrs[++\$c].\n";
        $changesCounter++;
      }
    }

    $curly14_pattern = preg_quote('$str{0}', '/');
    if (preg_match( '/' . $curly14_pattern . '/i', $str_result)) {
      $str_result = preg_replace('/' . $curly14_pattern . '/i', '$str[0]', $str_result, -1, $occurences);
      if ($occurences) {
        echo "Fixed " . $occurences . " occurences of \$str{0} with \$str[0].\n";
        $changesCounter++;
      }
    }

  } //end of if Services.php

  //Replace any occurences of deprecated get_magic_quotes_gpc() function with a false constant.
  //See: https://www.php.net/manual/en/function.get-magic-quotes-gpc.php
  $str_result = str_replace("get_magic_quotes_gpc()", "false", $str_result, $occurences);
  if ($occurences) {
    echo "Removed " . $occurences . " occurences of get_magic_quotes_gpc() function.\n";
    $changesCounter++;
  }

  //All done looking for changes, now actually apply the changes.
  if (strlen($str_result) > 0 && $changesCounter > 0) {
    file_put_contents($PHPFile, $str_result);
    echo "File rewritten with ";
  }

  return $changesCounter;

} //end of function fixPHP

function select_min_match($matches)
{
	
  //Gets the match location. Called while looking for a class.
  //$res = select_min_match($matches);

  if (sizeof($matches) ==  0)
		return false;
	$offsets = array();
	foreach ($matches as $n => $match) {
		$offsets[$match[1]] = $n;
	}
	ksort($offsets);
	reset($offsets);
	return current($offsets);

}
//end of function select_min_match.

?>