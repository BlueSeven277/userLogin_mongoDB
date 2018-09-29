<?php
function process_script() {	
	$action=strtolower(getRequest('action',true,'get'));
	$accepted_actions='login,signup,account,edit,logout,cancel';
	
        if (!in_array($action, explode(',',$accepted_actions))) {
		$action='login';
	}

	if ( (!get_SESSION('userID')) && (!in_array($action, array('login','signup'))) ) {
		$action='logout'; // Forced logout + session destroying/regeneration!
	}
	elseif ( (get_SESSION('userID') >0) && (in_array($action, array('login','signup'))) ) {
		$action='account'; // Reroute to account from login and signup page
	}

	return get_template($action, $action()); 
}


function set_header($action=null, $host=null) {
    $url = (empty($action)) ? urlhost($host) : urlhost($host).'?action='.$action;
    header('Location: '. $url );
    exit();
}


function &account() {
    $HTML=array();
    return $HTML;
}


function &logout() {
    $_SESSION=array();
    session_destroy();
    setcookie(APPLICATION,'',time()-3600,'/'); // delete session cookie
    set_header();
    exit();
}


function &login(){ 
    $HTML=array();
    $HTML['email']='';
    $HTML['password']='';

    $HTML['login_error']=''; //Reset Error
    $userID=NULL; // To enforce safe standards 
    
    // ----------------- INPUT PROCESSING BLOCK
    if (getRequest('submitted',true,'post') !=='yes') {return $HTML;}

    foreach($HTML as $key=> &$value) {
        $value=getRequest($key, true, 'post');
    }

    // ----------------- INPUT VALIDATION BLOCK
    if ($HTML['email'] =='') { 
        $HTML['login_error'] = 'Email cannot be empty';
    }
    elseif (!filter_var($HTML['email'], FILTER_VALIDATE_EMAIL)) {
        $HTML['login_error'] = 'Invalid Email';
    }
    elseif ($HTML['password'] =='') { 
        $HTML['login_error'] = 'Password cannot be empty';
    }
    elseif ( !($userID = validate_record($HTML['email'],$HTML['password'])) ) {
           $HTML['login_error'] = 'Account not found or invalid Email/Password';
    }

    // ----------------- LOGIN BLOCK
    if (empty($HTML['login_error'])) {
        session_regenerate_id(true);
        set_SESSION('userID', $userID);
        set_header('account'); //If no errors -> go to account
        exit();
    }
    else {
        $HTML['password'] = ''; // We reset the password always on errors!
    }

    return $HTML;
}


function &edit() {
	return signup(true);
} 


function &signup($edit=false){
	$HTML=array();
	$HTML['email']='';
	$HTML['password']='';
	$HTML['confirm_password']='';
        $HTML['name']='';
	$HTML['city']='';
	$HTML['countryID']='';

	$errors=0; //Error counter
	$all_keys= array_keys($HTML);

        $userID=get_SESSION('userID');
        
	foreach($all_keys as $key) {
		$HTML[$key . '_error']=''; //Reset Errors
	}

	$HTML['signup_error']='';//Special General Error message. Database error goes here too!
	
	if (getRequest('submitted',true,'post') !=='yes') {
                //Pre-populate the values!
                if ( ($edit) && (!get_record($userID, $HTML)) ) {
                        set_header('logout');
			exit();

                }
                // We create a drop-down list of the countries
                $HTML['country_options_escape']= buildOptions (get_countries(), 'Please Select', $HTML['countryID']);
		return $HTML;
	}
	
	foreach($all_keys as $key) {
		$HTML[$key]=getRequest($key, true, 'post');
		$error_key=$key .'_error';
		
		switch($key) {
			case 'email':
				$HTML[$key]=strtolower($HTML[$key]);
				if ($HTML[$key]=='') {
						$HTML[$error_key] = 'Email Cannot be empty';
				}elseif (!filter_var($HTML[$key],FILTER_VALIDATE_EMAIL)) {
						$HTML[$error_key] = 'Invalid Email Address';
				}elseif (email_exists($HTML[$key],$userID)) {
                                    		$HTML[$error_key] = 'Email Address already registered!';
                                }
			
				break;
			
			case 'confirm_password':
				if ($HTML[$key] != $HTML['password']) {
					$HTML[$error_key]='Passwords do not match';
                                        $HTML[$key]=$HTML['password']='';
				}
                                elseif ($HTML[$key]=='') {
                                    //Allow empty passwords for edit
                                    if (!$edit) {$HTML[$error_key]='Password cannot be empty'; }
                                }

                                break;
			
			case 'countryID':
                                // We build Drop-down list and make sure selection stays!
				$HTML['country_options_escape']= buildOptions (get_countries(), 'Please Select', $HTML['countryID']);
				if (empty($HTML[$key])) {
					$HTML[$error_key]='Please select the country';
				} 
				break;
			
			case 'city':
                        case 'name':
				if ($HTML[$key]==''){
					$HTML[$error_key]='This field cannot be empty!';
				}
				elseif (preg_match("/[^\w\d\'\-\.\,[[:space:]]]/",$HTML[$key])) {
					$HTML[$error_key]='Special Symbols are not allowed!';
				}
				break;
					
			default:
				break; 
		}
		
		if (!empty($HTML[$error_key])) { $errors++;}
		
	}

	
	if (empty($errors)) {
                        if ( ($edit) && (update_record($userID, $HTML)) ) {
                                session_regenerate_id(true);
                                set_header('account');
				exit();
                        }
			elseif (  (!$edit) && ($userID=add_record($HTML)) ) {
                                session_regenerate_id(true);
				set_SESSION('userID',$userID);
				set_header('account');
				exit();
			}
			else {
				$HTML['signup_error']='Server is busy. Please try again later';
			}
		
	}	
	
	return $HTML;
}


