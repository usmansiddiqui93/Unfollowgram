<?php
require_once 'Instagram.php';
use MetzWeb\Instagram\Instagram;

    //Config instagram & db
    $instagram = new Instagram(array(
      'apiKey'      => 'api key',
      'apiSecret'   => 'api secret',
      'apiCallback' => 'api callback'
    ));

    $db = new mysqli('db host', 'db user', 'db pass','db name');

    //Redirect to Twitter API if there's no request
    	if(empty($_GET['code'])){
    	
    		header('Location: '.$instagram->getLoginUrl());
		
		}else{
			#user data
			$data = $instagram->getOAuthToken($_GET['code']);
			
			#user id
			$id = $data->user->id;

			#set token
			$instagram->setAccessToken($data);

			#get actual timestamp
			$actual = date("Y-m-d H:i:s");
	
			#get followers and count
			$follower = $instagram->getUserFollower($id, 1000);
			$num_items = count($follower->data);
	
			#add new followers, update current
				for($i=0; $i<$num_items; $i++){
					if($db->query('SELECT id FROM followers WHERE id="'.$follower->data[$i]->id.'" AND user="'.$id.'"')->num_rows == 0){
					
						$db->query('INSERT INTO followers (user, id, username, status) VALUES ("'.$id.'","'.$follower->data[$i]->id.'", "'.$follower->data[$i]->username.'", "'.$actual.'")');
		
					}elseif($db->query('SELECT id FROM followers WHERE id="'.$follower->data[$i]->id.'" AND user="'.$id.'"')->num_rows > 0){

						$db->query('UPDATE followers SET status="'.$actual.'" WHERE id="'.$follower->data[$i]->id.'" AND user="'.$id.'"');	

					}
				}	

			echo '*Current followers saved* <br>';

			#check followers who didn't update, let's call them unfollowers
				$count = $db->query('SELECT * FROM followers WHERE user="'.$id.'"')->num_rows;
			
				echo '*Unfollowers* <br>';
				if($count == 0){

					echo 'Nobody unfollowed you :)';

				}else{

					$unfollowers = $db->query('SELECT * FROM followers WHERE user="'.$id.'"');

						while($unfollower = $unfollowers->fetch_object()){
			
							if($unfollower->status < $actual){
					
								$db->query('INSERT INTO unfollowers (user, id, username) VALUES ("'.$id.'", "'.$unfollower->id.'", "'.$unfollower->username.'")');
								$db->query('DELETE FROM followers WHERE id="'.$unfollower->id.'" AND user="'.$id.'"');	
			
								echo '@'.$unfollower->username.' stopped following you <br>';

								$db->query('DELETE FROM unfollowers WHERE id="'.$unfollower->id.'" AND user="'.$id.'"');	

							}

						}

				}
			}	

?>