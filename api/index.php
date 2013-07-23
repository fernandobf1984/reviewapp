<?php
 
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();


$app->get('/speakers', 'getSpeakers');
$app->get('/speakers/:id',  'getSpeaker');
$app->get('/speakers/search/:query', 'findSpeakerByName');

$app->get('/speakers/reviews/:id', 'getReviewSpeaker');
$app->post('/signup', 'registerUser');
$app->post('/signin', 'loginUser');
$app->post('/speakers/vote', 'voteSpeaker');
$app->post('/speakers/review', 'reviewSpeaker');

$app->post('/bespeaker', 'beSpeaker');
$app->post('/savespeaker', 'saveSpeaker');
$app->post('/addspeaker', 'addSpeaker');

$app->post('/speakers/isvoted', 'checkUserVote');


//$app->post('/linkedin/1', 'linkedinFirstStep');
//$app->post('/linkedin/2', 'linkedinSecondStep');
$app->post('/linkedin/3', 'linkedinLastStep');


$app->get('/countries',  'getCountries');
$app->get('/regions/:id',  'getRegions');
$app->get('/cities/:id',  'getCities');

 
$app->run();




function saveSpeaker() {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $user = json_decode($request->getBody());
    
	if($user->user_id != "" && $user->user_id != 0){
		$s_query_aux = "";
		if(@isset($user->country)){
			if($user->country != "-"){
				$s_query_aux .= ", country_id = ".$user->country."";
			}
			if($user->region != "-"){
				$s_query_aux .= ", region_id = ".$user->region."";
			}
			if($user->city != "-"){
				$s_query_aux .= ", city_id = ".$user->city."";
			}
		}
		$sql = "UPDATE tbl_users SET name = :name, surname = :surname, company = :company ".$s_query_aux." WHERE user_id = :user_id";
		
		deleteSkillsSpeaker($user->user_id);
		$type = "update";
	}else{
		$sql = "INSERT INTO tbl_users (name, company, surname, speaker) VALUES (:name, :company, 1)";
		$type = "insert";
	}
	
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("name", strip_tags($user->name));
		$stmt->bindParam("surname", strip_tags($user->surname));
		$stmt->bindParam("company", strip_tags($user->company));
		
		if($type != "insert"){
			$stmt->bindParam("user_id", strip_tags($user->user_id));
		}
		$stmt->execute();
		if($type == "insert"){
			$s_msg = $db->lastInsertId();
			$user->user_id = $db->lastInsertId();
		}else{
			$s_msg = "ok";
		}
		$db = null;
		
	}catch(PDOException $e){
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
	
	$a_skills = json_decode($user->skills);
	
	if(count($a_skills) >= 1){
			
		foreach($a_skills as $id=>$value){
			$id_skill = insertSkill($value);
			if($id_skill != 0){
				addSkillSpeaker($id_skill,$user->user_id);
			}
		}
	}
	
	die($s_msg);
}

function deleteSkillsSpeaker($user_id){
	$sql = "DELETE FROM tbl_skills_users WHERE user_id = :user_id";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("user_id", strip_tags($user_id));
        $stmt->execute();
        $db = null;
        return true;
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
        return false;
    }
}

