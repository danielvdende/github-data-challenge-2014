<?php
include 'config.php';
if(isset($_POST["data"])){
	$data = json_decode($_POST["data"]);
	if($data->callType == "init"){
		fetch_chord_data();
	} else if($data->callType == "usernameSearch"){
		fetch_user_data($data->username);
	} else if($data->callType == "languageSearch"){
		// replace cp by c++
		$data->languages = array_replace($data->languages,
		    array_fill_keys(
		        array_keys($data->languages, "cp"),
		        "c++"
		    )
		);
		fetch_users_by_languages($data->languages);
	} else if($data->callType == "languageSearchLimited"){
		// replace cp by c++
		$data->languages = array_replace($data->languages,
		    array_fill_keys(
		        array_keys($data->languages, "cp"),
		        "c++"
		    )
		);
		fetch_users_by_languages_limited($data->languages);
	}
}

function fetch_chord_data(){
	global $dbuser;
	global $dbpass;
	$dbhost = "localhost";
	$dbname = "github_vis";

	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	

	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$sql = "SELECT * FROM `cross-languages`";
	try{
		$final = [];
		$totals = [];
		$stmt = $dbh->query($sql);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		for($i = 0; $i < count($results); $i++){
			$key = $results[$i]["languages"];
			$values = explode("|", $key);
			if($values[0] == $values[1]){
				$final[$values[0]][$values[1]] = 0;
				$totals[$values[0]] = $results[$i]["count"];
			} else{
				$final[$values[0]][$values[1]] = $results[$i]["count"];
			}
		}
		$array = [
			"finals" => $final,
			"totals" => $totals
		];
		echo json_encode($array);
	} catch(PDOException $e){
		echo "db write error" . $e . "\n";
	}
}

function fetch_user_data($username){
	global $dbuser;
	global $dbpass;
	$dbhost = "localhost";
	$dbname = "github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "SELECT * FROM `languages` WHERE `login`=?";
	try {
		$query = $dbh->prepare($sql);
		$query->execute(array($username));		
		$results = $query->fetchAll();
		$user = $results[0];
		$languages = [];
		foreach ($user as $key => $value) {
			if($value == 1 && gettype($key) == "string" && $key != "login"){
				array_push($languages, $key);
			}
		}
		echo json_encode($languages);
	} catch(PDOException $e){
		echo "db fetch error" . $e . "\n";
	}

}

function fetch_users_by_languages($languages){
	global $dbuser;
	global $dbpass;
	$dbhost = "localhost";
	$dbname = "github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "SELECT login FROM `languages` WHERE ";
	for($i=0; $i < count($languages) - 1; $i++){
		$sql .= "`" . $languages[$i] . "`=1 AND ";
	}
	$sql .= "`" . $languages[count($languages)-1] . "`=1";
	try {
		$query = $dbh->prepare($sql);
		$query->execute();		
		$results = $query->fetchAll();
		
		$users = [];
		for($j=0; $j < count($results); $j++){
			array_push($users, $results[$j]["login"]);
		}
		$list = [
			"users" => $users,
			"total" => count($users)
		];
		echo json_encode($list);
	} catch(PDOException $e){
		echo "db fetch error" . $e . "\n";
	}
}

function fetch_users_by_languages_limited($languages){
	global $dbuser;
	global $dbpass;
	$dbhost = "localhost";
	$dbname = "github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "SELECT login FROM `languages` WHERE ";
	for($i=0; $i < count($languages) - 1; $i++){
		$sql .= "`" . $languages[$i] . "`=1 AND ";
	}
	$sql .= "`" . $languages[count($languages)-1] . "`=1";
	$sql .= " LIMIT 10";

	// send back the total number for creating correct pie chart.
	$sql2 = "SELECT count(login) FROM `languages` WHERE ";
	for($i=0; $i < count($languages) - 1; $i++){
		$sql2 .= "`" . $languages[$i] . "`=1 AND ";
	}
	$sql2 .= "`" . $languages[count($languages)-1] . "`=1";
	
	try {
		$query = $dbh->prepare($sql);
		$query->execute();		
		$results = $query->fetchAll();
		
		$users = [];
		for($j=0; $j < count($results); $j++){
			array_push($users, $results[$j]["login"]);
		}
		// var_dump($users);

		$query = $dbh->prepare($sql2);
		$query->execute();
		$results = $query->fetchAll();
		$totalNumber = $results[0][0];

		$list = [
			"users"=>$users,
			"total"=>$totalNumber
		];
		echo json_encode($list);
	} catch(PDOException $e){
		echo "db fetch error" . $e . "\n";
	}
}
// echo "HELLO";
// fetch_chord_data();
// echo "bye";
// fetch_user_data("007lva");

// fetch_users_by_languages_limited(["javascript"]);


?>