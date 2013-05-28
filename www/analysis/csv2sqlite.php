<?php
//insert csv into db
//shall not be called directly

if (!isset($parameters['filename']) or ($parameters['filename'] == ''))
  trigger_error("Missing file name (filename)",E_USER_ERROR);

$fname = $parameters['filename'];
$dataname = $parameters['dataname'];
try {
  $fin = fopen($settings['file_path'].$fname,'r');
}
catch(Exception $e) {
  trigger_error("Cannot open $fname. Exception: " . $e->getMessage(),E_USER_ERROR);
}

$step = $settings['db_step'];

try {
// Create (connect to) SQLite database in file
    //$file_db = new SQLiteDatabase('test.sqlite', 0666, $error);
    $tmp_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$dataname.'.sqlite3');	//***************
    // Set errormode to exceptions
    $tmp_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    //
    $query = "
      CREATE TABLE IF NOT EXISTS all_vote(
      mp_code BLOB NOT NULL,
      division_code BLOB NOT NULL,
      divided_on TEXT,
	  vote_kind_code BLOB NOT NULL,
	  division_name TEXT,
	  group_name TEXT,
	  mp_name TEXT,
	  	  CONSTRAINT mp_vote_pkey PRIMARY KEY (mp_code , division_code )
	  )
    ";
    $tmp_db->exec($query);
}
catch(PDOException $e) {
    // Print PDOException message
   trigger_error($e->getMessage(),E_USER_ERROR);
}
$i = 0;
$ar = array();
while (($row = fgetcsv($fin, 0, ",")) !== FALSE) {
 if (!isset($parameters['fileheader']) or !($parameters['fileheader']) or ($i > 0)) {
   $ar[] = $row;
 }
 //echo $i;
 //print_r($ar);
  $columns = implode(",",$parameters['filecolumns']);
 if ($i > $step) {
   sqlite_bulk_import($tmp_db,$ar,$columns);
   $ar = array();
   $i = 1;
 }
 $i++;
 //if ($i>10000) break;
}

sqlite_bulk_import($tmp_db,$ar,$columns);


function sqlite_bulk_import($tmp_db,$ar,$columns) {
  try{
	$tmp_db->exec("PRAGMA synchronous=OFF");
	$tmp_db->exec("PRAGMA count_changes=OFF");
	$tmp_db->exec("PRAGMA journal_mode=MEMORY");
	$tmp_db->exec("PRAGMA temp_store=MEMORY");
	
	$p = $tmp_db->prepare("INSERT INTO all_vote({$columns}) VALUES(?,?,?,?,?,?,?)");
	
	$tmp_db->exec("BEGIN TRANSACTION");
	
	foreach ($ar as $row) {
	  $p->execute($row);
	} 

	$tmp_db->exec("COMMIT TRANSACTION");
  }
	catch(PDOException $e) {
		// Print PDOException message
	   trigger_error($e->getMessage(),E_USER_ERROR);
	}

}

?>
