<?php

/***************************************/
/* Author: Caue Garcia Polimanti	   */
/* Splitter App REST API        	   */
/***************************************/

	require 'vendor/autoload.php';
	

$app = new \Slim\App;

// GET route for the root
$app->get(
	'/',
	function(){
		echo "<h1>Splitter App</h1>";
		echo "<h1>REST API</h1>";
        //echo "<p>Beatriz Mestichelli | Cauê Polimanti | Victor Noronha </p>";
	}

);

// POST route to add a user
$app->post(
	'/user/add',
	function ($request, $response, $args) {
		$user = json_decode($request->getBody(),true);
		postAddUser($user['name'],$user['cpf'],$user['dateOfBirth'],$user['login'],
                    $user['phone'],$user['password']);
        //echo $user['name']."\n";
        //echo "\n".$user."\n";
	}
);

// GET route to get user by his email
$app->get(
	'/user/email/{email}',
	function ($request, $response, $args) {
		//echo "This is a GET route to request a user by his email. <br>";
		getUserByEmail($args['email']);
	}
);

// POST route to update a user
$app->post(
	'/user/update',
	function ($request, $response, $args) {
		$user = json_decode($request->getBody(),true);
		postUpdateUser($user['name'],$user['cpf'],$user['dateOfBirth'],$user['login'],
                    $user['phone'],$user['password']);
        //echo $user['name']."\n";
	}
);

// GET route to get user by his email
$app->post(
	'/user/deactivate/{email}',
	function ($request, $response, $args) {
		//echo "This is a GET route to request a user by his email. <br>";
		deactivateUserByEmail($args['email']);
        echo "User deactivated";
	}    
);

// GET route to get all users
$app->get(
	'/user',
	function ($request, $response, $args) {
		//echo "This is a GET route to request a user by his email. <br>";
		getAllUsers();
	}
);



$app->run();


// Function to create the connection to the Database
function getDB(){
    
    /* hard coded connection 
	$dbhost="us-cdbr-iron-east-04.cleardb.net:3306";
	$dbuser="bfc24b5ee38c88";
	$dbpass="6ffa6ae6";
	$dbname="ad_f2c4aad8eb6c7af";
    */
    
    //error reporting
    ini_set('display_errors',1);
    error_reporting(E_ALL | E_STRICT);

    //get connection parameters from sysenv
    $services = getenv("VCAP_SERVICES");
    $services_json = json_decode($services,true);
    $mysql_config = $services_json["cleardb"][0]["credentials"];

    $dbname = $mysql_config["name"];
    $dbhost = $mysql_config["hostname"];
    $dbport = $mysql_config["port"];
    $dbuser = $mysql_config["username"];
    $dbpass = $mysql_config["password"];
    
	// best way to protect the password above is using an external and import the password from that file

	//create a DB connection
	$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport);
	if ($conn->connect_error){
		die("Connection failed: " . $conn->connect_error . "\n");
	}

	return $conn;

}



// Function to add a new client, calling a procedure from DB, by passing all the user info
//postAddUser($user['id'],$user['login'],$user['password'],$user['name'],$user['dateOfBirth'],$user['cpf'],$user['phone']);
function postAddUser($name,$cpf,$dateOfBirth,$login,$phone,$pass){
	//$db = new mysqli("localhost:3306","root","","splitterdb");
	$db = getDB();
    
	// veryfing if the connection is successful
	if ($db->connect_errno) {
    echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
	}

	/* Prepared statement, stage 1: prepare */
	if (!($stmt = $db->prepare("CALL pr_criar_nova_conta (?,?,STR_TO_DATE(?,'%d/%m/%Y'),?,?,?,?)"))) {
    echo "Prepare failed: (" . $db->errno . ") " . $db->error;
	}

	/* Prepared statement, stage 2: bind and execute */
	//$id = 1;
	if (!$stmt->bind_param("sississ", $name, $cpf, $dateOfBirth, $login, $phone, $login, $pass)) {
	    echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
	}

	if (!$stmt->execute()) {
	    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
	}
	else{
		echo "User ".$name." Successfully Added";
	}

	$db->close();		// close connection to the database
}


