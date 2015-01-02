<?php
/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
	$pi = pathinfo ($_FILES["content"]["name"]);
	$fupath = Jaring::$_media_dir . sha1_file ($_FILES["content"]["tmp_name"]);

	$bindv		= [];
	$bindv[]	= Jaring::$_c_profile_id;
	$bindv[]	= Jaring::$_db->generate_id ();
	$bindv[]	= ("" === $_POST["name"]
					? $pi["filename"]
					: $_POST["name"]);
	$bindv[]	= $pi["extension"];
	$bindv[]	= $_FILES["content"]["size"];
	$bindv[]	= $_FILES["content"]["type"];
	$bindv[]	= $_POST["description"];
	$bindv[]	= $fupath;

	Jaring::$_db->execute_insert (Jaring::$_mod["db_table"]["name"]
							, Jaring::$_mod["db_table"]["create"]
							, $bindv);

	move_uploaded_file ($_FILES["content"]["tmp_name"], APP_PATH ."/". $fupath);

	Jaring::$_out->set (true, self::$MSG_SUCCESS_CREATE);
