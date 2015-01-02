<?php
/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
require_once "../../../init.php";

try {
	Jaring::db_init ();

	if (UPLOAD_ERR_OK === $_FILES["logo"]["error"]) {
		$q = "update _profile set logo_type = ? , logo = ?";

		$ps = Jaring::$_db->_dbo->prepare ($q);

		$fp = fopen ($_FILES["logo"]["tmp_name"], "rb");

		$i = 1;
		$ps->bindParam ($i++, $_FILES["logo"]["type"]);
		$ps->bindParam ($i++, $fp, PDO::PARAM_LOB);

		Jaring::$_db->_dbo->beginTransaction ();
		$ps->execute ();
		Jaring::$_db->_dbo->commit ();

		Jaring::$_out->set (true, "New logo has been uploaded");
	} else {
		Jaring::$_out->data = $_FILES["logo"]["error"];
	}
} catch (Exception $e) {
	Jaring::$_out->data = addslashes ($e->getMessage ());
}

header('Content-Type: application/json');
echo json_encode (Jaring::$_out, JSON_NUMERIC_CHECK);
