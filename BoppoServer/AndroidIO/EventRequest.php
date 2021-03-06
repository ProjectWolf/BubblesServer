<?php

$function = $_GET['function'];

if ($function == "createEvent")
	createEvent();
if ($function == "getEid")
	getEid();
if ($function == "getEventData")
	getEventData();
if ($function == "getEventDataByRadius")
    getEventDataByRadius();
if ($function == "getLiveEventDataByRadius")
	getLiveEventDataByRadius();
if ($function == "getEventDataByMember")
	getEventDataByMember();
if ($function == "getLiveEventDataByMember")
	getLiveEventDataByMember();
if ($function == "getEventDataByName")
	getEventDataByName();
if ($function == "getLiveEventDataByName")
	getLiveEventDataByName();
if ($function == "getEventDataByTopNViews")
	getEventDataByTopNViews();
if ($function == "getLiveEventDataByTopNViews")
	getLiveEventDataByTopNViews();
if ($function == "getEventDataByTopNLikes")
	getEventDataByTopNLikes();
if ($function == "getLiveEventDataByTopNLikes")
	getLiveEventDataByTopNLikes();
if ($function == "getEventDataByTopNDislikes")
	getEventDataByTopNDislikes();
if ($function == "getLiveEventDataByTopNDislikes")
	getLiveEventDataByTopNDislikes();
if ($function == "getEventDataByTopNRatings")
	getEventDataByTopNRatings();
if ($function == "getLiveEventDataByTopNRatings")
	getLiveEventDataByTopNRatings();
if ($function == "deleteEvent")
	deleteEvent();
if ($function == "updateEvent")
	updateEvent();
if ($function == "updateEventUnparsedAddress")
    updateEventUnparsedAddress();

	

	
	
/* FUNCTION:    createEvent
 * DESCRIPTION: Adds an event into the corresponding database table.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function createEvent()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */
		
	// IMPORT THE DATABASE CONNECTION
	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';
	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$event_host_uid                       = $json_decoded["eventHostUid"];
	$event_name                           = $json_decoded["eventName"];
	$event_category_label                 = $json_decoded["eventCategoryLabel"];
	$event_type_label                     = $json_decoded["eventTypeLabel"];
	$event_invite_type_label              = $json_decoded["eventInviteTypeLabel"];
	$event_privacy_label                  = $json_decoded["eventPrivacyLabel"];
	$event_image_upload_allowed_indicator = $json_decoded["eventImageUploadAllowedIndicator"];
	$event_description_text               = $json_decoded["eventDescriptionText"];
	$event_start_datetime                 = $json_decoded["eventStartDatetime"];
	$event_end_datetime                   = $json_decoded["eventEndDatetime"];
	$event_gps_latitude                   = $json_decoded["eventGpsLatitude"];
	$event_gps_longitude                  = $json_decoded["eventGpsLongitude"];
	
	// ENCODE THE EVENT TYPE LABEL
	require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/ReferenceData.php';
	$event_type_code = dbGetEventTypeCode($event_category_label, $event_type_label);
	if (!($json_decoded["eventTypeLabel"] == null || $event_type_label != null)) {
        echo "ERROR: Incorrect event type specified.";
        return; }
	   
    // ENCODE THE EVENT CATEGORY LABEL
    require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/ReferenceData.php';
    $event_category_code = dbGetEventCategoryCode($event_category_label, $event_type_label);
    if (!($json_decoded["eventCategoryLabel"] == null || $event_category_label != null ||
            $json_decoded["eventTypeLabel"] == null || $event_type_label != null)) {
        echo "ERROR: Incorrect event category or event type specified.";
        return; }
		
	// ENCODE THE PRIVACY LABEL
	require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Privacy.php';
	$event_privacy_code = fetchPrivacyCode($event_privacy_label);
	if ($event_privacy_code == -1) { 
		echo "ERROR: Incorrect event privacy specified.";
		return; }
			
	// ENCODE THE INVITE TYPE LABEL
	require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/InviteType.php';
	$event_invite_type_code = fetchInviteTypeCode($event_invite_type_label);
	if ($event_invite_type_code == -1) {
		echo "ERROR: Incorrect event invite type specified.";
		return; }
				
	// CONVERT THE IMAGE UPLOAD ALLOWED INDICATOR TO A CHARACTER
	require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
	$event_image_upload_allowed_indicator = strBoolToChar($event_image_upload_allowed_indicator);
	
	//echo "PRIVACY CODE: " . $event_privacy_code . "<br><br>"; 
	//echo "INVITE TYPE CODE: " . $event_invite_type_code . "<br><br>";
	//echo "IMAGE UPLOAD ALLOWED INDICATOR: " . $event_image_upload_allowed_indicator . "<br><br>";
	//echo "GPS LATITUDE: " . $event_gps_latitude . "<br><br>";
	//echo "GPS LONGITUDE: " . $event_gps_longitude . "<br><br>";
			
	// EXECUTE THE TRANSACTION
	$queries = array(
		"INSERT INTO T_EVENT 
		    (event_name, event_host_uid, event_category_code, event_type_code, 
	         event_privacy_code, event_invite_type_code, 
			 event_image_upload_allowed_indicator, event_description_text, 
	         event_start_datetime, event_end_datetime, 
		     event_gps_latitude, event_gps_longitude)
		 VALUES 
		 	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
		"INSERT INTO R_EVENT_USER
			(eid, uid, event_user_type_code, 
	         event_user_invite_status_type_code) 
		 VALUES 
			(?, ?, 1, 1)" 	// As of this moment, the 1 was = 'Administrator' & 1 was = 'Joined'
	);
		
	$conn->autocommit(FALSE);
	
	$response = "PLACEHOLDER FOR RESPONSE";
	$eid = null;
	
	foreach ($queries as $query)
	{
		$statement = $conn->prepare($query);
		
		$index = array_search($query, $queries);
		if ($index === 0) {
			$statement->bind_param("siiiiiisssdd", $event_name, $event_host_uid, 
			    $event_category_code, $event_type_code, $event_privacy_code, $event_invite_type_code, 
			    $event_image_upload_allowed_indicator, $event_description_text, 
				$event_start_datetime, $event_end_datetime, $event_gps_latitude, $event_gps_longitude);
		}
		elseif ($index === 1) {
			$statement->bind_param("ii", $eid, $event_host_uid);	
		}
		
		$statement->execute();
		$error = $statement->error;
		// CHECK FOR AN ERROR, RETURN IT IF ONE EXISTS
		if ($error != "") { echo "DB ERROR: " . $error; return; }
		$statement->close();
		
		if ($conn->insert_id != 0) {
			$eid = $conn->insert_id;
			$response = "Success. ID: " . $eid;
		}
	}
	
	$conn->commit();
	$conn->autocommit(TRUE);
	
	// RETURN A SUCCESS CONFIRMATION MESSAGE
	echo $response;	
}
	
