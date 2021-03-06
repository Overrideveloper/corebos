<?php
/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/
require_once('include/database/PearDatabase.php');
global $adb,$current_user;

if (!empty($_REQUEST['hour_format'])) {
	$hour_format = vtlib_purify($_REQUEST['hour_format']);
} else {
	$hour_format = '24';
}

$activity_view = $_REQUEST['activity_view'];

$user_view = $_REQUEST['user_view'];

$delquery = "delete from vtiger_sharedcalendar where userid=?";
$adb->pquery($delquery, array($_REQUEST["current_userid"]));

$selectedid = $_REQUEST['shar_userid'];
$sharedid = explode (";",$selectedid);
if (isset($sharedid) && $sharedid != null) {
	foreach ($sharedid as $sid) {
		if ($sid != '') {
			$sql = "insert into vtiger_sharedcalendar values (?,?)";
			$adb->pquery($sql, array($_REQUEST["current_userid"], $sid));
		}
	}
}
if (isset($_REQUEST['start_hour']) && $_REQUEST['start_hour'] != '') {
	$sql = "update vtiger_users set start_hour=? where id=?";
	$adb->pquery($sql, array($_REQUEST['start_hour'], $current_user->id));
}

$sql = "update vtiger_users set hour_format=?, activity_view=? where id=?";
$adb->pquery($sql, array($hour_format, $activity_view, $current_user->id));

$dayoftheweek = $_REQUEST["dayoftheweek"];

if (isset($_REQUEST["show_weekends"]) && $_REQUEST["show_weekends"] == "1")
	$show_weekends = "1";
else
	$show_weekends = "0";

$sql2 = "SELECT * FROM its4you_calendar4you_settings WHERE userid=?";
$result2 = $adb->pquery($sql2, array($current_user->id));
$num_rows2 = $adb->num_rows($result2);

if ($num_rows2 > 0) {
	$sql3 = "UPDATE its4you_calendar4you_settings SET dayoftheweek=?, show_weekends=?, user_view = ? WHERE userid=?";
	$adb->pquery($sql3, array($dayoftheweek, $show_weekends, $user_view, $current_user->id));
} else {
	$sql3 = "INSERT INTO its4you_calendar4you_settings (userid, dayoftheweek, show_weekends, user_view) VALUES  (?,?,?,?)";
	$adb->pquery($sql3, array($current_user->id, $dayoftheweek, $show_weekends, $user_view));
}

$update_google_account = (isset($_REQUEST['update_google_account']) ? $_REQUEST['update_google_account'] : '0');

if ($update_google_account == "1") {
	$google_login = vtlib_purify($_REQUEST['google_login']);
	// $google_password = vtlib_purify($_REQUEST['google_password']);
	$google_apikey= vtlib_purify($_REQUEST['google_apikey']);
	$google_keyfile = vtlib_purify($_REQUEST['google_keyfile']);
	$google_clientid = vtlib_purify($_REQUEST['google_clientid']);
	$googleinsert1 = (isset($_REQUEST['googleinsert']) ? vtlib_purify($_REQUEST['googleinsert']) : '');

	$us=$adb->query("select * from vtiger_users where deleted=0");
	for ($i=0;$i<$adb->num_rows($us);$i++) {
		$userid=$adb->query_result($us,$i,'id');
		$q=$adb->pquery("select refresh_token,googleinsert from its4you_googlesync4you_access WHERE userid = ?",array($userid));
		$refresh_token=$adb->query_result($q,0,"refresh_token");
		if ($adb->num_rows($q)!=0 && $userid!=$current_user->id)
			$googleinsert = $adb->query_result($q,0,"googleinsert");
		else if ($userid != $current_user->id )
			$googleinsert = 1;
		else
			$googleinsert=$googleinsert1;
		$sql4 = "DELETE FROM its4you_googlesync4you_access WHERE userid = ?";
		$adb->pquery($sql4,array($userid));
		if ($google_login != "" && $google_apikey != "" && $google_keyfile != "" && $google_clientid!= "") {
			$sql5 = "INSERT INTO its4you_googlesync4you_access (userid, google_login, google_password,google_apikey,google_keyfile,google_clientid,refresh_token) VALUES (?,?,?,?,?,?,?)";
			$adb->pquery($sql5,array($userid,$google_login, $google_password,$google_apikey,$google_keyfile,$google_clientid,$refresh_token));
			$adb->pquery('Update its4you_googlesync4you_access set googleinsert=? where userid=?',array($googleinsert,$userid));
		}
	}
}

RecalculateSharingRules();
header("Location: index.php?action=index&module=Calendar4You&viewOption=".vtlib_purify($_REQUEST['view'])."&hour=".vtlib_purify($_REQUEST['hour'])."&day=".vtlib_purify($_REQUEST['day'])."&month=".vtlib_purify($_REQUEST['month'])."&year=".vtlib_purify($_REQUEST['year'])."&user_view_type=".$user_view."&parenttab=".getParentTab());
?>