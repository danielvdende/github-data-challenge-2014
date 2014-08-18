<?php
include 'config.php';
function array_cartesian() {
    $_ = func_get_args();
    if(count($_) == 0)
        return array(array());
    $a = array_shift($_);
    $c = call_user_func_array(__FUNCTION__, $_);
    $r = array();
    foreach($a as $v)
        foreach($c as $p)
            $r[] = array_merge(array($v), $p);
    return $r;
}

$languages = [
"javascript",
"ruby",
"java",
"php",
"python",
"c++",
"c",
"objective-c",
"c#",
"shell",
"css",
"perl",
"coffeescript",
"viml",
"scala",
"go",
"prolog",
"clojure",
"haskell",
"lua"
];

$cartesian = array_cartesian(
   	$languages,
    $languages
);

for($i = 0; $i < count($cartesian); $i++){
	fetch_row_count($cartesian[$i][0], $cartesian[$i][1]);
}

function fetch_row_count($lang1, $lang2){
	global $dbpass;
	global $dbuser;
	$dbhost = "localhost";
	$dbname = "github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "SELECT COUNT(*) FROM languages WHERE `" . $lang1 . "`=1 AND `" . $lang2 . "`=1";
	try{
		$stmt = $dbh->query($sql);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo $lang1 . "|" . $lang2 . "|" .  $results[0]["COUNT(*)"] . "\n";
		$index = $lang1 . "|" . $lang2;
		writeToDatabase($index, $results[0]["COUNT(*)"]);
	} catch(PDOException $e){
		echo "db write error" . $e . "\n";
	}
}

function writeToDatabase($index, $value){
	global $dbpass;
	global $dbuser;
	$dbhost="localhost";
	$dbname="github_vis";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$sql = "INSERT INTO `cross-languages` (`languages`,`count`) VALUES('".$index."', '" . $value . "')";
	try{
		$stmt = $dbh->query($sql);
	} catch(PDOException $e){
		echo "db write error" . $e . "\n";
	}
}

?>