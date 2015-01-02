<?php
/*
	Copyright 2014 Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
function get_group ($pid, $depth)
{
	$q	="
			select		A._profile_id
			,			A.id
			,			A.pid
			,			A.name
			,			A.name		as text
			from		_group		A
			where		A._profile_id	= ?
			and			A.pid			= ?
			order by	A.id
		";

	$rs = Jaring::$_db->execute ($q
			, array (Jaring::$_c_profile_id, $pid)
			, true);

	$index = 0;
	foreach ($rs as &$m) {
		$id = $m["id"];

		if ($index === 0) {
			$m["isFirst"] = true;
		} else {
			$m["isFirst"] = false;
		}

		$m["iconCls"]	= "group";
		$m["index"] 	= $index++;
		$m["depth"]		= $depth;

		$c = get_group ($id, $depth + 1);

		if (count ($c) <= 0) {
			$m["leaf"]			= true;
		} else {
			$m["data"]			= $c;
			$m["expandable"]	= true;
			$m["expanded"]		= true;
			$m["loaded"]		= true;
		}
	}

	return $rs;
}

$data = get_group (0, 0);

Jaring::$_out->set (true, (array) $data);
