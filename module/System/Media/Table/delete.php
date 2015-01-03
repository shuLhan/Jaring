<?php
/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
foreach ($data as $d) {
	//{{{ delete all file
	$q	="	select	M.path
			from	_media			M
			,		_media_table	MT
			where	M.id		= MT._media_id
			and		MT.table_id	= '". $d["table_id"] ."'"
		;

	$rs = Jaring::$_db->execute ($q);

	foreach ($rs as $k => $v) {
		$f = APP_PATH . $v["path"];

		if (file_exists ($f)) {
			unlink ($f);
		}
	}
	//}}}

	//{{{ delete data from _media_table
	$media_id	= $d["_media_id"];
	$bindv		= [];
	$bindv[]	= $d["table_id"];
	$bindv[]	= $media_id;

	$table		= "_media_table";
	$fids		= ["table_id", "_media_id"];

	Jaring::$_db->execute_delete ($table, $fids, $bindv);
	//}}}

	//{{{ delete data from media
	$q	=" delete from _media where id = ". $media_id;

	Jaring::$_db->execute ($q);
	//}}}
}

Jaring::$_out->set (true, self::MSG_SUCCESS_DESTROY);
