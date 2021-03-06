<?php

/* FUNCTION:    subscribeDeviceToTopics
 * DESCRIPTION: Subscribes the device with the specified Firebase Registration
 *              Identifier to all of the topics (User, Image, Event, etc. 
 *              notifications) with the input topic names. 
 * USE CASES:
 *   - Subscribe a newly logged in device to all of the friend, event, image, etc.
 *     notifications
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */

function subscribeDeviceToTopics($frid)
{
  // IMPORT REQUIRED METHODS
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Firebase.php';
  
  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';
  
  // Fetch the API_ACCESS_KEY from the GlobalVariables
  $path_gv        = $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Resources/GlobalVariables.json';
  $file_gv        = file_get_contents($path_gv);
  $array_gv       = json_decode($file_gv, true);
  $API_ACCESS_KEY = $array_gv["Firebase"]["API_ACCESS_KEY"];
  $HEADER         = array(
    'Content-Type:application/json',
    'Content-Length:0',
    'Authorization:key=' . $API_ACCESS_KEY
  );
  
  $topics = dbGetSubscribedTopics($frid)["topics"];

  // Form the URLs for cURL
  // URL TEMPLATE: "https://iid.googleapis.com/iid/v1/REGISTRATION_TOKEN/rel/topics/TOPIC_NAME"
  $url = "https://iid.googleapis.com/iid/v1/$frid/rel/topics/";
  $curlRre = array(); 
  foreach ($topics as $topic)
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url . $topic["topicName"]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADER);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $curlResult = curl_exec($curl);
    $curlError = curl_error($curl);
    $curlResponse = curl_getinfo($curl, CURLINFO_HTTP_CODE); 
    curl_close($curl);
    array_push($curlRre, array
      (
        "topicName" => $topic["topicName"], 
        "curlResult" => $curlResult, 
        "curlError" => $curlError, 
        "curlResponse" => $curlResponse 
      )
    );
  }

  $parent = array
  (
    "subscribeResults"  => $curlRre
  );
  
  saveToSystemLog(json_encode($parent), __FUNCTION__);

  return $parent;
}



/* FUNCTION:    unsubscribeDeviceFromTopics
 * DESCRIPTION: Unsubscribes the device with the specified Firebase Registration
 *              Identifier from all of the topics (User, Image, Event, etc. 
 *              notifications) with the input topic names. 
 * USE CASES:
 *   - Unsubscribe a logged out device from all notification subscriptions. 
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */

function unsubscribeDeviceFromTopics($frid)
{
  // IMPORT REQUIRED METHODS
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Firebase.php';

  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';

  // Fetch the API_ACCESS_KEY from the GlobalVariables
  $path_gv        = $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Resources/GlobalVariables.json';
  $file_gv        = file_get_contents($path_gv);
  $array_gv       = json_decode($file_gv, true);
  $API_ACCESS_KEY = $array_gv["Firebase"]["API_ACCESS_KEY"];
  $HEADER         = array(
      'Content-Type:application/json',
      'Authorization:key=' . $API_ACCESS_KEY
  );

  $topics = dbGetSubscribedTopics($frid)["topics"];

  // Form the URLs for cURL
  // URL TEMPLATE: "https://iid.googleapis.com/iid/v1/REGISTRATION_TOKEN/rel/topics/TOPIC_NAME"
  $url = "https://iid.googleapis.com/iid/v1:batchRemove";
  $curlRre = array();
  foreach ($topics as $topic)
  {
    $message = array
    (
      "to" => "/topics/" . $topic["topicName"],
      "registration_tokens" => array($frid)
    );
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADER);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($message));
    $curlResult = curl_exec($curl);
    $curlError = curl_error($curl);
    $curlResponse = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    array_push($curlRre, array
      (
        "topicName" => $topic["topicName"],
        "curlResult" => $curlResult,
        "curlError" => $curlError,
        "curlResponse" => $curlResponse
      )
    );
  }

  $parent = array
  (
    "unsubscribeResults"  => $curlRre
  );
  
  saveToSystemLog(json_encode($parent), __FUNCTION__);

  return $parent;
}



/* FUNCTION:    unsubscribeDevicesFromTopic
 * DESCRIPTION: Unsubscribes all of the devices of a user from the topic (User,
 *              Image, Event, etc. notifications) with the specified topicName.
 * USE CASES:
 *   - Unsubscribe a user from all notification related to an ex-friend.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */

