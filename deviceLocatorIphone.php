<?PHP
/*
Date: 09/15/2015
Name: Pat Coggins
Description: This is a light weight cross platform GPS track logging backend processor.
This version is written to work with an app in the Apple App Store 'Device Locator'. I
Am not affilitaed with the app in any way. I chose this one because of it runs in the backgound
of the iPhone and restarts itself after a reboot. Also, there is a web site were you can
enter your URL and arguments for THIS application. 

This is a working example that is fine for processing a handfull of iPhones. To track a
large amount or psuedo realtime tracking the architecture would be different in that 
you would not want the feed to be slowed down because of slow reverse geocoding calls
and the like.   

After you get the app you can go to their web site to set the URL and arguments for this application
https://device-locator.com/index.php
After you login go to settings and put this string in the URL Forwarding field. Be sure to set your
actual phone number in the device field as shown below. This is to associate each position record
with the phone it came from. The line below is litterally copy paste after you set device= to your
number.

http://gpsonit.com/remote_locateIPhone.php?device=15555551212&long={long}&lat={lat}&acc={acc}&alt={alt}&altacc={altacc}&batt={batt}&ip={ip}&timediff={timediff}

Variable:	Description:
{long}	Longitude
{lat}	Latitude
{acc}	Accuracy
{alt}	Altitude
{altacc}	Altitude Accuracy
{batt}	Battery
{ip}	IP of the device
{timediff}	Time in seconds since the location was recorded. Always a negative number.


You can test your installation by pasting this in your browser assuming you are running a web server
locally:
http://127.0.0.1/remote_locate.php?device=9093190447&long=-80.1236953735&lat=26.3995513916&acc=65.0&alt=0.0&altacc=-1.0&batt=0.9
*/

//Do our defines so we have logging.
define("DEBUG", 1);
/* Set our directory and log name */
define("LOG_FILE", "/tmp/deviceLocateApp.log");

//Set our default time zone.
date_default_timezone_set(timezone_name_from_abbr('UTC'));

if(DEBUG == true) {
  error_log(date('[Y-m-d H:i e] '). "HTTP request URL:". $_SERVER['REQUEST_URI'] . PHP_EOL, 3, LOG_FILE);
}

//Filter URL Parameters - You can change these filters to what ever works for you
$lat=filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lon=filter_input(INPUT_GET, 'long', FILTER_VALIDATE_FLOAT);
$acc=filter_input(INPUT_GET, 'acc', FILTER_VALIDATE_FLOAT);
$alt=filter_input(INPUT_GET, 'alt', FILTER_VALIDATE_FLOAT);
$batt=filter_input(INPUT_GET, 'batt', FILTER_VALIDATE_FLOAT);
$ipAddress=filter_input(INPUT_GET, 'ip', FILTER_VALIDATE_IP);
$timeDiff=filter_input(INPUT_GET, 'timediff', FILTER_VALIDATE_INT);
$device=filter_input(INPUT_GET, 'device', FILTER_SANITIZE_SPECIAL_CHARS);

