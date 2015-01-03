<?php
/*
	Copyright 2014 Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
require_once "../../json_begin.php";

function get_menu ($pid)
{
	$q	="
		select	A.id
		,		A.label
		,		A.image
		,		A.description
		,		A.module
		,		B.permission
		from	_menu		A
		,		_group_menu	B
		,		_user_group	C
		where	A.type			in (2,3)
		and		A.id			= B._menu_id
		and		B._group_id		= C._group_id
		and		C._user_id		= ?
		and		A.pid			= ?
		and		C._profile_id	= ?
		";

	$rs = Jaring::$_db->execute ($q
			, array (Jaring::$_c_uid, $pid, Jaring::$_c_profile_id)
			);

	foreach ($rs as &$m) {
		$m["image_path"] = str_replace (" ", "-", (string) $m["image"]);
	}

	return $rs;
}

try {
	$q	="
		select	distinct
				A.id
		,		A.label		as title
		from	_menu		A
		,		_group_menu	B
		,		_user_group	C
		where	A.pid			= 0
		and		A.id			> 2
		and		A.id			= B._menu_id
		and		B._group_id		= C._group_id
		and		B.permission	> 0
		and		C._user_id		= ?
		and		C._profile_id	= ?
		order by A.pid, A.id asc
		";

	$rs = Jaring::$_db->execute ($q
			, array (Jaring::$_c_uid, Jaring::$_c_profile_id)
			, true);

	foreach ($rs as &$row) {
		$row["data"] = get_menu ($row["id"]);
	}

	$r['success']	= true;
	$r['data']		= $rs;
	$r['total']		= count ($rs);
}
catch (Exception $e) {
	$r['data']		= $e->getMessage ();
}

require_once "../../json_end.php";