function subscribeDevicesToTopic($uid, $topicName)
{
  // IMPORT REQUIRED METHODS
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Firebase.php';

  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';

  // Fetch the API_ACCESS_KEY from the GlobalVariables
  $path_gv        = $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Resources/GlobalVariables.json';
  $file_gv        = file_get_contents($path_gv);
  $array_gv       = json_decode($file_gv, true);
  $API_ACCESS_KEY = $array_gv["Firebase"]["API_ACCESS_KEY"];
  $HEADER         = array(
      'Content-Type:application/json',
      'Authorization:key=' . $API_ACCESS_KEY
  );

  $isUserSubscribedToTopic = dbIsUserSubscribedToTopic($uid, $topicName);
  if ($isUserSubscribedToTopic === true)
  {
    return formatResponseSuccess("User's devices are already subscribed to topic.");
  }
  
  // SUBSCRIBE THE USER TO THE TOPIC IN THE DATABASE
  // To-Consider: Instead of checking, try subscribing anyway (enables retry)
  $subscribeUserToTopicResponse = dbSubscribeUserToTopic($uid, $topicName);
  if (!contains(json_encode($subscribeUserToTopicResponse), "Success"))
    return $subscribeUserToTopicResponse;

  $devices = dbGetDevices($uid)["devices"];
  $deviceFrids = array();
  foreach ($devices as $device)
  {
    array_push($deviceFrids, $device["deviceFrid"]);
  }

  // Form the URLs for cURL
  // URL TEMPLATE: "https://iid.googleapis.com/iid/v1/REGISTRATION_TOKEN/rel/topics/TOPIC_NAME"
  $url = "https://iid.googleapis.com/iid/v1:batchAdd";

  $message = array
  (
      "to" => "/topics/" . $topicName,
      "registration_tokens" => $deviceFrids
  );

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADER);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($message));
  $curlResult = curl_exec($curl);
  $curlError = curl_error($curl);
  $curlResponse = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  curl_close($curl);
  $curlRre = array
  (
      "topicName" => $topicName,
      "curlResult" => $curlResult,
      "curlError" => $curlError,
      "curlResponse" => $curlResponse
  );

  $parent = array
  (
      "subscribeResult"  => $curlRre
  );

  saveToSystemLog(json_encode($parent), __FUNCTION__);

  return $parent;
}



/* FUNCTION:    unsubscribeDevicesFromTopic
 * DESCRIPTION: Unsubscribes all of the devices of a user from the topic (User, 
 *              Image, Event, etc. notifications) with the specified topicName. 
 * USE CASES:
 *   - Unsubscribe a user from all notification related to an ex-friend. 
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */

function unsubscribeDevicesFromTopic($uid, $topicName)
{
  // IMPORT REQUIRED METHODS
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Firebase.php';

  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';

  // Fetch the API_ACCESS_KEY from the GlobalVariables
  $path_gv        = $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Resources/GlobalVariables.json';
  $file_gv        = file_get_contents($path_gv);
  $array_gv       = json_decode($file_gv, true);
  $API_ACCESS_KEY = $array_gv["Firebase"]["API_ACCESS_KEY"];
  $HEADER         = array(
      'Content-Type:application/json',
      'Authorization:key=' . $API_ACCESS_KEY
  );

  // CHECK IF THE TOPIC IS ALREADY UNSUBSCRIBED 
  // To-Consider: Instead of checking, try unsubscribing anyway (enables retry)
  $isUserSubscribedToTopic = dbIsUserSubscribedToTopic($uid, $topicName); 
  if ($isUserSubscribedToTopic === false)
  {
    return formatResponseSuccess("User's devices are already unsubscribed from topic.");
  }
  
  // UNSUBSCRIBE THE USER FROM THE TOPIC IN THE DATABASE
  $unsubscribeUserFromTopicResponse = dbUnsubscribeUserFromTopic($uid, $topicName);
  if (!contains(json_encode($unsubscribeUserFromTopicResponse), "Success"))
    return $unsubscribeUserFromTopicResponse;
  
  $devices = dbGetDevices($uid)["devices"];
  $deviceFrids = array();
  foreach ($devices as $device) 
  {
    array_push($deviceFrids, $device["deviceFrid"]);
  }

  // Form the URLs for cURL
  // URL TEMPLATE: "https://iid.googleapis.com/iid/v1/REGISTRATION_TOKEN/rel/topics/TOPIC_NAME"
  $url = "https://iid.googleapis.com/iid/v1:batchRemove";

  $message = array
  (
      "to" => "/topics/" . $topicName,
      "registration_tokens" => $deviceFrids
  );

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADER);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($message));
  $curlResult = curl_exec($curl);
  $curlError = curl_error($curl);
  $curlResponse = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  curl_close($curl);
  $curlRre = array
  (
    "topicName" => $topicName,
    "curlResult" => $curlResult,
    "curlError" => $curlError,
    "curlResponse" => $curlResponse
  );

  $parent = array
  (
      "unsubscribeResult"  => $curlRre
  );

  saveToSystemLog(json_encode($parent), __FUNCTION__);

  return $parent;
}

