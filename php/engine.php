<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require 'db.php';
require 'tmhOAuth.php';

class twitter {
	
	private $connection;
	private $tts;
	private $q = '#loftchurch';
	
	function __construct () {//set api keys and get new tweets
		
		require 'app_tokens.php';
		
		$this->connection = new tmhOAuth(array(
			'host'		    => 'api.twitter.com',
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'user_token'      => $user_token,
			'user_secret'     => $user_secret
		));
		
		$stmt = db::connect()->prepare('SELECT * FROM tiles WHERE id=1');
		$stmt->execute();
		$result = $stmt->fetchAll();
		
		if (count($result)) { 	

			foreach($result as $row) {
				
				$row['search'] != '' ? $this->q = $row['search'] : null;
				$this->tts = DateTime::createFromFormat('Y-m-d H:i:s', $row['timestamp']);//get most recent timestamp from database
			}
			
		}
		
		$this->sendRequest();//send authenticated request
		
	}
	
	private function wordFilter($text) {//filter curse words
		
		$filter_terms = array('\bass(es|holes?)?\b', '\bshit(e|ted|ting|ty|head)\b');
		$filtered_text = $text;
		foreach($filter_terms as $word) {
			
			$match_count = preg_match_all('/' . $word . '/i', $text, $matches);
			
			for($i = 0; $i < $match_count; $i++) {
				
					$bwstr = trim($matches[0][$i]);
					$filtered_text = preg_replace('/\b' . $bwstr . '\b/', str_repeat("*", strlen($bwstr)), $filtered_text);
					
				}
		}
		return $filtered_text;
	}

	private function sendRequest () {//send authenticated request
		
		$this->connection->request('GET', $this->connection->url('1.1/search/tweets'), array(
			'q' => $this->q,
			'result_type'  => 'recent',
			'count' => '50'
		));
		
		// Get the HTTP response code for the API request
		$response_code = $this->connection->response['code'];		
		
		// A response code of 200 is a success
		if ($response_code <> 200) {
			
			echo "Error: $response_code\n";
		
		}
	}
	
	private function getTimestamp ($ts) {//convert twitter timestamp to datetime
		
		$ts = explode(' ', $ts);
		return date('Y-m-d', strtotime($ts[1].' '.$ts[2].' '.$ts[5])). ' '.$ts[3];
		
	}

	public function getTweets () {

		$push = array();
		// Convert the JSON response into an array
		$response_data = json_decode($this->connection->response['response'],true);
		// Display the response array
		$i = 0;
		$url_pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
		
		foreach ($response_data['statuses'] as $tweet) {

			$ts = DateTime::createFromFormat('Y-m-d H:i:s', $this->getTimestamp($tweet['created_at']));//get twitter timestamp
			
			if (preg_match('/RT @/', $tweet['text']) === 0) {//if not a retweet

				if ($ts > $this->tts) {//build tweet array if new tweet
				
				
					if (isset($tweet['entities']['media'][0]['media_url'])) {
							$push[$i]['media_url'] = '<img src="' . $tweet['entities']['media'][0]['media_url'] . '">';
					} else {
							$push[$i]['media_url'] = '';
					}
					
					if (isset($tweet['entities']['urls'][0]['display_url'])) {
						if (strrpos($tweet['entities']['urls'][0]['display_url'], 'vine') !== false) {
							$push[$i]['media_url'] = '<iframe class="vine-embed" src="https://' . $tweet['entities']['urls'][0]['display_url'] . '/embed/simple" width="275" height="220" frameborder="0"></iframe>';
							$display_url = '';
						} else {
							$display_url = $tweet['entities']['urls'][0]['display_url'];
						}
					} else if (!isset($tweet['entities']['urls'][0]['display_url'])) {
							$display_url = '';
					}
					
					$push[$i]['id_str'] =  $tweet['id_str'];
					$push[$i]['created_at'] =  $ts->format('Y-m-d H:i:s');
					$push[$i]['name'] =  $this->wordFilter($tweet['user']['name']);
					$push[$i]['screen_name'] =  '@'.$this->wordFilter($tweet['user']['screen_name']);
					$push[$i]['profile_image_url'] =  '<img src="'.$tweet['user']['profile_image_url'].'">';
					$push[$i]['text'] =   $this->wordFilter(preg_replace($url_pattern, $display_url, str_replace('#loftchurch', '<span class="hashtag">#loftchurch</span>', $tweet['text'])));
					
					$i++;
	
				}
			}
		}

		if (count($push) >= 1) {//add new timestamp to database if newer
			$stmt = db::connect()->prepare('UPDATE tiles SET timestamp=:timestamp WHERE id=1');
			$stmt->execute(array(
				':timestamp' => $push[0]['created_at']
			));
		}
		
		$i = 0;//reset array indexes
				
		echo json_encode($push);//build new tweet json
		
	}
}

if (isset($_POST['reset'])) {

	$stmt = db::connect()->prepare('UPDATE tiles SET timestamp=:timestamp WHERE id=1');

	$stmt->execute(array(

		':timestamp' => '0000-00-00 00:00:00'

	));

} else {

	$twitter = new twitter;

	$twitter->getTweets();

}
?>