function get_record($userID=null, &$HTML) {
    if (empty($userID)) {return false;}
    $recordID=NULL;
      //Alter
      $filter = [ 'userID' => $userID ]; 
$query = new MongoDB\Driver\Query($filter); 

$rows = $GLOBALS['DB']->executeQuery("cs4411_hw3.users", $query);
 
foreach ($rows as $row) {
	
        $recordID = $row->_id;
         $HTML['email'] = $row->email;
        $HTML['name'] = $row->name;
        $HTML['city'] = $row->city;
        $HTML['countryID'] = $row->countryID;
}
////Alter End   
   
  
        
    return $recordID;
}




// Validates a user based on username and password
function validate_record($email, $password='') {

    if ( (empty($email)) || ($password=='') ) { return false; }
    
    $userID = false; // default value to return
     // Get Instance of a statement
      $filter = [ 'email' => $email , 'password' =>$password]; 
$query = new MongoDB\Driver\Query($filter); 

$rows = $GLOBALS['DB']->executeQuery("cs4411_hw3.users", $query);
 
foreach ($rows as $row) {
//	echo "$row->email : $row->password\n";
        $userID = $row->userID;
}   
 
    
    return $userID; // will be the userID if found or remain false if not
}



// Check if user with the email $email already exists
function email_exists($email,$currentID=null) {

    if (empty($email)) return false;
        
    $userID = 0; // default value to return
    // Get Instance of a statement
    $filter = [ 'email' => $email ]; 
$query = new MongoDB\Driver\Query($filter); 

$rows = $GLOBALS['DB']->executeQuery("cs4411_hw3.users", $query);
 
foreach ($rows as $row) {
//	echo "$row->email : already exist\n";
        $userID = $row->userID;
}
    
    // Reset userID to 0 if matches non-empty currentID
    if ( !empty($currentID) && ($currentID==$userID) ) {$userID=0;}
    
    return ($userID > 0) ? true : false; // will be the userID if found or remain false if not
}




function update_record($userID=NULL, &$HTML=array()) {
       if (empty($userID)) {return false;}
       $result=-1;
    $bulk = new MongoDB\Driver\BulkWrite();
    if ($HTML['password'] == '') {
        $filter = ['userID' => $userID];
        $query = new MongoDB\Driver\Query($filter);

        $rows = $GLOBALS['DB']->executeQuery("cs4411_hw3.users", $query);

        foreach ($rows as $row) {

            $HTML['password'] = $row->password;
        }
    }
       $bulk->update(
    ['userID' => $userID],
    ['$set' => ['email' => $HTML['email'], 'password' => $HTML['password'],'name' => $HTML['name'], 'city' => $HTML['city'], 'countryID' => $HTML['countryID']]],
    ['multi' => false, 'upsert' => false]
);

    $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
    $result = $GLOBALS['DB']->executeBulkWrite('cs4411_hw3.users', $bulk, $writeConcern);
    //Alter End
        
            
	 
	return ($result < 0) ? false : true;
}


function add_record(&$HTML=array()) {
       $userID=false; // Default value that we return;
    $bulk = new MongoDB\Driver\BulkWrite();
$newID =  rand(1000,99999);
$_id1=$bulk->insert( array('email'=> $HTML['email'], 'userID'=> $newID,  'password'=>$HTML['password'], 
    'name' => $HTML['name'],'city' => $HTML['city'], 'countryID' =>$HTML['countryID']
     ) );
//var_dump($_id1);
$writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100);