/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* This method may no longer be needed as it was replaced by device group one */

/* FUNCTION:    subscribeDevicesToTopics
 * DESCRIPTION: Subscribes the devices with the specified Firebase Registration
 *              Identifiers to all of the input topics' IDs for the specified type
 *              of object (User, User Image, Event, etc.).
 * USE CASES: 
 *   - Subscribe all of the devices (correctly the logged in user's) to all of the 
 *     friends, events, etc., for example after befriending a user. 
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
/*
function subscribeDevicesToTopics($frids, $objectTypeLabel, $topics)
{
  // IMPORT REQUIRED METHODS
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/ObjectType.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Firebase.php';

  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';

  // Fetch the API_ACCESS_KEY from the GlobalVariables
  $path_gv        = $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Resources/GlobalVariables.json';
  $file_gv        = file_get_contents($path_gv);
  $array_gv       = json_decode($file_gv, true);
  $API_ACCESS_KEY = $array_gv["Firebase"]["API_ACCESS_KEY"];
  $HEADER         = array(
      'Content-Type:application/json',
      'Content-Length:0',
      'Authorization:key=' . $API_ACCESS_KEY
  );
  
  // Fetch the object type code for the specified objectTypeLabel
  // Ex: x for the User, y for the User Image, z for the Event
  $objectTypeCode = dbGetObjectTypeCode($objectTypeLabel);
  if (contains(json_encode($objectTypeCode), "responseType"))
    return $objectTypeCode;
  
  $devices = array();
      
  foreach ($frids as $frid)
  {
    // Form the URLs for cURL
    // URL TEMPLATE: "https://iid.googleapis.com/iid/v1/REGISTRATION_TOKEN/rel/topics/TOPIC_NAME"
    $url = "https://iid.googleapis.com/iid/v1/$frid/rel/topics/";
    
    $curlResults = array();
    $curlResponses = array();
    $curlErrors = array();    
    
    foreach ($topics as $id)
    {
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url . $objectTypeCode . "." . $id);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADER);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      array_push($curlResults, curl_exec($curl));
      array_push($curlErrors, curl_error($curl));
      array_push($curlResponses, curl_getinfo($curl, CURLINFO_HTTP_CODE));
      curl_close($curl);
    }
  
    $dbResponses = array();
    for ($i = 0; $i < sizeof($topics); $i++)
    {
      if ($curlResponses[$i] === 200)
      {
        $result = dbSubscribeDeviceToTopic($frid, $objectTypeCode . "." . $topics[$i]);
        if (!contains($result, "Device has been successfully subscribed to topic."))
        {
          // TO-DO: If the DB insert fails and the device is not subscribed to the topic,
          // unsubscribe the device from the topic in FCM.
          array_push($dbResponses, "<placeholder for DB error.>");
        }
        else
        {
          array_push($dbResponses, $result);
        }
      }
      else 
      {
        array_push($dbResponses, "");  // No dbResponse because no DB call was made
      }
    }
    
    $parent = array
    (
        "curlResults"   => $curlResults,
        "curlResponses" => $curlResponses,
        "curlErrors"    => $curlErrors//,
        //"dbResponses"   => $dbResponses
    );
    saveToErrorLog(json_encode($parent), __FUNCTION__ . " - Subscribe");
    array_push ($devices, $parent);;
  }

  return $devices;
}*/



