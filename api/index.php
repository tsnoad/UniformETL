<?php

require_once("/etc/uniformetl/database.php");
require_once("/etc/uniformetl/autoload.php");
require_once("/etc/uniformetl/transform/transform_models.php");

// Place where things happen: the API class works out what needs to be done, and which model it needs to use to do it.
Class API {
	// Thunderbirds are go!
	function init() {
		//check that we're protected by SSL, and that the request has used a valid api key
		$this->check_auth();

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

	// Check that we're protected by SSL, and that the request has used a valid api key
	function check_auth() {
		//make sure that we're protected by SSL
		if (empty($_SERVER['HTTPS'])) {
			header("HTTP/1.1 401 Unauthorized");
			die("HTTP/1.1 401 Unauthorized");
		}

		//make sure that the request has used a valid api key
		if (empty($_REQUEST['api_key']) || $_REQUEST['api_key'] != Conf::$api_key) {
			header("HTTP/1.1 401 Unauthorized");
			die("HTTP/1.1 401 Unauthorized");
		}
	}

	// Calls all the models that we might need
	function init_models() {
		//call all the models
		$this->users_model = New APIModelUsers;
		$this->user_model = New APIModelUser;
		$this->userlogin_model = New APIModelUserLogin;
		$this->passwordupdates_model = New APIModelPasswordUpdates;
		$this->passwords_model = New APIModelPasswords;
		$this->nmep_model = New APIModelNmep;
		$this->teapot_model = New APIModelTeapot;
	
		//add all the models to an array, so that we can loop through them
		$this->models = array(
			$this->users_model,
			$this->user_model,
			$this->userlogin_model,
			$this->passwordupdates_model,
			$this->passwords_model,
			$this->nmep_model,
			$this->teapot_model,
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