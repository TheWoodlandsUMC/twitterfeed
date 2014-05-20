<?php
require 'config.php';

class db {//singleton db connection

    private static $conn;
    
    private function __construct(){}

    public static function connect() {

		if (!isset(self::$conn)) {//prevents multiple objects of same db connection

			try {
				
				self::$conn = new PDO('mysql:host=' . host . ';dbname=' . dbname, username, password);
				self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
			} catch(PDOException $e) {
				
				echo 'Error: ' . $e->getMessage();
				
			}
		}
		
		return self::$conn;
		
    }
	
}
?>
