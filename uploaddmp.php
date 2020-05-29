<?php
/*
   +----------------------------------------------------------------------+
   | Copyright 2020 University of Melbourne                               |
   +----------------------------------------------------------------------+
   | License here https://opensource.org/licenses/BSD-3-Clause            |
   +----------------------------------------------------------------------+
   | Authors: Lyle Winton <l.winton@unimelb.edu.au>                       |
   |          ... others encouraged                                       |
   +----------------------------------------------------------------------+
 */

/*
 Script purpose:
    * Find a figshare user account id given an email address.
    (rest of actions password protected)
    * parse a given maDMP JSON from a URL (just plain HTTP at this point)
    * create a figshare Project, as a placeholder for all datasets to be uploaded/published
    * create a DMP item within the project with the referenced DMP ID, title, description
    * reserve a DOI for the DMP item, for future publishing (or possible update back in the DMP system)
    * uploading the maDMP JSON and PDF as files as part of the item. [TODO]

    This script is eventually intended for direct call with URL parameters (email etc.).
    The HTML form is included only for testing direct call parameters.
    In future the response might be JSON, to allow for fully automated calls from other systems.

*/

### defaults
$email = "";
$dmpurl = "https://figshareapi.digital-scholarship.cloud.edu.au/madmp/dmp.json";
$pdfurl = "https://figshareapi.digital-scholarship.cloud.edu.au/madmp/dmp.pdf";


ini_set('display_errors', 1);
### config.php contains authentication information
include '../config.php';


/* 
    figshareCall($method, $uri, $action, $data, $token="")
        REST call function for figshare API.
    
    method = 'GET' or 'POST'
    uri = 'https://api.figshare.com/v2'
    action = eg. '/articles/search'
    token = personal authorization token
    returns ($jsondecode,$fail,$returncode)
*/
function figshareCall($method, $uri, $action, $data, $token="") {
	$curl = curl_init($uri.$action);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	if ($method == "POST") {
		curl_setopt($curl, CURLOPT_POST, true);
	} else {
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	}
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
	if ($token != "") {
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
		  'Content-Type: application/json',
		  'Authorization: token '.$token
		]);
	} else {
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
		  'Content-Type: application/json'
		]);
	}
	$return = curl_exec($curl);
	// check if the HTTP request failed
	if (curl_errno($curl)) {
		$error = curl_error($curl);
	} else {
		$error = false;
	}
	if ($return === false) {
		$response = [];
		$statuscode = 0;
	} else {
		$response = json_decode($return, true);
		$statuscode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	}
	curl_close($curl);
	return array( $response, $error, $statuscode );
}


### Get input parameters
if (isset($_GET['email']) && $_GET['email'] != '') {
	$email = $_GET['email'];
}
if (isset($_GET['dmpurl']) && $_GET['dmpurl'] != '') {
	$dmpurl = $_GET['dmpurl'];
}
if (isset($_GET['pdfurl']) && $_GET['pdfurl'] != '') {
	$pdfurl = $_GET['pdfurl'];
}
$password = "";
if (isset($_GET['password']) && $_GET['password'] != '') {
	$password = $_GET['password'];
}

### Retrieve a user account based on email address (Note: we could use login (institution_user_id)
if ($email != '') {
	// Data to send
	$data = [
	  'page' => 1,
	  'page_size' => 10,
	  'is_active' => true,
	  'institution_user_id' => '',
	  'email' => $email
	];
	list ($response, $error, $status) = figshareCall('GET', $figshare_api_uri, "/account/institution/accounts", $data, $figshare_token);
} else {
	$response = [];
	$error = false;
}


?>
<html>
 <head>
  <title>maDMP to figshare - hackathon</title>
 </head>
