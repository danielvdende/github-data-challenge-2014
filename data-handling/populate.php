<?php
include 'config.php';

function fetch_from_github($url){
	// This function assumes the url for the api call has been correctly constructed
	global $username;
	global $password;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_USERAGENT,$username); 
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,1);
	curl_setopt($ch,CURLOPT_USERPWD, $username . ':'. $password);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	$content = curl_exec($ch);
	// get the response header
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header = substr($content, 0, $header_size);
	// get the response body
	$body = substr($content, $header_size);
	return [
		"body" => $body,
		"header" => $header
		];
}

function fetch_page_of_users($url){
	global $languages;
	echo "fetching page: " . $url . "\n";

	// this function takes the paginated url to obtain the users from github.
	$result = fetch_from_github($url);
	$header = $result["header"];
	$body = json_decode($result["body"], true);

	// first, we handle the body content
	for ($i=0; $i < count($body); $i++) { 

		// per user
		$user = $body[$i];
	// $user = $body;
		$dbOject = [];
		$dbObject["login"] = $user["login"];
		// set all counts to 0
		for($k=0; $k < count($languages); $k++){
			$dbObject[$languages[$k]] = 0;
		}
		echo "Handling user: " . $dbObject["login"] . "\n";
		// now we have to use that login to find the repositories of that user.
		$repos = fetch_from_github("https://api.github.com/users/".$dbObject["login"]."/repos");
		$reposBody = $repos["body"];
		$reposBody = json_decode($reposBody, true);
		for ($j=0; $j < count($reposBody); $j++) { 
			$lang = strtolower($reposBody[$j]["language"]);
			if(in_array($lang, $languages)){
				$dbObject[$lang] +=1;
			}

		}

		// var_dump($dbObject);
		writeUserToDb($dbObject);
		echo "db write complete .\n";
		// sleep(1);

	}
	$nextUrl = "";

	// now we have to fetch the new url from the header. Use string handling for this.
	foreach (explode("\r\n",$header) as $hdr){
		if(strpos($hdr, "Link:") !== FALSE){
			$res = explode(",", $hdr);
			for ($i=0; $i < count($res) ; $i++) { 
				if(strpos($res[$i], "next") !== FALSE){
					$parsed = str_replace("Link: <", "", $res[$i]);
					$parsed = str_replace(">; rel=\"next\"", "", $parsed);
					$nextUrl = $parsed;
				}
			}
			
		}
	}
	return $nextUrl;

}

function writeUserToDb($obj){
	global $dbpass;
	global $dbuser;
	$dbhost="localhost";
	$dbname="github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "INSERT INTO languages (";
	$keys = array_keys($obj);
	// first add the column names.
	for ($i=0; $i < count($keys); $i++) { 
		$sql .= "`".$keys[$i] . "`,";
	}
	$sql = rtrim($sql, ",");
	$sql .= ") VALUES (";
	for ($j=0; $j < count($keys); $j++) { 
		$sql .= "'".$obj[$keys[$j]] . "',";
	}
	$sql = rtrim($sql, ",");
	$sql .= ")";
	try{
		$stmt = $dbh->query($sql);
	} catch(PDOException $e){
		echo "db write error" . $e . "\n";
	}
}

// $test = "https://api.github.com/users/danielvdende/repos";
// $result = fetch_from_github($test);
// $body = json_decode($result["body"], true);
// for ($i=0; $i < count($body); $i++) { 
// 	echo "REPO" . $body[$i]["name"] . " LANGUAGE:" . strtolower($body[$i]["language"]) . "<br />";
// }

$result = fetch_page_of_users("https://api.github.com/users?since=25970");
while($result != ""){
	echo "moving to next page \n";
	$result = fetch_page_of_users($result);
}
echo "All done! \n";

// function get_users_from_github($url) {
// 	global $total;
// 	$ch = curl_init();
// 	curl_setopt($ch,CURLOPT_URL,$url);
// 	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
// 	curl_setopt($ch,CURLOPT_USERAGENT,'danielvdende'); 
// 	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,1);
// 	curl_setopt($ch,CURLOPT_USERPWD, 'danielvdende:dinkytoys1');
// 	curl_setopt($ch, CURLOPT_HEADER, 1);
// 	$content = curl_exec($ch);
// 	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
// 	$header = substr($content, 0, $header_size);
// 	$body = substr($content, $header_size);
// 	$nextUrl = "";
// 	foreach (explode("\r\n",$header) as $hdr){
// 		if(strpos($hdr, "Link:") !== FALSE){
// 			$res = explode(",", $hdr);
// 			for ($i=0; $i < count($res) ; $i++) { 
// 				if(strpos($res[$i], "next") !== FALSE){
// 					$parsed = str_replace("Link: <", "", $res[$i]);
// 					$parsed = str_replace(">; rel=\"next\"", "", $parsed);
// 					$nextUrl = $parsed;
// 				}
// 			}
			
// 		}
// 	}
// 	curl_close($ch);
// 	$users = json_decode($body, true);
// 	// var_dump($users);
// 	array_merge($total, $users);
// 	return $nextUrl;
// }

// $result = get_users_from_github("https://api.github.com/users?since=0");
// while($result != ""){
// 	echo "\n" . explode("=", $result)[1];
// 	$result = get_users_from_github($result);
// }
// echo "DONE";
	// echo "\n";
	// echo $result[$i]["login"];

?>