function insertSkill($s_skill){
	$sql = "SELECT * FROM tbl_skills WHERE name like '".trim(strip_tags($s_skill))."'";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $skill = $stmt->fetchObject();
        
        if(!$skill){
			$sql = "INSERT INTO tbl_skills (name) VALUES (:name)";
			$stmt = $db->prepare($sql);
			$stmt->bindParam("name", strip_tags($s_skill));
			$stmt->execute();
			$skill_id = $db->lastInsertId();
		}else{
			$skill_id = $skill->skill_id;
		}
		
		$db = null;
        
        return $skill_id;
    } catch(PDOException $e) {
		return 0;
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

function addSkillSpeaker($skill_id,$user_id){
	$sql = "SELECT * FROM tbl_skills_users WHERE skill_id = :skill_id AND user_id = :user_id";
    try {
		$db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("skill_id", strip_tags($skill_id));
        $stmt->bindParam("user_id", strip_tags($user_id));
        $stmt->execute();
        
        $skill = $stmt->fetchObject();
        if(!$skill){
			$sql = "INSERT INTO tbl_skills_users (skill_id,user_id) VALUES (:skill_id, :user_id)";
			
			$stmt = $db->prepare($sql);
			$stmt->bindParam("skill_id", strip_tags($skill_id));
			$stmt->bindParam("user_id", strip_tags($user_id));
			$stmt->execute();
			//die($db->lastInsertId());
		}
		$db = null;
		
        return true;
    } catch(PDOException $e) {
		return false;
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}


 
function getSpeakers() {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $sql = "SELECT * FROM tbl_users WHERE speaker = 1 ORDER BY name";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $speakers = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo '{"speaker": '.json_encode($speakers).'}';
    } catch(PDOException $e) {
        echo '{"error":{"text":'.$e->getMessage().'}}';
    }
}
 
 
function getSpeaker($id) {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $sql = "SELECT * FROM tbl_users WHERE user_id=:id";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", strip_tags($id));
        $stmt->execute();
        $speaker = $stmt->fetchObject();
        //$db = null;
        
        $s_location = "";
        if($speaker->country_id != 0){
			$s_location = getLocationName($speaker->country_id);
			if($speaker->region_id != 0){
				$s_location = getLocationName($speaker->region_id).", ".$s_location.".";
				if($speaker->city_id != 0){
					$s_location = getLocationName($speaker->city_id).", ".$s_location;
				}
			}
		}
		$speaker->location = $s_location;
        
        $sql = "SELECT s.name as skill_name FROM tbl_skills_users su INNER JOIN tbl_skills s ON su.skill_id = s.skill_id WHERE su.user_id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", strip_tags($id));
        $stmt->execute();
        $skills = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        $i = 1;
        $s_skills = "";
        $a_skills = array();
        foreach($skills as $skill){
			$a_skills[$i] = $skill->skill_name;
			$i++;
		}
		$speaker->skills = $a_skills;
		
        echo json_encode($speaker);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}
 

function addSpeaker() {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $speaker = json_decode($request->getBody());
    $sql = "INSERT INTO tbl_users (name, surname, speaker) VALUES (:name, :surname, 1)";
    //die($sql);
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("name", strip_tags($speaker->name));
        $stmt->bindParam("surname", strip_tags($speaker->surname));
        $stmt->execute();
        $speaker->user_id = $db->lastInsertId();
        $db = null;
        
        $a_skills = json_decode($speaker->skills);
	
		if(count($a_skills) >= 1){
				
			foreach($a_skills as $id=>$value){
				$id_skill = insertSkill($value);
				if($id_skill != 0){
					addSkillSpeaker($id_skill,$speaker->user_id);
				}
			}
		}
        die($speaker->user_id);
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}
// curl -i -X POST -H 'Content-Type: application/json' -d '{"name": "Nuevo Speaker", "company": "2009"}' http://www.prueba.com/speakers
 

function findSpeakerByName($query) {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $sql = "SELECT * FROM tbl_users WHERE (UPPER(name) LIKE :query OR UPPER(surname) LIKE :query) AND speaker = 1 ORDER BY name";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $query = "%".strip_tags($query)."%";
        $stmt->bindParam("query", $query);
        $stmt->execute();
        $speakers = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo '{"speaker": ' . json_encode($speakers) . '}';
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}



