<?php
require_once "../../../json_begin.php";

try {
	$pid	= $_GET["pid"];

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
		where	A.type		in (2,3)
		and		A.id		= B._menu_id
		and		B._group_id	= C._group_id
		and		C._user_id	= ?
		and		A.pid		= ?
		";

	$ps = Jaring::$_db->prepare ($q);
	$i	= 1;
	$ps->bindValue ($i++, Jaring::$_c_uid, PDO::PARAM_INT);
	$ps->bindValue ($i++, $pid, PDO::PARAM_INT);
	$ps->execute ();
	$rs = $ps->fetchAll (PDO::FETCH_ASSOC);

	foreach ($rs as &$m) {
		$m["image_path"] = str_replace (" ", "-", (string) $m["image"]);
	}

	$r['success']	= true;
	$r['data']		= $rs;
	$r['total']		= count ($rs);
}
catch (Exception $e) {
	$r['data']		= $e->getMessage ();
}

require_once "../../../json_end.php";