<style>
body p td a {
	font-family: Arial, Helvetica, sans-serif;
}
.link {
	font-family: Arial, Helvetica, sans-serif;
	color: #14A;
	padding: 2px;
}
.submitbutton {
  background-color: #4CAF50; /* Green background */
  border: 1px solid #C2C2C2;
  border-radius: 3px;
  color: white; /* White text */
  padding: 8px 16px; /* Some padding */
  font-size: 16px /* Set a font size */
}
label {
	font-family: Arial, Helvetica, sans-serif;
	margin: 0px 0px 15px 0px;
}
input {
	font-family: Arial, Helvetica, sans-serif;
	background: #eee;
	box-sizing: border-box;
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	border: 1px solid #C2C2C2;
	box-shadow: 1px 1px 4px #EBEBEB;
	-moz-box-shadow: 1px 1px 4px #EBEBEB;
	-webkit-box-shadow: 1px 1px 4px #EBEBEB;
	border-radius: 3px;
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	padding: 7px;
	outline: none;
}
</style>
<body>

<form action="" method="get">
          <label for="email">User Email:</label>
          <input id="email" type="text" name="email" size="70" value="<?php echo htmlspecialchars($email) ?>" />
	  <br/>
          <label for="dmpurl">DMP URL:</label>
          <input id="dmpurl" type="text" name="dmpurl" size="70" value="<?php echo htmlspecialchars($dmpurl) ?>" />
	  <br/>
          <label for="pdfurl">PDF URL:</label>
          <input id="pdfurl" type="text" name="pdfurl" size="70" value="<?php echo htmlspecialchars($pdfurl) ?>" />
	  <br/>
          <label for="password">Password:</label>
          <input id="password" type="password" name="password" size="20" value="" />
      <br />
          <button type="submit" name="submit" class="submitbutton" id="submit" onclick="document.getElementById('submit').innerHTML='loading...';document.getElementById('submit').style.opacity='.7';">Search/Upload</button>
  </form>

<?php if ($error) { echo '<font color="red">ERROR: '.$error.'</font>'; } ?>

Found accounts:<table border=1>
<?php
$count = 0;
if (count($response) == 0) {
	echo '<tr><td><font class="doi">&nbsp;&nbsp;No users found.</font><p/>&nbsp;<p/></td></tr>';
}
foreach ($response as $item) {
	$count += 1;
	echo "<tr><td height=50 cellpadding=20>" . PHP_EOL;
	echo 'i=' . $count . ' ';
	echo "</td><td>" . PHP_EOL;
	echo ' ' . htmlspecialchars($item['first_name']) . ' ';
	echo ' ' . htmlspecialchars($item['last_name']) . ' ';
	echo "</td><td>" . PHP_EOL;
	echo ' ' . htmlspecialchars($item['id']) . ' ';
	echo "</td><td>" . PHP_EOL;
	echo ' ' . htmlspecialchars($item['email']) . ' ';
	echo "</td></tr>" . PHP_EOL;
}
?>
</table><p/>
<?php


echo '<!-- '. PHP_EOL;
echo 'figshare query string "' . $email . '"' . PHP_EOL;
print_r($response);
echo '-->' . PHP_EOL;


### If a shared secret password is provided, and a user has been found, create a Project and DMP Item
### This is done my impersonating that user, so the auth token must belong to an admin.
### (password is a temporary measure to reduce security holes while testing)
### (additionally check if a restricted user is set)