$result = $GLOBALS['DB']->executeBulkWrite("cs4411_hw3.users", $bulk, $writeConcern);
$userID = $newID;
        

	/* return userID of newly created record! */
	return $userID;
}



function &cancel() {
        // Get Instance of a statement
       //Alter
    $bulk = new MongoDB\Driver\BulkWrite;
$bulk->delete(['userID' => get_SESSION('userID')], ['limit' => 1]);   
$writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
$result = $GLOBALS['DB']->executeBulkWrite('cs4411_hw3.users', $bulk, $writeConcern);
    //Alter End
	logout(); 
}


function get_template($file, &$HTML=null){
    $content='';
    // Needed to prevent XSS!
    sanitizeOutputHTML($HTML);
    ob_start();
            if (@include(TMPL_DIR . '/' .$file .'.tmpl.php')):
            $content=ob_get_contents();
    endif;
    ob_end_clean();
    return $content;
}


//This function is needed to sanitize $HTML before using it in the template
// If we have $HTML['value_escape'] then do NOT convert the string to HTML5 character
function sanitizeOutputHTML(&$HTML=array()) {
    foreach ($HTML as $key => &$value) {
	if(!preg_match('/_escape/i',$key)) {
		$value=convert2HTML($value);
	}
    }
    
}



// FUNCTION needed for INPUT block	
function getRequest($str='', $removespace=false, $method=null){
    if (empty($str)) {return '';}
    switch ($method) {
        case 'get':
                $data=(isset($_GET[$str])) ? $_GET[$str] : '' ;
                break;
        case 'post':
                $data=(isset($_POST[$str])) ? $_POST[$str] : '';
                break;

        default:
                $data=(isset($_REQUEST[$str])) ? $_REQUEST[$str] : '';
    }

    if (empty($data)) {return $data;}

    if (get_magic_quotes_gpc()) {
        $data= (is_array($data)) ? array_map('stripslashes',$data) : stripslashes($data);	
    }

    if (!empty($removespace)) {
        $data=(is_array($data)) ? array_map('removeSpacing',$data) : removeSpacing($data);
    }

    return $data;
}


// THIS FUNCTION used to get GET DATA
function get_SESSION($key) {
    return ( !isset($_SESSION[$key]) ) ? NULL : $_SESSION[$key];
}

// THIS FUNCTION used to set SESSION DATA
function set_SESSION($key, $value) {
    if ( !empty($key) ) {
        $_SESSION[$key] = $value;
        return true;
    }
    return false;
}

// THIS FUNCTION used to remove extra spaces in the string
function removeSpacing($str) {
    return trim(preg_replace('/\s\s+/', ' ', $str));
}

// THIS FUNCTION creates a drop-down menu from array. Used to create drop-down for countries!
function buildOptions($choicesArray, $init_str='', $selectedID=null) {
   		
   		$result='';
   		if ( $init_str !='') {
				$result .="<option value=\"\">" . convert2HTML($init_str) . "</option>\n";	
		}

		if (sizeof($choicesArray)>0) {
			foreach($choicesArray as $id=>&$value) {
				if (!empty($value)) {
						$check_str = ($id==$selectedID) ? "selected='selected'": '';
						$result .="<option value=\"{$id}\" {$check_str}>" . convert2HTML($value) . "</option>\n";
				}
			}
		}
	return $result;
}	

// THIS FUNCTION converts dangerous chars (', "", &, etc) into HTML entities.
function convert2HTML ($str='') {
  	   	return htmlspecialchars($str, ENT_QUOTES, ini_get('default_charset'), false); 
}

// THIS FUNCTION will decode whatever convert2HTML encodes
function decode2HTML  ($str='') {
  	   	return htmlspecialchars_decode($str, ENT_QUOTES); 
}


// THIS FUNCTION creates an array of countries to be passed to buildOptions function.
function get_countries() {

    $countries=array(); // Default value that we return
    $countryID = $country ='';

     $filter = ['active' => 'YES'];
$options = [
   'projection' => ['_id' => 0],
];
$query = new MongoDB\Driver\Query($filter, $options);
$rows = $GLOBALS['DB']->executeQuery('cs4411_hw3.countries', $query); // $mongo contains the connection object to MongoDB
foreach($rows as $r){
     $country = $r->country;
    $countryID = $r->countryID;
    $countries[$countryID]=$country;
   
}

    

	return $countries;
}


?>
