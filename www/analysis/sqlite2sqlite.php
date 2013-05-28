<?php
//insert values into correct db scheme
//shall not be called directly

if (!isset($parameters['filename']) or ($parameters['filename'] == ''))
  trigger_error("Missing file name (filename)",E_USER_ERROR);
$dataname = $parameters['dataname'];

$file_db = new PDO('sqlite:'.$settings['analyses_path'].$dataname.'/'.$dataname.'.sqlite3');
$file_db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

//mps
$query = "
  INSERT INTO mp (code,name) SELECT mp_code,mp_name FROM
  (SELECT mp_code, mp_name FROM all_vote
  GROUP BY mp_code, mp_name) as t1
";
$file_db->exec($query);

//divisions
$query = "
  INSERT INTO division (code,name, divided_on) SELECT division_code,division_name,divided_on FROM
  (SELECT division_code, division_name,max(divided_on) as divided_on FROM all_vote
  GROUP BY division_code, division_name) as t1
";
$file_db->exec($query);

//groups
$query = "SELECT distinct(group_name) FROM all_vote";
$sth = $file_db->prepare($query);
$sth->execute();
$result = $sth->fetchAll();
$query = '
    INSERT INTO "group" (code,name,short_name,color)
    VALUES (?,?,?,?)
';
$q = $file_db->prepare($query);
foreach ($result as $row) {
  $q->execute(array($row['group_name'],$row['group_name'],$row['group_name'],group2color($row['group_name'],$parameters['groups'],$parameters['colors'])));
}

//mp_votes
$str = '';
if (!isset($parameters['vote_for']))
  trigger_error('Missing "vote_for" code',E_USER_ERROR);
if (is_array($parameters['vote_for'])) {
  foreach($parameters['vote_for'] as $row) {
    $str .= " WHEN '{$row}' THEN 1";
  }
} else
	$str .= " WHEN '{$parameters['vote_for']}' THEN 1";
	
if (!isset($parameters['vote_against']))
  trigger_error('Missing "vote_against" code',E_USER_ERROR);
if (is_array($parameters['vote_against'])) {
  foreach($parameters['vote_against'] as $row) {
    $str .= " WHEN '{$row}' THEN -1";
  }
} else
	$str .= " WHEN '{$parameters['vote_against']}' THEN -1";

if (isset($parameters['vote_neutral'])) {
  if (is_array($parameters['vote_neutral'])) {
    foreach($parameters['vote_neutral'] as $row) {
      $str .= " WHEN '{$row}' THEN 0";
    }
  } else
	$str .= " WHEN '{$parameters['vote_neutral']}' THEN 0";
}

$query = "
  INSERT INTO mp_vote (mp_code,division_code,vote_meaning_code,group_code) 
  SELECT 
    mp_code,
    division_code,
    CASE vote_kind_code {$str} ELSE 'NA' END,
    group_name
  FROM all_vote
";

$file_db->exec($query);


function group2color($group,$groups,$colors){

  if (is_array($groups)) {
    $key = array_search($group,$groups);
    if (!($key === FALSE)) return "#".$colors[$key];
    else return "#808080";
  } else {
    if (isset($groups)) {
      return "#".$colors;
    }
    else return "#808080";
  }
}

?>