function registerUser() {
	
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    
    $user = json_decode($request->getBody());
    
    $sql = "SELECT * FROM tbl_users WHERE email = :email";
    //echo"<pre>";print_r($user);die;
    try {
		$db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("email", strip_tags(trim($user->email)));
        $stmt->execute();
        
        $pre_user = $stmt->fetchObject();
        
        if(!$pre_user){
			$sql = "INSERT INTO tbl_users (name, surname, email, company, password, country_id, region_id, city_id) VALUES (:name, :surname, :email, :company, :password, :country, :region, :city)";
			
			$stmt = $db->prepare($sql);
			$stmt->bindParam("name", strip_tags(trim($user->name)));
			$stmt->bindParam("surname", strip_tags(trim($user->surname)));
			$stmt->bindParam("email", strip_tags(trim($user->email)));
			$stmt->bindParam("company", strip_tags(trim($user->company)));
			$stmt->bindParam("password", strip_tags($user->password));
			if($user->country == "-"){
				$country = 0;
			}else{
				$country = $user->country;
			}
			$stmt->bindParam("country", strip_tags($country));
			if($user->region == "-"){
				$region = 0;
			}else{
				$region = $user->region;
			}
			$stmt->bindParam("region", strip_tags($region));
			if($user->city == "-"){
				$city = 0;
			}else{
				$city = $user->city;
			}
			$stmt->bindParam("city", strip_tags($city));
			
			$stmt->execute();
			
			$user_id = $db->lastInsertId();
			
			$db = null;
			
			die($user_id);
		}else{
			die("mail");
        }
    } catch(PDOException $e) {
		die('0');
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}


function loginUser(){
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    
    $sql = "SELECT * FROM tbl_users WHERE email = :email AND password = :password";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("email", strip_tags($data->email));
        $stmt->bindParam("password", strip_tags($data->password));
        $stmt->execute();
        $user = $stmt->fetchObject();
        $db = null;
        
        if($user){
			$result = array();
			$result["user_id"] = $user->user_id;
			if($user->speaker){
				$result["speaker"] = "yes";
			}else{
				$result["speaker"] = "no";
			}
			
			die(json_encode($result));
		}else{
			die('0');
		}
        
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}
//curl -i -X POST -H 'Content-Type: application/json' -d '{"email": "m@m.es","password":"asamajala"}' http://www.prueba.com/signin

 
function voteSpeaker(){
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    
    $sql = "SELECT * FROM tbl_califications WHERE user_id = :user_id AND speaker_id = :speaker_id";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("user_id", strip_tags($data->user_logged));
        $stmt->bindParam("speaker_id", strip_tags($data->user_id));
        $stmt->execute();
        $result = $stmt->fetchObject();
        $db = null;
        
        if($result){
			die("ko");
		}else{
			$sql = "INSERT INTO tbl_califications (user_id, speaker_id, expertise, engagement, clarity) VALUES (:user_id, :speaker_id, :expertise, :engagement, :clarity)";
		
			$db = getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("user_id", strip_tags($data->user_logged));
			$stmt->bindParam("speaker_id", strip_tags($data->user_id));
			$stmt->bindParam("expertise", strip_tags($data->expertise));
			$stmt->bindParam("engagement", strip_tags($data->engagement));
			$stmt->bindParam("clarity", strip_tags($data->clarity));
			$stmt->execute();
			$db = null;
			die("ok");
		}
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    
}


function reviewSpeaker() {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
	
	if(trim($data->review) != ""){
		
		$request = \Slim\Slim::getInstance()->request();
		$data = json_decode($request->getBody());
		//$sql = "UPDATE tbl_califications SET review = '".$data->review."' WHERE user_id = ".$data->user_logged." AND speaker_id = ".$data->user_id;
		$sql = "UPDATE tbl_califications SET review = :review WHERE user_id = :user_id AND speaker_id = :speaker_id";
		try {
			$db = getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("user_id", strip_tags($data->user_logged));
			$stmt->bindParam("speaker_id", strip_tags($data->user_id));
			$stmt->bindParam("review", strip_tags($data->review));
			
			$stmt->execute();
			$db = null;
		} catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}
	die("ok");
}


function getReviewSpeaker($speaker_id) {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    
    $sql = "SELECT sum(expertise) as exp, sum(engagement) as eng, sum(clarity) as cla, count(id) as total FROM tbl_califications t WHERE speaker_id = :speaker_id";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("speaker_id", strip_tags($speaker_id));
        $stmt->execute();
        
        $votes = $stmt->fetchObject();
         
        if($votes->total > 0){
			$total = ($votes->exp + $votes->eng + $votes->cla) / ($votes->total * 3);
			if($total < 0.5){
				$total = 0;
			}elseif($total < 1.5){
				$total = 1;
			}elseif($total < 2.5){
				$total = 2;
			}elseif($total < 3.5){
				$total = 3;
			}elseif($total < 4.5){
				$total = 4;
			}else{
				$total = 5;
			}
		}else{
			$total = 0;
		}
        
        $sql = "SELECT u.name, u.surname, cal.date, cal.review FROM tbl_califications cal INNER JOIN tbl_users u ON cal.user_id = u.user_id WHERE cal.speaker_id = :speaker_id AND cal.review IS NOT NULL ORDER BY date DESC";

		$db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("speaker_id", strip_tags($speaker_id));
        $stmt->execute();
        
        $reviews = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $db = null;
        
        echo '{"votes": "'.$total.'","reviews":'.json_encode($reviews).'}';
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}


function beSpeaker() {
	
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $user = json_decode($request->getBody());
	$sql = "UPDATE tbl_users SET speaker= :speaker WHERE user_id= :user_id";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("user_id", strip_tags($user->user_id));
        $stmt->bindParam("speaker", strip_tags($user->speaker));
        $stmt->execute();
        $db = null;
        if($user->speaker){
			die('yes');
		}else{
			die('no');
		}
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

function checkUserVote(){
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    //echo"<pre>";print_r($data);die;
     
    $sql = "SELECT * FROM tbl_califications WHERE user_id = :user_id AND speaker_id = :speaker_id";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("user_id", strip_tags($data->user_id));
        $stmt->bindParam("speaker_id", strip_tags($data->speaker_id));
        $stmt->execute();
        $result = $stmt->fetchObject();
        $db = null;
        
         
        if($result){
			die("ko");
		}else{
			die("ok");
		}
    } catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
	
}

/*
 * DEPRECATED:
 * This functions was for the old linkedin authentication(with PIN).
 * (linkedinFirstStep,linkedinSecondStep)
 * 
function linkedinFirstStep(){
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    
    $result = file_get_contents($data->url);
    
    die($result);
}


function linkedinSecondStep(){
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    
    $result = file_get_contents($data->url);
    die($result);
}
*/



function linkedinLastStep(){
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    
    $xml = file_get_contents($data->url);
    
    $result = new SimpleXMLElement($xml);

	$resultado = $result->xpath('/person');

	$a_person = (array)$resultado[0];
	
	$s_name = $a_person["first-name"];
	$s_surname = $a_person["last-name"];
	$s_email = $a_person["email-address"];
	
	$a_url = (array)$a_person["site-standard-profile-request"][0];
	$a_url_aux = explode('?',$a_url["url"]);
	$a_url_aux = explode('&',$a_url_aux[1]);
	$a_url_aux = explode('=',$a_url_aux[0]);
	$s_id = $a_url_aux[1];
	
    $sql = "SELECT * FROM tbl_users WHERE linkedin_id = :linkedin_id";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("linkedin_id", $s_id);
        $stmt->execute();
        $result = $stmt->fetchObject();
        $db = null;
        
        if($result){
			if($result->speaker == 1){
				$is_speaker = "yes";
			}else{
				$is_speaker = "no";
			}
			echo '{"user_id": "'.$result->user_id.'","speaker": "'.$is_speaker.'","type":"signin"}';
		}else{
			$sql = "INSERT INTO tbl_users (name, surname, email, linkedin_id) VALUES (:name, :surname, :email, :linkedin_id)";
		
			$db = getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("name", $s_name);
			$stmt->bindParam("surname", $s_surname);
			$stmt->bindParam("email", $s_email);
			$stmt->bindParam("linkedin_id", $s_id);
			$stmt->execute();
			$user_id = $db->lastInsertId();
			$db = null;
			
			echo '{"user_id": "'.$user_id.'","type":"signup"}';
		}
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

 
function getCountries() {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $sql = "SELECT id,local_name FROM meta_location WHERE type = 'CO' ORDER BY local_name ASC";
    try {
        $db = getConnection();
        $stmt = $db->query($sql);
        $countries = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo '{"countries": '.json_encode($countries).'}';
    } catch(PDOException $e) {
        echo '{"error":{"text":'.$e->getMessage().'}}';
    }
}

 
function getRegions($id_country) {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	//die($id_country);
    $sql = "SELECT id,local_name FROM meta_location WHERE in_location = :in_location AND type = 'RE' ORDER BY local_name ASC";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
		$stmt->bindParam("in_location", $id_country);
		$stmt->execute();
        
        $regions = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        
        echo '{"regions": '.json_encode($regions).'}';
    } catch(PDOException $e) {
        echo '{"error":{"text":'.$e->getMessage().'}}';
    }
}

 
function getCities($id_region) {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $sql = "SELECT id,local_name FROM meta_location WHERE in_location = :in_location AND type = 'CI' ORDER BY local_name ASC";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
		$stmt->bindParam("in_location", $id_region);
		$stmt->execute();
        
        $cities = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        echo '{"cities": '.json_encode($cities).'}';
    } catch(PDOException $e) {
        echo '{"error":{"text":'.$e->getMessage().'}}';
    }
}


function getLocationName($id) {
	header('Access-Control-Allow-Origin: *');
	header('Content-type: application/xml');
	
    $sql = "SELECT local_name FROM meta_location WHERE id=:id";
    try {
        $db = getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindParam("id", strip_tags($id));
        $stmt->execute();
        $location = $stmt->fetchObject();
        return trim($location->local_name);
        $db = null;
    } catch(PDOException $e) {
        return false;
    }
}

 
function getConnection() {
    $dbhost = "127.0.0.1";
    $dbuser = "user";
    $dbpass = "pass";
    $dbname = "slim";
    $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
}
