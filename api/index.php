<?php

require_once("/etc/uniformetl/config.php");
require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/transform/transform_models.php");

require_once("/etc/uniformetl/api/model_users.php");
require_once("/etc/uniformetl/api/model_user.php");
require_once("/etc/uniformetl/api/model_userlogin.php");
require_once("/etc/uniformetl/api/model_teapot.php");

/*
function returnXMLData($data) {
	header("HTTP/1.1 200 OK");
	echo "<?xml version=\"1.0\"?>\n";
	echo "<user>\n";

	foreach ($data as $data_element_name => $data_element) {
		if (is_array($data_element)) {
			echo "\t<{$data_element_name}>\n";

			foreach ($data_element as $data_group) {
				echo "\t\t<".substr($data_element_name, 0, -1).">\n";

				foreach ($data_group as $data_group_element_name => $data_group_element) {
					echo "\t\t\t<{$data_group_element_name}>{$data_group_element}</{$data_group_element_name}>\n";
				}

				echo "\t\t</".substr($data_element_name, 0, -1).">\n";
			}

			echo "\t</{$data_element_name}>\n";
		} else {
			echo "\t<{$data_element_name}>{$data_element}</{$data_element_name}>\n";
		}
	}

	echo "</user>\n";
}
*/

// Place where things happen: the API class works out what needs to be done, and which model it needs to use to do it.
Class API {
	// Thunderbirds are go!
	function init() {
		//call all the models that we might need
		$this->init_models();

		//based on the request, which model do we need to use
		$model = $this->choose_model_for_situation();

		//couldn't find a model
		if (!$model) {
			header("HTTP/1.1 400 Bad Request");
			die("HTTP/1.1 400 Bad Request");
		}

		//use the model to do stuff
		$model->what_do_i_do();
	}

	// Calls all the models that we might need
	function init_models() {
		//call all the models
		$this->users_model = New Users;
		$this->user_model = New User;
		$this->userlogin_model = New UserLogin;
		$this->teapot_model = New Teapot;
	
		//add all the models to an array, so that we can loop through them
		$this->models = array(
			$this->users_model,
			$this->user_model,
			$this->userlogin_model,
			$this->teapot_model
		);
	}

	// Ask each model if they're the one who can process the request
	function choose_model_for_situation() {
		//loop through all the models
		foreach ($this->models as $model) {
			//run the model's who_me() function
			if (call_user_func(array($model, "who_me"))) {
				//this model can process the request
				return $model;
			} else {
				//nope, try the next model
				continue;
			}
		}

		//If we're here then we coudn't find a model who could process the request
		return false;
	}
}

$api = New API;
$api->init();

?>