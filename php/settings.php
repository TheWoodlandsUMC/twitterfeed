<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require 'db.php';

class settings {
	
	private $push;
	private $color;
	private $interval;
	
	function __construct() {//get setting from db
		
		$stmt = db::connect()->prepare('SELECT * FROM tiles WHERE id=1');
		$stmt->execute();
		$result = $stmt->fetchAll();
		
		if (count($result)) { 	

			foreach($result as $row) {
				$this->color = unserialize($row['color']);//set values from db
				$this->interval = $row['interval'];
			}
			
		}
		
	}
	
	public function getSettings () {//json call for settings
		
		$this->push['color'] = $this->color;
		$this->push['interval'] = $this->interval;
		echo json_encode($this->push);
		
	}
	
	public function setSettings () {//set values from settings form
		
		if (isset($_POST['interval']) && is_int(intval($_POST['interval'])) && $_POST['interval'] != '') {
			
			$stmt = db::connect()->prepare('UPDATE tiles SET `interval`=:interval WHERE id=1');
			$stmt->execute(array(
				':interval' => $_POST['interval']
			));
		}
		
		if (isset($_POST['color']) && $_POST['color'] != '') {
			
			$stmt = db::connect()->prepare('UPDATE tiles SET color=:color WHERE id=1');
			$stmt->execute(array(
				':color' => serialize(explode(',', $_POST['color']))
			));
			
		}
		
		if (isset($_POST['search']) && $_POST['search'] != '') {
			
			$stmt = db::connect()->prepare('UPDATE tiles SET search=:search WHERE id=1');
			$stmt->execute(array(
				':search' => $_POST['search']
			));
		}
		
        if (isset($_POST['timestamp'])) {
			
			$stmt = db::connect()->prepare('UPDATE tiles SET timestamp=:timestamp WHERE id=1');
			$stmt->execute(array(
				':timestamp' => '0000-00-00 00:00:00'
			));
			
		}
		
        echo 'done!';
    }
}
$settings = new settings;

if (isset($_POST['settings'])) {
	
	$settings->setSettings();
	
} else {
	
	$settings->getSettings();
	
}
?>