if ( md5($password) != $passwd_md5 ) {
echo 'Correct password needed to proceed.<p/>'.PHP_EOL;
} else {
// START password protected section
echo 'PASSWORD IS GOOD!<br/>Proceeding to read DMP...<p/>'.PHP_EOL;


### Get the DMP information
$madmpdata = file_get_contents($dmpurl);
if ($madmpdata === false) {
	$error = error_get_last();
	die('ERROR: '.$error['message']);
}
$madmp = json_decode($madmpdata, true);
$title = $madmp['dmp']['title'];
$description = $madmp['dmp']['description'];
$dmp_id = false;
if ($madmp['dmp']['dmp_id']) {
    $dmp_id = $madmp['dmp']['dmp_id']['identifier'];
}

echo 'DMP details:<table border=1><tr><td>'.PHP_EOL;
echo 'DMP_ID:</td><td>'.$dmp_id.PHP_EOL;
echo '</td></tr><tr><td>'.PHP_EOL;
echo 'TITLE:</td><td>'.$title.PHP_EOL;
echo '</td></tr><tr><td>'.PHP_EOL;
echo 'DESCRIPTION:</td><td>'.$description.PHP_EOL;
echo '</td></tr></table><p/>'.PHP_EOL;


if ($count != 1) {
	die('ERROR: '.$count.' accounts found, needs to be one.');
}
$user_id = $item['id'];
if (($restricted_user) && ($user_id != $restricted_user)) { 
	die('RESTRICTED USER FAIL - will not create unless on safe users for testing');
}



### create a project under the user
echo 'Proceeding to create DMP Project in figshare...<p/>'.PHP_EOL;
$data = [
  'impersonate' => $user_id,
  'title' => $title.' '.date("Y-m-d_H:m:s"), // adding date for test
  'description' => $description
];
list ($response, $error, $status) = figshareCall('POST', $figshare_api_uri, "/account/projects", $data, $figshare_token);
if ($error) {
	die('CALL FAILED: '.$error);
}
if ($status!=201) {
	die('ACTION FAILED: '.$status.' - '.$response['code'].' - '.$response['message']);
}
echo '<p/>Response:<table border=1><tr><td><pre>'.PHP_EOL;
print_r($response);
echo '</pre></td></tr></table><p/>'.PHP_EOL;
$project_location = $response['location'];


### create an article under the project
echo 'Proceeding to create DMP Article in figshare project...<p/>'.PHP_EOL;
$data = [
  'impersonate' => $user_id,
  'title' => "DMP - ".$title,
  'description' => $description,
  'tags' => ["DMP","maDMP"],
  'keywords' => ["DMP","maDMP"],
  'references' => [$dmp_id],
  'defined_type' => "online resource",
  'license' => 1,
  "categories" => [2] //uncategorized
];
echo '<!-- Data:'.PHP_EOL;
print_r($data);
echo PHP_EOL.' -->'.PHP_EOL;
list ($response, $error, $status) = figshareCall('POST', $project_location, "/articles", $data, $figshare_token);
if ($error) {
	die('CALL FAILED: '.$error);
}
if ($status!=201) {
	die('ACTION FAILED: '.$status.' - '.$response['code'].' - '.$response['message']);
}
echo '<p/>Response:<table border=1><tr><td><pre>'.PHP_EOL;
print_r($response);
echo '</pre></td></tr></table><p/>'.PHP_EOL;
$item_location = $response['location'];
$item_urlbits = explode("/", $item_location);
$item_id = end( $item_urlbits );


### create a project under the user
echo 'Proceeding to reserve a DOI for the DMP Article...<p/>'.PHP_EOL;
$data = [
  'impersonate' => $user_id
];
list ($response, $error, $status) = figshareCall('POST', $figshare_api_uri, "/account/articles/".$item_id."/reserve_doi", $data, $figshare_token);
if ($error) {
	die('CALL FAILED: '.$error);
}
if ($status!=200) {
	die('ACTION FAILED: '.$status.' - '.$response['code'].' - '.$response['message']);
}
echo '<p/>Response:<table border=1><tr><td><pre>'.PHP_EOL;
print_r($response);
echo '</pre></td></tr></table><p/>'.PHP_EOL;


// TODO: implement https://docs.figshare.com/#private_article_upload_initiate
// TODO: implement https://docs.figshare.com/#private_article_upload_complete
// TODO: consider https://docs.figshare.com/#private_article_publish

}
// END password protected section


?>

<p/>
<p style="text-align:center;size:50%;color:#AAA;">Powered by <a href="https://melbourne.figshare.com" target="_blank"><img alt="figshare" height="21" style="vertical-align:middle" src="https://website-p-eu.figstatic.com/assets/d8fdea89be25e043ce9c291bc0ce1d570055a531/public/global/images/full-logo.png" border=0/></a></p>
</body>
</html>
