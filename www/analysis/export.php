<?php
//export matrix
//shall not be called directly

if (!isset($parameters['dataname']) or ($parameters['dataname'] == ''))
  trigger_error("Missing data name (dataname)",E_USER_ERROR);
if (!isset($parameters['analysisname']) or ($parameters['analysisname'] == ''))
  trigger_error("Missing analysis name (analysisname)",E_USER_ERROR);
  
$dataname = $parameters['dataname'];
$analysisname = $parameters['analysisname']; 

$step = $settings['db_step'];

try {
  $fout = fopen($settings['analyses_path'].$dataname.'/'.$dataname.'.csv','w+');		//*************************
} catch(Exception $e) {
   trigger_error($e->getMessage(),E_USER_ERROR);
}

try {
	/*$anal_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$analysisname.'/'.$analysisname.'.sqlite3');	//**************************
	$anal_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);*/

	$data_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$dataname.'.sqlite3');	//*******************************
	$data_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

	//count
	$query = "SELECT count(*) as count FROM mp_vote_full
	 ORDER BY CAST(mp_code AS INTEGER),CAST(division_code AS INTEGER)";
	$q = $data_db->prepare($query);
	$q->execute();
	$count = $q->fetchAll();
	$count = $count[0]['count'];
	$query = "
		 SELECT mp_code,division_code,vote_meaning_code FROM mp_vote_full
		 ORDER BY CAST(mp_code AS INTEGER),CAST(division_code AS INTEGER)
		 LIMIT ? OFFSET ?
		";
	$q = $data_db->prepare($query);
	
	for ($i = 1; $i <= ceil($count/$step); $i++) {
		$offset = $step*($i-1);

		$q->execute(array($step,$offset));
		$result = $q->fetchAll();


		foreach($result as $row) {
		  fputcsv($fout,array($row['mp_code'],$row['division_code'],$row['vote_meaning_code']));
		}


	}

}
catch(PDOException $e) {
    // Print PDOException message
   trigger_error($e->getMessage(),E_USER_ERROR);
}

fclose($fout);


?>
