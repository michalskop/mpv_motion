<?php


//inserts analysis info, make intervals
//shall not be called directly



if (!isset($parameters['dataname']) or ($parameters['dataname'] == ''))
  trigger_error("Missing data name (dataname)",E_USER_ERROR);
if (!isset($parameters['analysisname']) or ($parameters['analysisname'] == ''))
  trigger_error("Missing analysis name (analysisname)",E_USER_ERROR);
  
$dataname = $parameters['dataname'];
$analysisname = $parameters['analysisname']; 

try {
	$anal_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$analysisname.'/'.$analysisname.'.sqlite3');	//**************************
	$anal_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

	$data_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$dataname.'.sqlite3');		//****************************
	$data_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

	//analysis info
	$orientation = implode(',',$parameters['orientation']);
	$query = "
	  INSERT INTO analysis_info (data,name,method,number_of_dimensions,orientation)
	  VALUES ('{$analysisname}','{$analysisname}','{$parameters['method']}',{$parameters['number_of_dimensions']},'{$orientation}')
	  ";														//******************
	$anal_db->exec($query);

	//analysis intervals
	$query = "
	  INSERT INTO analysis_interval (name,since,until)
	  VALUES (?,?,?)
	  ";
	$q = $anal_db->prepare($query);
	for($i=$parameters['since']; $i<=$parameters['until']; $i = $i + $parameters['step']) {
	  $q->execute(array($i,$i,$i+$parameters['step']));
	}

	//change manually
	/*$query = "
	  UPDATE analysis_interval
	  SET since = '2006.25'
	  WHERE since = '2006.5'
	";
	$q = $anal_db->prepare($query);
	$q->execute();*/

	//divisions in interval
	$query = "
	  SELECT * FROM division
	";

	$q = $data_db->prepare($query);
	$q->execute();
	$result = $q->fetchAll();

	$q = $anal_db->prepare("INSERT INTO analysis_division_in_interval(division_code,interval_name) VALUES(?,?)");

	$anal_db->exec("BEGIN TRANSACTION");
	foreach ($result as $row) {
	  //print_r($row);
	  $qq = $anal_db->prepare("SELECT * FROM analysis_interval 
	  WHERE 
		(julianday('{$row['divided_on']}')-julianday('0000-01-01')-CAST(since as REAL)*365.24 > 0)
	  AND 
		(julianday('{$row['divided_on']}')-julianday('0000-01-01')-CAST(until as REAL)*365.24 < 0)");
	  
	  
	  $qq->execute();
	  $qqresult = $qq->fetchAll();
	  //print_r($qqresult);die();
	  if (isset($qqresult[0]))
		$q->execute(array($row['code'],$qqresult[0]['name']));
	  else {
		echo "division {$row['code']} is not in any interval";die(); 
	  }
	} 
	$anal_db->exec("COMMIT TRANSACTION");

}
catch(PDOException $e) {
    // Print PDOException message
   trigger_error($e->getMessage(),E_USER_ERROR);
}

?>