// This is just a quick sanity check to make sure we have a valid lat/lon
if($lat > -90.0 && $lat < 90.0 && $lon > -180.0 && $lat < 180.0) {
  //Instantiate our class
  $processPosition = new ProcessPosition();
  
  //call google maps api with lat/lon to get closest address
  $addressArray = $processPosition->reverseGeocodeGoogle($lat, $lon);
  if(DEBUG == true) {
    error_log(date('[Y-m-d H:i e] '). "processPosition->reverseGeocodeGoogle:". print_r($addressArray, true) . PHP_EOL, 3, LOG_FILE);
  }
  
  //Create a timeStamp var since none comes with the position. 
  //You can subtract the timediff arg to make it more accurate 
  $timeStamp = time();
  
  //Stuff our vars in to an array and pass to insertRecord
  $insertRecord = $processPosition->insertRecord(
	    		array(
	    			"lat" 	    => $lat,
	    			"lon" 	    => $lon,
	    			"acc" 	    => $acc,
	    			"alt" 	    => $alt,
	    			"batt" 	    => $batt,
	    			"ipAddress" => $ipAddress,
	    			"device"    => $device,
	    			"timeStamp" => $timeStamp,	    				
                    "address"   =>  $addressArray['Address'],
                    "city"      =>  $addressArray['City'],
                    "state"     =>  $addressArray['State'],
                    "country"   =>  $addressArray['Country'],
                    "zip"       =>  $addressArray['Zip'],
	    		)
	    	);



  exit;
} 

  class ProcessPosition {
    private $hostName          = '127.0.0.1';
	private $dbhost            = "127.0.0.1";	// Host Name
	private $dbport            = "3306";	    // Port
	private $dbuser            = "UserName";	// MySQL Database Username
	private $dbpass            = "Password";	// MySQL Database Password
	private $dbname            = "myDB";	    // Your Database or Schema
	private $dbTrackLogTable   = "trackLog";	// This is what ever you called your table.
	private $dbh = '';

	public function __construct(){
      try {
	    $this->dbh	= new PDO("mysql:dbname={$this->dbname};host={$this->dbhost};port={$this->dbport}", $this->dbuser, $this->dbpass);
      } 
      catch (PDOException $e) {
        /* Our Connection failed, log it */
        if(DEBUG == true) {
          error_log(date('[Y-m-d H:i e] '). "PDO Connection failed:". $e->getMessage() . PHP_EOL, 3, LOG_FILE);
        }
      }
    }
    public function reverseGeocodeGoogle($lat, $lon) {
      //echo 'Trying Google'."</br>\r\n";
      $addr['Address'] = '';
      $addr['City'] = '';
      $addr['County'] = '';
      $addr['Zip'] = '';
      $addr['State'] = '';
      $addr['Country'] = '';
      $url = "http://maps.google.com/maps/api/geocode/xml?latlng=".$lat.",".$lon."&sensor=false";
      $response = file_get_contents($url); 
      $xmlQueue = $this->get_string_between($response, '<GeocodeResponse>', '</GeocodeResponse>');

      while(strlen($xmlQueue) > 2) {
        $type = $this->get_string_between($xmlQueue, '<type>', '</type>');
        if($type == 'street_address') {
          $tmp1 = $this->get_string_between($xmlQueue, '<address_component>', '</address_component>');
          $addr['Address'] = $this->get_string_between($tmp1, '<short_name>', '</short_name>');
        }
        if($type == 'route') {
          $tmp1 = $this->get_string_between($xmlQueue, '<address_component>', '</address_component>');
          $addr['Address']=$addr['Address'].' '.$this->get_string_between($tmp1, '<short_name>', '</short_name>');
        }
        if($type == 'locality') {
          $tmp1 = $this->get_string_between($xmlQueue, '<address_component>', '</address_component>');
          $addr['City'] = $this->get_string_between($tmp1, '<short_name>', '</short_name>');
        }
        if($type == 'administrative_area_level_1') {
          $tmp1 = $this->get_string_between($xmlQueue, '<address_component>', '</address_component>');
          $addr['State'] = $this->get_string_between($tmp1, '<short_name>', '</short_name>');
        }
        if($type == 'administrative_area_level_2') {
          $tmp1 = $this->get_string_between($xmlQueue, '<address_component>', '</address_component>');
          $addr['County'] = $this->get_string_between($tmp1, '<short_name>', '</short_name>');
        }
        if($type == 'postal_code') {
          $tmp1 = $this->get_string_between($xmlQueue, '<address_component>', '</address_component>');
          $addr['Zip'] = $this->get_string_between($tmp1, '<short_name>', '</short_name>');
        }
        if($type == 'country') {
          $tmp1 = $this->get_string_between($xmlQueue, '<address_component>', '</address_component>');
          $addr['Country'] = $this->get_string_between($tmp1, '<short_name>', '</short_name>');
        }
        $ini = strpos($xmlQueue,'</address_component>');
        $xmlQueue = substr($xmlQueue,($ini+20),(strlen($xmlQueue) - ($ini+20)));
      }
      return $addr;
    }  

	/* insert the track records in our DB */
	public function insertRecord($toAdd = array()){
		if( is_array($toAdd)){
			$columns = "";
			foreach($toAdd as $i => $s){
				$columns .= "`$i` = :$i, ";
			}
			 // Remove last ","
			$columns = substr($columns, 0, -2);
			$sql   = $this->dbh->prepare("INSERT INTO `{$this->dbTrackLogTable}` SET {$columns}");
			foreach($toAdd as $key => $value){
					$value = htmlspecialchars($value);
					$sql->bindValue(":$key", $value);
			}
			$sql->execute();
			
		}else{
			return false;
		}
	}
    /* get the string from between any tags like: <test> abc </test> */
    public function get_string_between($string, $start, $end) {
      $string=" ".$string;
      $idx = strpos($string,$start);
      if ($idx == 0) return "";
      $idx += strlen($start);
      $len = strpos($string,$end,$idx) - $idx;
      return substr($string,$idx,$len);
    }
}

?>