// Function to get a specific user from database by his email
function getUserByEmail($email){
	$db = getDB();
	//$db = new mysqli("localhost","root","","splitterdb");
	
	// veryfing if the connection is successful
	if ($db->connect_errno) {
    echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
	}

	/* Prepared statement, stage 1: prepare */
	if (!($stmt = $db->prepare("CALL pr_buscar_conta(?)"))) {
    echo "Prepare failed: (" . $db->errno . ") " . $db->error;
	}

	/* Prepared statement, stage 2: bind and execute */
	if (!$stmt->bind_param("s", $email)) {
	    echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
	}

	if (!$stmt->execute()) {
	    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
	}

	$res = $stmt->get_result();		// getting the result from the query
    
	$return_arr = array();	// creating an empty array

	// enconde database into json format
	while($row = $res->fetch_assoc()){
		$row_array['id'] = $row['id'];		// assigning the information to another arrow
		$row_array['name'] = $row['txt_nome'];
        $row_array['cpf'] = $row['nr_cpf'];
        $row_array['dateOfBirth'] = $row['dt_nasc'];
        $row_array['login'] = $row['txt_email'];
        $row_array['phone'] = $row['nr_telefone'];
        $row_array['password'] = $row['txt_senha'];

		array_push($return_arr,$row_array);	// put the created array in the return arrow
	}

	echo json_encode($return_arr);	// encoding the entire array to json
			// it's printing the json to the php

	$db->close();		// close connection to the database

}

// Function to update a user's information, calling a procedure from DB, by passing user info to be updated
function postUpdateUser($name,$cpf,$dateOfBirth,$login,$phone,$pass){
	//$db = new mysqli("localhost:3306","root","","splitterdb");
	$db = getDB();
    
	// veryfing if the connection is successful
	if ($db->connect_errno) {
    echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
	}

	/* Prepared statement, stage 1: prepare */
	if (!($stmt = $db->prepare("CALL pr_atualizar_conta (?,?,STR_TO_DATE(?,'%d/%m/%Y'),?,?,?)"))) {
    echo "Prepare failed: (" . $db->errno . ") " . $db->error;
	}

	/* Prepared statement, stage 2: bind and execute */
	//$id = 1;
	if (!$stmt->bind_param("sissis", $name, $cpf, $dateOfBirth, $login, $phone, $pass)) {
	    echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
	}

	if (!$stmt->execute()) {
	    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
	}
	else{
		echo "User ".$name." Successfully Updated";
	}

	$db->close();		// close connection to the database
}

// Function to get a specific user from database by his email
function deactivateUserByEmail($email){
	$db = getDB();
	//$db = new mysqli("localhost","root","","splitterdb");
	
	// veryfing if the connection is successful
	if ($db->connect_errno) {
    echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
	}

	/* Prepared statement, stage 1: prepare */
	if (!($stmt = $db->prepare("UPDATE tb_cliente SET conta_ativa=0 
    WHERE id = (SELECT id FROM tb_login WHERE txt_login =?);"))) {
    echo "Prepare failed: (" . $db->errno . ") " . $db->error;
	}

	/* Prepared statement, stage 2: bind and execute */
	if (!$stmt->bind_param("s", $email)) {
	    echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
	}

	if (!$stmt->execute()) {
	    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
	}

	$res = $stmt->get_result();		// getting the result from the query
    
	$return_arr = array();	// creating an empty array

	// enconde database into json format
	while($row = $res->fetch_assoc()){
		$row_array['id'] = $row['id'];		// assigning the information to another arrow
		$row_array['name'] = $row['txt_nome'];
        $row_array['cpf'] = $row['nr_cpf'];
        $row_array['dateOfBirth'] = $row['dt_nasc'];
        $row_array['phone'] = $row['nr_telefone'];
        $row_array['conta_ativa'] = $row['conta_ativa'];

		array_push($return_arr,$row_array);	// put the created array in the return arrow
	}

	echo json_encode($return_arr);	// encoding the entire array to json
			// it's printing the json to the php

	$db->close();		// close connection to the database

}

// Function to get a specific user from database by his email
function getAllUsers(){
	$db = getDB();
	//$db = new mysqli("localhost","root","","splitterdb");
	
	// veryfing if the connection is successful
	if ($db->connect_errno) {
    echo "Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error;
	}

	/* Prepared statement, stage 1: prepare */
	if (!($stmt = $db->prepare("SELECT * FROM tb_cliente"))) {
    echo "Prepare failed: (" . $db->errno . ") " . $db->error;
	}

	if (!$stmt->execute()) {
	    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
	}

	$res = $stmt->get_result();		// getting the result from the query
    
	$return_arr = array();	// creating an empty array

	// enconde database into json format
	while($row = $res->fetch_assoc()){
		$row_array['id'] = $row['id'];		// assigning the information to another arrow
		$row_array['name'] = $row['txt_nome'];
        $row_array['cpf'] = $row['nr_cpf'];
        $row_array['dateOfBirth'] = $row['dt_nascimento'];
        $row_array['login'] = $row['txt_email'];
        $row_array['phone'] = $row['nr_telefone'];
        $row_array['conta_ativa'] = $row['conta_ativa'];

		array_push($return_arr,$row_array);	// put the created array in the return arrow
	}

	echo json_encode($return_arr);	// encoding the entire array to json
			// it's printing the json to the php

	$db->close();		// close connection to the database

}

?>