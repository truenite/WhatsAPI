<?php
// @truenite
require_once "BotasLogin.php";
require("BotasUsers.php");
echo "Starting bot....\n";


/////////////////////////////
//
//   Run run run!! Botas
//
////////////////////////////

$lastSeenDuration = "";
$statusMessage = "";
$log = "";

function showPresence($phone, $from, $type){
    global $mode, $w , $host, $targets, $timestamp, $statusMessage, $log, $lastSeenDuration;
    $name = "";
    $duration = "";
    $number =  substr($from, 0, 11);

    // We get the name    
    if (array_key_exists($number, $targets)) {
        $name = $targets[$number];
        
    }else{
        $name = $host['name'];       
    }
    
    // Print presence change to console.
    echo "Presence change: Name: $name, Phone: $number, From: $from, Type: $type Time: ".date("Y-m-d H:i:s", time())."\n";
    
    $type = ucwords($type);   
    
    // Only for targets: We save the status change (with out the name at the moment since I only have 1 target)
    // and we save it to a message variable. 
    if (array_key_exists($number, $targets)) {
        if($type=="available"){
            $lastSeenDuration = time();
            $message = "$type at " . date("Y-m-d H:i:s", time());
            //$message = "$name $type at " . date("Y-m-d H:i:s", time());
        }else{
            $duration = time() - $lastSeenDuration;
            $message = "$type at " . date("Y-m-d H:i:s", time())." duration: ".$duration;
            //$message = "$name $type at " . date("Y-m-d H:i:s", time())." duration: ".$duration;
        }
    }

    
    if (array_key_exists($number, $targets)) {
        $w->sendStatusUpdate($message);
        
        if($log==""){
            $log=$message;
        }else{
            $log = $log."\n".$message;
        }
        // When in mode 0: We save the status change to the log 
        // When in mode 1: We automatically send the update as the status change
        if($mode==1){
            sendUpdate();
        }
    }
}

// Function to do on Last Seen
function showLastSeen($phone, $from, $msgid, $sec){
        echo "Last seen: Phone: $phone, From: $from, MSGId: $msgid, Sec: $sec\n";
}

// Function to do on message recieved.
function onMessage($mynumber, $from, $id, $type, $time, $name, $body){
    echo "\nMessage from $name:  $body\n";
    global $w , $host, $timestamp, $statusMessage, $log, $mode;
    
    $number =  substr($from, 0, 11);
    
    if($body=="1"){
        $mode = 1;
        $w->sendMessage($host['number'], "mode: ".$mode);
        return;
    }else{
        if($body=="0"){
            $mode = 0;
            $w->sendMessage($host['number'], "mode: ".$mode);
            return;
        }
    }
    if($number == $host['number']){
        sendUpdate();
    }
}

// This function sends the update to the host number
function sendUpdate(){
    global $w, $host, $log;
    if($log==""){
        $log="noUpdates";
    }
    $w->sendMessage($host['number'], $log);
    $log = "";
}



//$w->sendGetProfilePicture($number, $large = false);

function test($phone, $from, $id, $t){
    
    echo "WIN";
}




// Function bingings to WhatsApp Event
$w->eventManager()->bind("onGetMessage", "onMessage");
$w->eventManager()->bind("onGetRequestLastSeen", "showLastSeen");
$w->eventManager()->bind("onPresence", "showPresence");
$w->eventManager()->bind("onProfilePictureChanged","test");
$w->connect();

$w->loginWithPassword($password);
echo "Bot started\n";

// Send the subscription of the host and the targets

foreach ($targets as $targetName => $targetPhone) {
    $w->sendPresenceSubscription($targetPhone);
}

// I also susbscribe the host just to be sure.

$w->sendPresenceSubscription($host['number']);


//////////
//
//    Main Loop
//
//////////

$timestamp = time();
while(true){
    try{
        //$w->PollMessages();
        $w->PollMessage();
        if($timestamp > time() - 60){
            // Count seconds ago
            $deff = time() - $timestamp;
            $seconds = $deff;
            if(intval($seconds)> 30){
                $w->sendPong($statusMessage);
                $timestamp = time();
                // If bot is crashing constantly uncomment line below to debug
                //echo "Still alive at: ". date("Y-m-d H:i:s", time())."\n";
            }
        }
    }
    catch (Exception $e){
        // This usually never works
        throw new Exception( 'Something really gone wrong', 0, $e);
    }
}
echo "Exited";
?>
