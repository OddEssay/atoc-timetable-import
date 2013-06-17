<?php

$username = '';
$password = '';
$queue  = '/topic/TRAIN_MVT_HE_TOC';

/* connection */
try {
    $stomp = new Stomp('tcp://datafeeds.networkrail.co.uk:61618',$username,$password);
} catch(StompException $e) {
    die('Connection failed: ' . $e->getMessage());
}

$stomp->subscribe($queue);

while(true){
	/* read a frame */
	$frame = $stomp->readFrame();

	if ($frame) {
	    /* acknowledge that the frame was received */
	    $stomp->ack($frame);

	    $body = json_decode($frame->body);
	    foreach($body as $update){
	    	if( 
	    		isset($update->body->event_type)
	    		&& isset($update->body->train_id)
	    		&& isset($update->body->train_service_code)
	    		&& isset($update->body->variation_status)
	    	) {
	    		echo $update->body->event_type . ' of train '  . $update->body->train_id . ' on service id ' . $update->body->train_service_code . ' running ' . $update->body->variation_status . "\n";	
	    	} else {
	    		#print_r($update); // Debug a message that doesn't fit the primay data format.
	    	}
	    	
	    }
	}
}


/* close connection */
unset($stomp);

?>