<?php
/*
	Copyright 2014 Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
require_once "../../json_begin.php";

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

	$r['success']	= true;
	$r['data']		= $rs;
	$r['total']		= count ($rs);
}
catch (Exception $e) {
	$r['data']		= $e->getMessage ();
}

require_once "../../json_end.php";
