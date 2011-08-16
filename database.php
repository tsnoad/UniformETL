<?php

require_once("/etc/uniformetl/autoload.php");

//get the specified database language from the config file
if (Conf::$dblang == "pgsql") {
	//load the right database language module
	require("/etc/uniformetl/database.pgsql.php");

//repeat for MySQL
} else if (Conf::$dblang == "mysql") {
	require("/etc/uniformetl/database.mysql.php");

//Argh, someone's trying to use a language we don't know
} else {
	throw new Exception("Don't know DB language: ".Conf::$dblang);
}

/**
 * Choose which language-specific piece of sql to use. Can be used in conjunction with db_mysql() and db_pgsql()
 *
 * Can be called in the following ways:
 *
 * db_choose(
 *	db_pgsql("postgres only sql"),
 *	db_mysql("mysql only sql")
 * )
 *
 * Or like this: db_choose(array(db_pgsql("postgres only"), db_mysql("mysql only")))
 *
 * Can even be called like this: db_choose(array(db_pgsql("postgres only")), db_mysql("mysql only"))
 */
function db_choose($choice1orchoices, $choice2=null, $choice3=null) {
	//create one big array with all the choices
	$choices = array_merge((array)$choice1orchoices, (array)$choice2, (array)$choice3);

	//if no choice is given for the language we're using
	if (!isset($choices[Conf::$dblang])) {
		//throw an error
		throw new Exception("No SQL given for ".Conf::$dblang);
	}

	//return the language-specific choice for the language we're using
	return $choices[Conf::$dblang];
};

/**
 * Provide some MySQL specific sql
 */
function db_mysql($query) {
	return array("mysql" => $query);
}

/**
 * Provide some postgres specific sql
 */
function db_pgsql($query) {
	return array("pgsql" => $query);
}

?>