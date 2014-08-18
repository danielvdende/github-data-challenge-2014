<?php
include 'config.php';

$file_handle = fopen("data/data.json", "r");
$counter = 0;
$total = 3113831;
while (!feof($file_handle) ) {
	$line_of_text = fgetcsv($file_handle, 1024,"\n");
	$data = explode(",", $line_of_text[0]);
	$user = $data[0];
	if($user !== "repository_language"){
		$language = strtolower($data[1]);
		if(in_array($language, $languages)){
			writeToDatabase($user, $language);
		}
	}
	
	
	$counter++;
	echo ($counter / $total) * 100 . " % done \n";
}
fclose($file_handle);

function writeToDatabase($user, $language){
	global $dbpass;
	global $dbuser;
	$dbhost="localhost";
	$dbname="github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "INSERT INTO `languages` (`login`,`".$language."`) VALUES('".$user."',1) ON DUPLICATE KEY UPDATE `".$language."`=1;";
	try{
		$stmt = $dbh->query($sql);
	} catch(PDOException $e){
		echo "db write error" . $e . "\n";
	}
}

?>