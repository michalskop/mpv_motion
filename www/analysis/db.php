<?php

//create SQLite DBs
//shall not be called directly



if (!isset($parameters['dataname']) or ($parameters['dataname'] == ''))
  trigger_error("Missing data name (dataname)",E_USER_ERROR);
if (!isset($parameters['analysisname']) or ($parameters['analysisname'] == ''))
  trigger_error("Missing analysis name (analysisname)",E_USER_ERROR);

$dataname = $parameters['dataname'];
$analysisname = $parameters['analysisname'];

try {
    /**************************************
    * Create databases and                *
    * open connections                    *
    **************************************/
 
    // Create (connect to) SQLite database in file
    //$file_db = new SQLiteDatabase('test.sqlite', 0666, $error);
    if(!file_exists($settings['analyses_path'].$dataname)) {
		mkdir($settings['analyses_path'].$dataname);
	}

    $file_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$dataname.'.sqlite3');
    // Set errormode to exceptions
    $file_db->setAttribute(PDO::ATTR_ERRMODE, 
                            PDO::ERRMODE_EXCEPTION);
 
                              
    // Create tables for dataset
    
    //mp
    $query = "CREATE TABLE IF NOT EXISTS mp(
      code BLOB PRIMARY KEY,
      name TEXT,
      color TEXT,
      weight REAL
      )
    ";
    $file_db->exec($query);
    
    //division
    $query = "CREATE TABLE IF NOT EXISTS division(
      code BLOB PRIMARY KEY,
  	  name TEXT,
      divided_on TEXT
     )
    ";
    $file_db->exec($query);
    
    //mp_vote
    $query = "
      CREATE TABLE IF NOT EXISTS mp_vote(
      mp_code BLOB NOT NULL,
      division_code BLOB NOT NULL,
	  vote_meaning_code BLOB NOT NULL,
	  group_code BLOB,
	  	  CONSTRAINT mp_vote_pkey PRIMARY KEY (mp_code , division_code )
	  )
    ";
    $file_db->exec($query);
    
    //vote_meaning
    $query = "
      CREATE TABLE IF NOT EXISTS vote_meaning(
        source_code BLOB PRIMARY KEY,
        code BLOB NOT NULL
	  )
    ";
    $file_db->exec($query);
    
    //group
    $query = '
      CREATE TABLE IF NOT EXISTS "group"(
      code BLOB PRIMARY KEY,
      name BLOB,
      short_name TEXT,
      color TEXT
      )
    ';
    $file_db->exec($query);
    
    
    //create tables for an analysis
    if(!file_exists($settings['analyses_path'].$dataname.'/'.$analysisname)) {
		mkdir($settings['analyses_path'].$dataname.'/'.$analysisname);
	}
    $anal_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$analysisname.'/'.$analysisname.'.sqlite3');	
    // Set errormode to exceptions
    $anal_db->setAttribute(PDO::ATTR_ERRMODE, 
                            PDO::ERRMODE_EXCEPTION);
    //info
    $query = "CREATE TABLE IF NOT EXISTS analysis_info(
      data TEXT,
      name BLOB,
      method BLOB,
      number_of_dimensions INTEGER,
      orientation BLOB
    )
    ";
    $anal_db->exec($query);
    
    //intervals
    $query = '
      CREATE TABLE IF NOT EXISTS analysis_interval(
      name BLOB PRIMARY KEY,
      since BLOB,
      until BLOB
      )
    ';
    $anal_db->exec($query);
    
    $query = '
      CREATE TABLE IF NOT EXISTS analysis_division_in_interval(
        division_code BLOB NOT NULL,
        interval_name BLOB NOT NULL
      )
    ';
    $anal_db->exec($query);
                              
                              
}
catch(PDOException $e) {
    // Print PDOException message
   trigger_error($e->getMessage(),E_USER_ERROR);
}

?>
