<?php
/*
	Copyright 2014 Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
require_once "../../init.php";

$fields	= [
	"_profile_id"
,	"id"
,	"name"
,	"realname"
,	"password"
];

Jaring::$_mod["db_table"]["name"]	= "_user";
Jaring::$_mod["db_table"]["read"]	=
									[
										"_profile_id"
									,	"id"
									,	"name"
									,	"realname"
									,	"password as password_old"
									];
Jaring::$_mod["db_table"]["search"]	= ["name", "realname"];
Jaring::$_mod["db_table"]["order"]	= ["name"];
Jaring::$_mod["db_table"]["create"]	= $fields;
Jaring::$_mod["db_table"]["update"]	= array_slice ($fields, 2);

function request_read_after (&$data)
{
	$q	="
		select	G.name
		from	_group		G
		,		_user_group	UG
		where	UG._group_id	= G.id
		and		UG._user_id		= ?
		";

	$ps = Jaring::$_db->_dbo->prepare ($q);

	foreach ($data as &$d) {
		$ps->execute (array ($d["id"]));

		$rs = $ps->fetchAll (PDO::FETCH_COLUMN, 0);

		$ps->closeCursor ();

		// add empty password.
		$d["password"] = "";

		// add user's group name to result set.
		$d["group_name"] = implode (",", $rs);
	}
}

function request_create_before (&$data)
{
	foreach ($data as &$d) {
		$d["password"] = hash ("sha256", $d["password"]);
	}

	return true;
}

function request_update_before (&$data)
{
	foreach ($data as &$d) {
		if (empty ($d["password"])) {
			$d["password"] = $d['password_old'];
		} else {
			$d["password"] = hash ("sha256", $d['password']);
		}
	}

	return true;
}

// Disallow user to delete super admin (id is 1)
function request_delete_before ($data)
{
	foreach ($data as $d) {
		$user_id = $d["id"];

		if ($user_id === 1 || $user_id === "1") {
			throw new Exception (Jaring::$MSG_DATA_LOCK);
		}

		$q = "
			select	count(_user_id) as n
			from	_profile_admin
			where	_user_id = $user_id
			";

		$rs = Jaring::$_db->execute ($q, null);

		if ((int) $rs[0]["n"] > 0) {
			throw new Exception (Jaring::$MSG_ADMIN_PROFILE);
		}
	}

	return true;
}

Jaring::request_handle ("crud");
