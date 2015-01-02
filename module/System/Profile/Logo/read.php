<?php
/*
	Copyright 2014 Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
$no_cookies = true;
require_once "../../../init.php";

Jaring::$_db->init ();

$q = "select logo_type, logo from _profile where id = ?";

$ps = Jaring::$_db->_dbo->prepare ($q);

$ps->execute (array ($_GET["_profile_id"]));

$ps->bindColumn (1, $type, PDO::PARAM_STR);
$ps->bindColumn (2, $lob, PDO::PARAM_LOB);
$ps->fetch (PDO::FETCH_BOUND);

header ("Content-Type: $type");
echo $lob;