/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getEid
 * DESCRIPTION: Gets an Event Identifier from the corresponding database table.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getEid()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// IMPORT THE DATABASE CONNECTION
	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';
	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$event_host_uid = $json_decoded["eventHostUid"];
	$event_name     = $json_decoded["eventName"];
		
	// EXECUTE THE QUERY
	$query = "SELECT eid 
			  FROM T_EVENT 
			  WHERE event_host_uid = ? AND event_name = ?";
	$statement = $conn->prepare($query);
	$statement->bind_param("is", $event_host_uid, $event_name);
	$statement->execute();
	$error = $statement->error;
	// CHECK FOR AN ERROR, RETURN IT IF ONE EXISTS
	if ($error != "") { echo "DB ERROR: " . $error; return; }
	
	// DEFAULT AND ASSIGN THE EVENT ID
	$eid = 0;
	$statement->bind_result($eid);
	$statement->fetch();
	$statement->close();
	
	// RETURN THE EVENT ID
	echo $eid;
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getEventData
 * DESCRIPTION: Gets the data of an entire event for the specified eid
 *              (Event Identifier).
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getEventData()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */
	
	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$eid = $json_decoded["eid"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$event = dbGetEventData($eid);
	
	// RETURN THE EVENT ID
    echo json_encode($event);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    dbGetEventDataByRadius
 * DESCRIPTION: Gets the data of an entire event for all events whose coordinates 
 *              are within the specified radius of the specified coordinates.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getEventDataByRadius()
{
  /* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
  error_reporting(E_ALL);
  ini_set('display_errors', TRUE);
  ini_set('display_startup_errors', TRUE);
  /* END. */

  // DECODE JSON STRING
  $json_decoded = json_decode(file_get_contents("php://input"), true);
  // ASSIGN THE JSON VALUES TO VARIABLES
  $longitude = $json_decoded["longitude"];
  $latitude  = $json_decoded["latitude"];
  $radius    = $json_decoded["radius"];

  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
  $event = dbGetEventDataByRadius($longitude, $latitude, $radius);

  // RETURN THE EVENT ID
  echo json_encode($event);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getLiveEventDataByRadius
 * DESCRIPTION: Gets the data of an entire live event for all events whose 
 *              coordinates are within the specified radius of the specified 
 *              coordinates. An event whose start-date-to-end-date time period is 
 *              no more than the configured amount of time is considered live.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getLiveEventDataByRadius()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$longitude = $json_decoded["longitude"];
	$latitude  = $json_decoded["latitude"];
	$radius    = $json_decoded["radius"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$event = dbGetLiveEventDataByRadius($longitude, $latitude, $radius);

	// RETURN THE EVENT ID
	echo json_encode($event);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getEventDataByMember
 * DESCRIPTION: Gets the data of an entire event for all of the events of which 
 *  			the specified uid (User Identifier) is a member.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getEventDataByMember()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$uid = $json_decoded["uid"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetEventDataByMember($uid);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getLiveEventDataByMember
 * DESCRIPTION: Gets the data of an entire live event for all of the events of 
 *              which the specified uid (User Identifier) is a member. An event 
 *              whose start-date-to-end-date time period is no more than the 
 *              configured amount of time is considered live.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getLiveEventDataByMember()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$uid = $json_decoded["uid"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetLiveEventDataByMember($uid);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getEventDataByName
 * DESCRIPTION: Gets the data of an entire event for all of the events whose names
 *  			match the specified event name.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getEventDataByName()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$event_name = $json_decoded["eventName"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetEventDataByName($event_name);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getLiveEventDataByName
 * DESCRIPTION: Gets the data of an entire live event for all of the events whose 
 *              names match the specified event name. An event whose 
 *              start-date-to-end-date time period is no more than the configured 
 *              amount of time is considered live.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getLiveEventDataByName()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$event_name = $json_decoded["eventName"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetLiveEventDataByName($event_name);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getEventDataByTopNViews
 * DESCRIPTION: Gets the data of an entire event for all of the events that have 
 *              the top N count of views, where N is the input value.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getEventDataByTopNViews()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$top_n_views = $json_decoded["topNViews"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetEventDataByTopNViews($top_n_views);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getLiveEventDataByTopNViews
 * DESCRIPTION: Gets the data of an entire live event for all of the events that 
 *              have the top N count of views, where N is the input value. An event 
 *              whose start-date-to-end-date time period is no more than the 
 *              configured amount of time is considered live.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getLiveEventDataByTopNViews()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$top_n_views = $json_decoded["topNViews"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetLiveEventDataByTopNViews($top_n_views);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getEventDataByTopNLikes
 * DESCRIPTION: Gets the data of an entire event for all of the events that have
 *              the top N count of likes, where N is the input value.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getEventDataByTopNLikes()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$top_n = $json_decoded["topN"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetEventDataByTopNLikes($top_n);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getLiveEventDataByTopNLikes
 * DESCRIPTION: Gets the data of an entire live event for all of the events that 
 *              have the top N count of likes, where N is the input value. An event 
 *              whose start-date-to-end-date time period is no more than the 
 *              configured amount of time is considered live.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getLiveEventDataByTopNLikes()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$top_n = $json_decoded["topN"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetLiveEventDataByTopNLikes($top_n);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getEventDataByTopNDislikes
 * DESCRIPTION: Gets the data of an entire event for all of the events that have
 *              the top N count of dislikes, where N is the input value.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getEventDataByTopNDislikes()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$top_n = $json_decoded["topN"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetEventDataByTopNDislikes($top_n);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getLiveEventDataByTopNDislikes
 * DESCRIPTION: Gets the data of an entire live event for all of the events that 
 *              have the top N count of dislikes, where N is the input value. An 
 *              event whose start-date-to-end-date time period is no more than the 
 *              configured amount of time is considered live.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getLiveEventDataByTopNDislikes()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$top_n = $json_decoded["topN"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetLiveEventDataByTopNDislikes($top_n);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getEventDataByTopNRated
 * DESCRIPTION: Gets the data of an entire event for all of the events that
 *              have the top N ratings, where N is the input value and a rating is
 *              the ratio of likes to the sum of likes and dislikes (ratings). An
 *              event whose start-date-to-end-date time period is no more than the
 *              configured amount of time is considered live.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getEventDataByTopNRatings()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$top_n = $json_decoded["topN"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetEventDataByTopNRatings($top_n);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    getLiveEventDataByTopNRated
 * DESCRIPTION: Gets the data of an entire live event for all of the events that
 *              have the top N ratings, where N is the input value and a rating is
 *              the ratio of likes to the sum of likes and dislikes (ratings). An
 *              event whose start-date-to-end-date time period is no more than the
 *              configured amount of time is considered live.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function getLiveEventDataByTopNRatings()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$top_n = $json_decoded["topN"];

	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$eventList = dbGetLiveEventDataByTopNRatings($top_n);

	// RETURN THE EVENT ID
	echo json_encode($eventList);
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    deleteEvent
 * DESCRIPTION: Deletes an Event with the specified Event Identifier from the 
 *              corresponding database table.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function deleteEvent()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// IMPORT THE DATABASE CONNECTION
	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';
	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	// ASSIGN THE JSON VALUES TO VARIABLES
	$eid = $json_decoded["eid"];

	// EXECUTE THE QUERY
	$query = "DELETE 
			  FROM T_EVENT 
			  WHERE eid = ?";
	$statement = $conn->prepare($query);
	$statement->bind_param("i", $eid);
	$statement->execute();
	$error = $statement->error;
	$statement->close();
	// CHECK FOR AN ERROR, RETURN IT IF ONE EXISTS
	if ($error != "") { echo "DB ERROR: " . $error; return; }

	// RETURN A SUCCESS MESSAGE
	echo "Success.";
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    updateEvent
 * DESCRIPTION: Updates an event into the corresponding database table with the
 *  			newly provided values.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function updateEvent()
{
	/* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);
	/* END. */

	// IMPORT THE DATABASE CONNECTION
	require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';
	// DECODE JSON STRING
	$json_decoded = json_decode(file_get_contents("php://input"), true);
	/*
	$json_decoded["eid"] = 38;
	$json_decoded["eventHostUid"] = null;
	$json_decoded["eventName"] = "FunnierShizEvah";
	$json_decoded["eventInviteTypeLabel"] = "Friends";
	$json_decoded["eventPrivacyLabel"] = null;
	$json_decoded["eventImageUploadAllowedIndicator"] = null;
	$json_decoded["eventStartDatetime"] = null;
	$json_decoded["eventEndDatetime"] = null;
	$json_decoded["eventGpsLatitude"] = null;
	$json_decoded["eventGpsLongitude"] = null;
	*/
	// ASSIGN THE JSON VALUES TO VARIABLES
	$eid 								  = $json_decoded["eid"];
	$event_host_uid                       = $json_decoded["eventHostUid"];
	$event_name                           = $json_decoded["eventName"];
	$event_category_label                 = $json_decoded["eventCategoryLabel"];
	$event_type_label                     = $json_decoded["eventTypeLabel"];
	$event_invite_type_label              = $json_decoded["eventInviteTypeLabel"];
	$event_privacy_label                  = $json_decoded["eventPrivacyLabel"];
	$event_image_upload_allowed_indicator = filter_var($json_decoded["eventImageUploadAllowedIndicator"], 
												FILTER_VALIDATE_BOOLEAN);
	$event_description_text               = $json_decoded["eventDescriptionText"];
	$event_start_datetime                 = $json_decoded["eventStartDatetime"];
	$event_end_datetime                   = $json_decoded["eventEndDatetime"];
	$event_gps_latitude                   = $json_decoded["eventGpsLatitude"];
	$event_gps_longitude                  = $json_decoded["eventGpsLongitude"];
	
	$set_or_not = $json_decoded["setOrNot"];
	
	// MAKE SURE THAT A VALID EVENT IDENTIFIER WAS PROVIDED
	if ($eid <= 0) {
		echo "ERROR: Incorrect event identifier specified.";
		return; }
		
	// ENCODE THE EVENT TYPE LABEL
	require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/ReferenceData.php';
	$set_or_not["eventTypeCode"] = $set_or_not["eventTypeLabel"];
	unset($set_or_not["eventTypeLabel"]);
	$event_type_code = dbGetEventTypeCode($event_category_label, $event_type_label);
	if (!($json_decoded["eventTypeLabel"] == null || $event_type_code != null)) {
	  echo "ERROR: Incorrect event type specified.";
	  return; }
	  
    // ENCODE THE EVENT CATEGORY LABEL
    require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/ReferenceData.php';
    $set_or_not["eventCategoryCode"] = $set_or_not["eventCategoryLabel"];
    unset($set_or_not["eventCategoryLabel"]);
    $event_category_code = dbGetEventCategoryCode($event_category_label);
    if (!($json_decoded["eventCategoryLabel"] == null || $event_category_code != null ||
            $json_decoded["eventTypeLabel"] == null || $event_type_code != null)) {
      echo "ERROR: Incorrect event category or event type specified.";
      return; }
	
	// ENCODE THE INVITE TYPE LABEL
	require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/InviteType.php';
	$set_or_not["eventInviteTypeCode"] = $set_or_not["eventInviteTypeLabel"];
	unset($set_or_not["eventInviteTypeLabel"]);
	$event_invite_type_code = fetchInviteTypeCode($event_invite_type_label);
	if (!($json_decoded["eventInviteTypeLabel"] == null || $event_invite_type_code != null)) {
		echo "ERROR: Incorrect event invite type specified.";
		return; }
	
	// ENCODE THE PRIVACY LABEL
	require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Privacy.php';
	$set_or_not["eventPrivacyCode"] = $set_or_not["eventPrivacyLabel"];
	unset($set_or_not["eventPrivacyLabel"]);
	$event_privacy_code = fetchPrivacyCode($event_privacy_label);
	if (!($json_decoded["eventPrivacyLabel"] == null || $event_privacy_code != null)) {
		echo "ERROR: Incorrect event privacy specified.";
		return; }
		
	// CONVERT THE IMAGE UPLOAD ALLOWED INDICATOR TO A CHARACTER
	require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
	$event_image_upload_allowed_indicator = strBoolToChar($event_image_upload_allowed_indicator);
	if ($event_image_upload_allowed_indicator != "0" && $event_image_upload_allowed_indicator != "1"
		&& $json_decoded["eventImageUploadAllowedIndicator"] != null) {
		echo "ERROR: Incorrect event image upload allowed indicator specified.";
		return; }
		
	// SEND THE NEW VALUES IN AN EVENT OBJECT TO THE CORRESPONDING DBIO METHOD
	$event = array
	(
		"eid" => $eid, 
		"eventHostUid" => $event_host_uid,
		"eventName" => $event_name, 
	    "eventCategoryCode" => $event_category_code, 
	    "eventTypeCode" => $event_type_code, 
		"eventInviteTypeCode" => $event_invite_type_code,
		"eventPrivacyCode" => $event_privacy_code,
		"eventImageUploadAllowedIndicator" => $event_image_upload_allowed_indicator,
	    "eventDescriptionText" => $event_description_text,
		"eventStartDatetime" => $event_start_datetime,
		"eventEndDatetime" => $event_end_datetime,
		"eventGpsLatitude" => $event_gps_latitude,
		"eventGpsLongitude" => $event_gps_longitude
	);
	require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
	$response = dbUpdateEvent($event, $set_or_not);
	
	echo $response;
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */



/* FUNCTION:    updateEventUnparsedAddress
 * DESCRIPTION: Updates an event with the specified eid to have the address with 
 *              the specified aid. 
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
function updateEventUnparsedAddress()
{
  /* THE FOLLOWING 3 LINES OF CODE ENABLE ERROR REPORTING. */
  error_reporting(E_ALL);
  ini_set('display_errors', TRUE);
  ini_set('display_startup_errors', TRUE);
  /* END. */

  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';
  // DECODE JSON STRING
  $json_decoded = json_decode(file_get_contents("php://input"), true);

  // ASSIGN THE JSON VALUES TO VARIABLES
  $eid = $json_decoded["eid"]; 
  $event_aid = $json_decoded["eventAid"]; 

  // MAKE SURE THAT A VALID EVENT IDENTIFIER WAS PROVIDED
  if ($eid <= 0) {
    echo "ERROR: Incorrect event identifier specified.";
    return; }
    
  // MAKE SURE THAT A VALID ADDRESS IDENTIFIER WAS PROVIDED
  if ($event_aid <= 0) {
    echo "ERROR: Incorrect address identifier specified.";
    return; }

  // SEND THE NEW VALUES IN AN EVENT OBJECT TO THE CORRESPONDING DBIO METHOD
  $event = array
  (
      "eid" => $eid, 
      "eventAid" => $event_aid
  );
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Event.php';
  $response = dbUpdateEventUnparsedAddress($event);

  echo $response;
}

/* --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */

?>