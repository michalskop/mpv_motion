<?php
/**
* sets up analysis
*/

set_time_limit(0);
session_start();
ob_end_flush();
ob_start();

//read defaults
global $settings;
require("../settings.php");

//set custom error handling
set_error_handler("customError");

// read parameters
global $parameters;
$parameters = read_parameters($settings['file_path']);
//print_r($parameters);
	
//create databases (db.php)
$start = microtime(true);
require($settings['helper_path'] . 'db.php');
$time_taken = microtime(true) - $start;
echo "created db(s), time:".$time_taken . "<br/>\n";
ob_flush();flush();

//insert csv into db (csv2sqlite.php)
if (isset($parameters['filename']) and ($parameters['filename'] != '')) {
	$start = microtime(true);
	require($settings['helper_path'] . 'csv2sqlite.php');
	$time_taken = microtime(true) - $start;
	echo "inserted csv into db, time:".$time_taken . "<br/>\n";
	ob_flush();flush();
}

//insert data into correct db scheme
if (isset($parameters['filename']) and ($parameters['filename'] != '')) {
	$start = microtime(true);
	require($settings['helper_path'] . 'sqlite2sqlite.php');
	$time_taken = microtime(true) - $start;
	echo "inserted data into correct db scheme, time:".$time_taken . "<br/>\n";
	ob_flush();flush();
}

//insert analysis info, make intervals
$start = microtime(true);
require($settings['helper_path'] . 'test2sqlite.php');
$time_taken = microtime(true) - $start;
echo "inserted analysis info and made intervals, time:".$time_taken . "<br/>\n";
ob_flush();flush();

//prepare matrix 
if (isset($parameters['filename']) and ($parameters['filename'] != '')) {
	$start = microtime(true);
	require($settings['helper_path'] . 'prepare_matrix.php');
	$time_taken = microtime(true) - $start;
	echo "prepared matrix for R, time:".$time_taken . "<br/>\n";
	ob_flush();flush();
}

//export matrix for R
$start = microtime(true);
require($settings['helper_path'] . 'export.php');
$time_taken = microtime(true) - $start;
echo "exported matrix for R, time:".$time_taken . "<br/>\n";
ob_flush();flush();

//calculation in R
$start = microtime(true);
create_r_file();
$time_taken = microtime(true) - $start;
echo "calculated in R, time:".$time_taken . "<br/>\n";
ob_flush();flush();

//make json file
$start = microtime(true);
require($settings['helper_path'] . 'result2json.php');
$time_taken = microtime(true) - $start;
echo "created json file, time:".$time_taken . "<br/>\n";
ob_flush();flush();

/**
* creates R file
*/
function create_r_file($source='wpca.r') {
  global $settings,$parameters;
  $replace = array(
    '_PATH' => $settings['full_path'].$settings['analyses_path'],
    '_DATANAME' => $parameters['dataname'],
    '_ANALYSISNAME' => $parameters['analysisname'],
    '_LO_LIMIT' => $parameters['lo_limit'],
    '_LIBPATH' => $settings['rscript_lib_path'],
  );
  $rstring = file_get_contents($settings['helper_path'].$source);
  foreach ($replace as $key=>$item) {
    $rstring = str_replace($key,$item,$rstring);
  }
  $rfile = $settings['analyses_path'].$parameters['dataname'].'/'.$parameters['analysisname'].'/'.$parameters['analysisname'].".r";
  $fout = fopen($rfile,"w+");
  fwrite($fout,$rstring);
  fclose($fout);
  exec("{$settings['rscript']} --vanilla {$rfile}",$r_output);
}

/**
* reads parameters from parameter.txt and from $_REQUEST
* \path path to storage directory
* \return parameters array of parameters
*/
function read_parameters($path='') {
  //parameters.txt
  $parameters = read_parameters_file($path.'parameters.txt');
  
  //$_REQUEST['parameters']
  if (isset($_REQUEST['parameters']) and file_exists($path.$_REQUEST['parameters'])) {
    $parameters =array_merge($parameters,read_parameters_file($path.$_REQUEST['parameters']));
  }
  
  //$_REQUEST
  $par = array();
  foreach ($_REQUEST as $key => $r) {
    if ($key != 'parameters') {
      if ($r != '') {
        $ar = str_getcsv($r);
        if (count($ar) >= 1) {
          $par[$key] = $ar;
        } else {
          $par[$key] = $r;
        }
      }
    }
  }
  if (count($par) > 0)
    $parameters += $par;
    
  return $parameters;
  
}

/**
* custom error handling
*/
function customError($errno, $errstr, $error_file, $error_line, $error_context) {
  echo "<b>Error:</b> [$errno] $errstr </br>
  File: {$error_file}<br/>\n
  Line: {$error_line}<br/>\n
  Context:
  ";
  /*print_r($error_context);*/
  if ($errno == 256) die("<br/><b>Stopping!</b>");
}



/**
* reads parameters from file into array
* \filename
* \return array of parameters read from the file
*/
function read_parameters_file($filename) {
  $out = array();
  if (file_exists($filename)) {
    $fin = file($filename);
    foreach ($fin as $row) {
       if (!(substr(trim($row),0,2) == '//')) {
         $tmp = explode("=",$row);
         $key = trim($tmp[0]);
         array_shift($tmp);
         $tmp = implode("=",$tmp);
         $ar = str_getcsv($tmp);
         if (count($ar) == 1) {
           if ($ar[0] != '') {
             $out[$key] = $ar[0];
           }
         } else {
           $out[$key] = $ar;
         }
       }
    }
  }
  return $out;
}
?>
