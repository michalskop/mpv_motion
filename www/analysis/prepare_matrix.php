<?php
//prepare matrix for R
//shall not be called directly

if (!isset($parameters['dataname']) or ($parameters['dataname'] == ''))
  trigger_error("Missing data name (dataname)",E_USER_ERROR);
if (!isset($parameters['analysisname']) or ($parameters['analysisname'] == ''))
  trigger_error("Missing analysis name (analysisname)",E_USER_ERROR);
  
$dataname = $parameters['dataname'];
$analysisname = $parameters['analysisname']; 

try {
	/*$anal_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$analysisname.'/'.$analysisname.'.sqlite3');	//**************************
	$anal_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);*/

	$data_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$dataname.'.sqlite3');	//*******************************
	$data_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

	$query = "
	  CREATE TABLE IF NOT EXISTS mp_vote_full(
	  mp_code BLOB NOT NULL,
	  division_code BLOB NOT NULL,
	  vote_meaning_code BLOB NOT NULL,
	  group_code BLOB,
	  	  CONSTRAINT mp_vote_pkey PRIMARY KEY (mp_code,division_code )
	  )
	";
	$data_db->exec($query);

	$query = "
	  INSERT INTO mp_vote_full (mp_code,division_code,vote_meaning_code,group_code) 
	  SELECT 
		t1.mp_code,
		t1.division_code,
		CASE WHEN mv.vote_meaning_code is null then 'NA' ELSE mv.vote_meaning_code END,
		mv.group_code FROM
		  (SELECT d.code as division_code,m.code as mp_code
		  FROM division as d 
		  CROSS JOIN mp as m) as t1
	  LEFT JOIN 
	  mp_vote as mv
	  ON mv.division_code=t1.division_code AND mv.mp_code=t1.mp_code
	  ";
	  
	$q = $data_db->prepare($query);
	$q->execute();

}
catch(PDOException $e) {
    // Print PDOException message
   trigger_error($e->getMessage(),E_USER_ERROR);
}
?>
