<?php
//make json from csv files
//shall not be called directly

if (!isset($parameters['dataname']) or ($parameters['dataname'] == ''))
  trigger_error("Missing data name (dataname)",E_USER_ERROR);
if (!isset($parameters['analysisname']) or ($parameters['analysisname'] == ''))
  trigger_error("Missing analysis name (analysisname)",E_USER_ERROR);
  
$dataname = $parameters['dataname'];
$analysisname = $parameters['analysisname']; 

$fout = fopen($settings['analyses_path'].$dataname.'/'.$analysisname.'/'.$analysisname.'.json',"w+");

$anal_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$analysisname.'/'.$analysisname.'.sqlite3');	//******************
$anal_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

$query = "
  SELECT name,(since+until)/2 as time FROM analysis_interval WHERE name IN
  (SELECT distinct(interval_name) as name FROM analysis_division_in_interval)
  ORDER BY name
";
$result = query2array($anal_db,$query);

$data = array();
$tdata = array();
foreach ($result as $row) {
  $path = $settings['analyses_path'].$dataname.'/'.$analysisname.'/';
  $f = fopen($path.$row['name'].'_result.csv',"r");
  $tdata = csv2array($f,true);
  foreach ($tdata as $drow) {
    //if ($drow[3] and ($drow[3]!='FALSE') and ($drow[3] != '0')) {
      $data[$drow[0]]['name'] = $drow[0];
      $data[$drow[0]]['d1'][] = array((float)$row['time'],(float)$drow[1]);
      $data[$drow[0]]['d2'][] = array((float)$row['time'],(float)$drow[2]);
      $data[$drow[0]]['color'][] = array((float)$row['time'],trim($drow[4]));
    //}
        //die();    
  }
}

//remove keys
$d = array();
foreach ($data as $r) {
  $d[] = $r;
}

fwrite($fout,json_encode($d));
fclose($fout);


function query2array($table,$query) {
  $q = $table->prepare($query);
  $q->execute();
  $result = $q->fetchAll();
  return $result;
}

function csv2array($file,$header) {
  $ar = array();
  $i = 0;
  while (($data = fgetcsv($file, 0, ",")) !== FALSE) {
        if (!($header and ($i < 1)))
          $ar[] = $data;
        $i++;
    }
  return $ar;
}
?>