/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* FUNCTION:    subscribeDeviceGroupToTopics
 * DESCRIPTION: Subscribes the device group of the specified uid to all of the
 *              input topics' IDs for the specified type of object (User,
 *              User Image, Event, etc.).
 * USE CASES:
 *   - Subscribe all of the devices (correctly the logged in user's) to all of the
 *     friends, events, etc., for example after befriending a user.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
/*
function subscribeDeviceGroupToTopics($uid, $objectTypeLabel, $topics)
{
  // IMPORT REQUIRED METHODS
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/ObjectType.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Firebase.php';

  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';

  $deviceGroupFrid = dbGetDeviceGroupFrid($uid);
  if (contains(json_encode($deviceGroupFrid), "responseType"))
    return json_encode($deviceGroupFrid);

  // Fetch the API_ACCESS_KEY from the GlobalVariables
  $path_gv        = $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Resources/GlobalVariables.json';
  $file_gv        = file_get_contents($path_gv);
  $array_gv       = json_decode($file_gv, true);
  $API_ACCESS_KEY = $array_gv["Firebase"]["API_ACCESS_KEY"];
  $HEADER         = array(
      'Content-Type:application/json',
      'Content-Length:0',
      'Authorization:key=' . $API_ACCESS_KEY
  );

  // Fetch the object type code for the specified objectTypeLabel
  // Ex: x for the User, y for the User Image, z for the Event
  $objectTypeCode = dbGetObjectTypeCode($objectTypeLabel);
  if (contains(json_encode($objectTypeCode), "responseType"))
    return $objectTypeCode;

  $devices = array();

  // Form the URLs for cURL
  // URL TEMPLATE: "https://iid.googleapis.com/iid/v1/REGISTRATION_TOKEN/rel/topics/TOPIC_NAME"
  $url = "https://iid.googleapis.com/iid/v1/$deviceGroupFrid/rel/topics/";

  $curlResults = array();
  $curlResponses = array();
  $curlErrors = array();

  foreach ($topics as $id)
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url . $objectTypeCode . "." . $id);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADER);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    array_push($curlResults, curl_exec($curl));
    array_push($curlError = curl_error($curl));
    array_push($curlResponses, curl_getinfo($curl, CURLINFO_HTTP_CODE));
    curl_close($curl);
  }

  $dbResponses = array();
  for ($i = 0; $i < sizeof($topics); $i++)
  {
    if ($curlResponses[$i] === 200)
    {
      $result = dbSubscribeDeviceGroupToTopic($uid, $objectTypeCode . "." . $topics[$i]);
      if (!contains($result, "Device group has been successfully subscribed to topic."))
      {
        // TO-DO: If the DB insert fails and the device is not subscribed to the topic,
        // unsubscribe the device from the topic in FCM.
        array_push($dbResponses, "<placeholder for DB error.>");
      }
      else
      {
        array_push($dbResponses, $result);
      }
    }
    else
    {
      array_push($dbResponses, "");  // No dbResponse because no DB call was made
    }
  }

  $parent = array
  (
      "curlResults"   => $curlResults,
      "curlResponses" => $curlResponses,
      "curlErrors"    => $curlErrors//,
      //"dbResponses"   => $dbResponses
  );
  saveToErrorLog(json_encode($parent), __FUNCTION__ . " - Subscribe");
  array_push ($devices, $parent);;

  return $devices;
}*/



/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* This method may no longer be needed as it was replaced by device group one */

/* FUNCTION:    unsubscribeDevicesFromTopics
 * DESCRIPTION: Unsubscribes the devices with the specified Firebase Registration
 *              Identifiers from all of the input topics' IDs for the specified 
 *              type of object (User, User Image, Event, etc.).
 * USE CASES:
 *   - Unsubscribe all of the devices (correctly the logged in user's) from all of 
 *     the friends, events, etc., for example after unfriending a user. 
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
/*
function unsubscribeDevicesFromTopics($frids, $objectTypeLabel, $topics)
{
  // IMPORT REQUIRED METHODS
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/ObjectType.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Firebase.php';

  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';

  // Fetch the API_ACCESS_KEY from the GlobalVariables
  $path_gv        = $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Resources/GlobalVariables.json';
  $file_gv        = file_get_contents($path_gv);
  $array_gv       = json_decode($file_gv, true);
  $API_ACCESS_KEY = $array_gv["Firebase"]["API_ACCESS_KEY"];
  $HEADER = array(
      'Content-Type:application/json',
      'Authorization:key=' . $API_ACCESS_KEY
  );
  
  // Form the URLs for cURL
  $url = "https://iid.googleapis.com/iid/v1:batchRemove";

  // Fetch the object type code for the specified objectTypeLabel
  // Ex: x for the User, y for the User Image, z for the Event
  $objectTypeCode = dbGetObjectTypeCode($objectTypeLabel);
  if (contains(json_encode($objectTypeCode), "responseType"))
    return $objectTypeCode;

  $devices = array();

  //{
  //"to": "/topics/1-4",
  //"registration_tokens": ["d_QhVbrCImw:APA91bHg6xnjlDLNsPoFLuA-0Ardl5E02triGsCb3WmwdOmqymyNM6cZuDcB6Eil_fl9GLtu-c8_MsPnvnXegmSF07ms0HCMjTVHPxLMe-6zACdvBoYinYgc3T8YycgbEggrhVywLSgB"]
  //}

  foreach ($frids as $frid)
  { 
    foreach ($topics as $id)
    {
      $curlResults = array();
      $curlResponses = array();
      $curlErrors = array();
  
      $to = "/topics/" . $objectTypeCode . "." . $id;
      $registration_tokens = $frids;
      $message = array
      (
          "to" => $to, 
          "registration_tokens" => $registration_tokens
      );
  
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADER);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($message));
      array_push($curlResults, curl_exec($curl));
      array_push($curlErrors, curl_error($curl));
      array_push($curlResponses, curl_getinfo($curl, CURLINFO_HTTP_CODE));
      curl_close($curl);
  
      $dbResponses = array();
      for ($i = 0; $i < sizeof($topics); $i++)
      {
        if ($curlResponses[$i] === 200)
        {
          $result = dbUnsubscribeDeviceFromTopic($frid, $objectTypeCode . "." . $topics[$i]);
          array_push($dbResponses, $result);
        }
        else
        {
          array_push($dbResponses, "");  // No dbResponse because no DB call was made
        }
      }
    }

    $parent = array
    (
        "curlResults"   => $curlResults,
        "curlResponses" => $curlResponses,
        "curlErrors"    => $curlErrors,
        "dbResponses"   => $dbResponses
    );
    array_push ($devices, $parent);;
  }
  
  saveToErrorLog(json_encode($devices), __FUNCTION__ . " - Unsubscribe");

  return $devices;
}*/



/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* FUNCTION:    unsubscribeDeviceGroupFromTopics
 * DESCRIPTION: Unsubscribes the device group with the specified device group 
 *              Firebase Registration Identifier for the user with the input UID
 *              for the specified type of object (User, User Image, Event, etc.).
 * USE CASES:
 *   - Unsubscribe all of the devices for the currently logged in user from all of
 *     the friends, events, etc., for example after unfriending a user.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
/*
function unsubscribeDeviceGroupFromTopics($uid, $objectTypeLabel, $topics)
{
  // IMPORT REQUIRED METHODS
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/ObjectType.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Firebase.php';

  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';

  // Fetch the API_ACCESS_KEY from the GlobalVariables
  $path_gv        = $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Resources/GlobalVariables.json';
  $file_gv        = file_get_contents($path_gv);
  $array_gv       = json_decode($file_gv, true);
  $API_ACCESS_KEY = $array_gv["Firebase"]["API_ACCESS_KEY"];
  $HEADER = array(
      'Content-Type:application/json',
      'Authorization:key=' . $API_ACCESS_KEY
  );
  
  $deviceGroupFrid = dbGetDeviceGroupFrid($uid);
  if (contains(json_encode($deviceGroupFrid), "responseType"))
    return json_encode($deviceGroupFrid);

  // Form the URLs for cURL
  $url = "https://iid.googleapis.com/iid/v1:batchRemove";

  // Fetch the object type code for the specified objectTypeLabel
  // Ex: x for the User, y for the User Image, z for the Event
  $objectTypeCode = dbGetObjectTypeCode($objectTypeLabel);
  if (contains(json_encode($objectTypeCode), "responseType"))
    return $objectTypeCode;

  $devices = array();

  //{
  //"to": "/topics/1-4",
  //"registration_tokens": ["d_QhVbrCImw:APA91bHg6xnjlDLNsPoFLuA-0Ardl5E02triGsCb3WmwdOmqymyNM6cZuDcB6Eil_fl9GLtu-c8_MsPnvnXegmSF07ms0HCMjTVHPxLMe-6zACdvBoYinYgc3T8YycgbEggrhVywLSgB"]
  //}

  foreach ($topics as $id)
  {
    $curlResults = array();
    $curlResponses = array();
    $curlErrors = array();

    $to = "/topics/" . $objectTypeCode . "." . $id;
    $registration_tokens = $deviceGroupFrid;
    $message = array
    (
        "to" => $to,
        "registration_tokens" => $registration_tokens
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADER);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($message));
    array_push($curlResults, curl_exec($curl));
    array_push($curlErrors, curl_error($curl));
    array_push($curlResponses, curl_getinfo($curl, CURLINFO_HTTP_CODE));
    curl_close($curl);

    $dbResponses = array();
    for ($i = 0; $i < sizeof($topics); $i++)
    {
      if ($curlResponses[$i] === 200)
      {
        $result = dbUnsubscribeDevicesFromTopic($uid, $objectTypeCode . "." . $topics[$i]);
        array_push($dbResponses, $result);
      }
      else
      {
        array_push($dbResponses, "");  // No dbResponse because no DB call was made
      }
    }
  }

  $parent = array
  (
      "curlResults"   => $curlResults,
      "curlResponses" => $curlResponses,
      "curlErrors"    => $curlErrors,
      "dbResponses"   => $dbResponses
  );
  array_push ($devices, $parent);;

  saveToErrorLog(json_encode($devices), __FUNCTION__ . " - Unsubscribe");

  return $devices;
} */



/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* !!!!! PLANNING TO DEPRECATE THIS !!!!! */
/* This method will be replaced by the more general "ToTopics" method */

/* FUNCTION:    subscribeDeviceToFriendUserTopics
 * DESCRIPTION: Subscribes the device with the specified Firebase Registration
 *              Identifier to all of the input friends' (users) UIDs.
 * --------------------------------------------------------------------------------
 * ================================================================================
 * -------------------------------------------------------------------------------- */
/*
function subscribeDevicesToEvent($devices, $eid)
{
  // IMPORT REQUIRED METHODS
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Functions/Miscellaneous.php';
  require_once $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/Firebase.php';

  // IMPORT THE DATABASE CONNECTION
  require $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/DBIO/_DBConnect.php';

  // Fetch the API_ACCESS_KEY from the GlobalVariables
  $path_gv        = $_SERVER['DOCUMENT_ROOT'] . '/BoppoServer/Resources/GlobalVariables.json';
  $file_gv        = file_get_contents($path_gv);
  $array_gv       = json_decode($file_gv, true);
  $API_ACCESS_KEY = $array_gv["Firebase"]["API_ACCESS_KEY"];
  $HEADER         = array(
      'Content-Type:application/json',
      'Content-Length:0',
      'Authorization:key=' . $API_ACCESS_KEY
  );
  
  $deviceFrids = $devices["deviceFrids"];
  
  $curlResults = array();
  $curlResponses = array();
  $curlErrors = array();
  $dbResponses = array();
  foreach ($deviceFrids as $frid)
  {
    // Form the URLs for cURL
    // URL TEMPLATE: "https://iid.googleapis.com/iid/v1/REGISTRATION_TOKEN/rel/topics/TOPIC_NAME"
    $url = "https://iid.googleapis.com/iid/v1/$frid/rel/topics/";

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url . "eid-" . $eid);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $HEADER);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    array_push($curlResults, curl_exec($curl));
    array_push($curlErrors, curl_error($curl));
    array_push($curlResponses, curl_getinfo($curl, CURLINFO_HTTP_CODE));
    curl_close($curl);
    
    if ($curlResponses[count($curlResponses)-1] === 200)
    {
      $result = dbSubscribeDeviceToTopic($frid, "eid-" . $eid);
      array_push($dbResponses, $result);
    }
    else 
    {
      array_push($dbResponses, formatResponseError(
        "The device has not been subscribed to topic in database because it failed to be registered to topic in FCM. ")
      );
    }
  }
  
  $parent = array
  (
      "curlResults"   => $curlResults,
      "curlResponses" => $curlResponses,
      "curlErrors"    => $curlErrors,
      "dbResponses"   => $dbResponses
  );
  
  return $parent;
}
*/

?>