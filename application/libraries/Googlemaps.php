<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Google Maps API V3 Class
 *
 * Displays a Google Map
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		BIOSTALL (Steve Marks)
 * @link		http://biostall.com
 */
 
class Googlemaps {
	
	var $adsense					= FALSE;
	var $adsenseChannelNumber		= '';
	var $adsenseFormat				= 'HALF_BANNER';
	var $adsensePosition			= 'TOP_CENTER';
	var $adsensePublisherID			= '';
	var $center						= "37.4419, -122.1419";
	var $disableDefaultUI			= FALSE;
	var $disableMapTypeControl		= FALSE;
	var $disableNavigationControl	= FALSE;
	var $disableScaleControl		= FALSE;
	var $disableDoubleClickZoom		= FALSE;
	var $draggable					= TRUE;
	var $draggableCursor			= '';
	var $draggingCursor				= '';
	var $navigationControlPosition	= '';
	var $keyboardShortcuts			= TRUE;
	var $jsfile						= '';
	var $map_div_id					= "map_canvas";
	var $map_height					= "450px";
	var $map_name					= "map";
	var $map_type					= "ROADMAP";
	var $map_width					= "100%";
	var $mapTypeControlPosition		= '';
	var $onclick					= '';
	var $region						= '';
	var $scaleControlPosition		= '';
	var $scrollwheel				= TRUE;
	var $sensor						= FALSE;
	var	$version					= "3";
	var $zoom						= 13;
	
	var	$markers					= array();	
	var	$polylines					= array();
	var	$polygons					= array();
	var	$circles					= array();
	
	var $directions					= FALSE;
	var $directionsStart			= "";
	var $directionsEnd				= "";
	var $directionsDivID			= "";
	var $directionsMode				= "DRIVING"; // DRIVING, WALKING or BICYCLING (US Only)
	var $directionsAvoidTolls		= FALSE;
	var $directionsAvoidHighways	= FALSE;
	
	function Googlemaps($config = array())
	{
		if (count($config) > 0)
		{
			$this->initialize($config);
		}

		log_message('debug', "Google Maps Class Initialized");
	}

	function initialize($config = array())
	{
		foreach ($config as $key => $val)
		{
			if (isset($this->$key))
			{
				$this->$key = $val;
			}
		}
		
		if ($this->sensor) { $this->sensor = "true"; }else{ $this->sensor = "false"; }
		
	}
	
	function add_marker($params = array())
	{
		
		$marker = array();
		
		$marker['position'] = '';
		$marker['infowindow_content'] = '';
		$marker['clickable'] = TRUE;
		$marker['cursor'] = '';
		$marker['draggable'] = FALSE;
		$marker['flat'] = FALSE;
		$marker['icon'] = '';
		$marker['onclick'] = '';
		$marker['ondragstart'] = '';
		$marker['ondragend'] = '';
		$marker['shadow'] = '';
		$marker['title'] = '';
		$marker['visible'] = TRUE;
		$marker['zIndex'] = '';
		
		$marker_output = '';
		
		foreach ($params as $key => $value) {
		
			if (isset($marker[$key])) {
			
				$marker[$key] = $value;
				
			}
			
		}
		
		if ($marker['position']!="") {
			if ($this->is_lat_long($marker['position'])) {
				$marker_output .= '
			var myLatlng = new google.maps.LatLng('.$marker['position'].');
			';
			}else{
				$lat_long = $this->get_lat_long_from_address($marker['position']);
				$marker_output .= '
			var myLatlng = new google.maps.LatLng('.$lat_long[0].', '.$lat_long[1].');';
			}
		}
		
		$marker_output .= '		
			var marker = new google.maps.Marker({
				position: myLatlng, 
				map: '.$this->map_name;
		if (!$marker['clickable']) {
			$marker_output .= ',
				clickable: false';
		}
		if ($marker['cursor']!="") {
			$marker_output .= ',
				cursor: "'.$marker['cursor'].'"';
		}
		if ($marker['draggable']) {
			$marker_output .= ',
				draggable: true';
		}
		if ($marker['flat']) {
			$marker_output .= ',
				flat: true';
		}
		if ($marker['icon']!="") {
			$marker_output .= ',
				icon: "'.$marker['icon'].'"';
		}
		if ($marker['shadow']!="") {
			$marker_output .= ',
				shadow: "'.$marker['shadow'].'"';
		}
		if ($marker['title']!="") {
			$marker_output .= ',
				title: "'.$marker['title'].'"';
		}
		if (!$marker['visible']) {
			$marker_output .= ',
				visible: false';
		}
		if ($marker['zIndex']!="" && is_numeric($marker['zIndex'])) {
			$marker_output .= ',
				zIndex: '.$marker['zIndex'];
		}
		$marker_output .= '		
			});		';
		
		if ($marker['infowindow_content']!="") {
			$marker_output .= '
			marker.set("content", "'.$marker['infowindow_content'].'");
			
			google.maps.event.addListener(marker, "click", function() {
				iw.setContent(this.get("content"));
				iw.open('.$this->map_name.', this);
			';
			if ($marker['onclick']!="") { $marker_output .= $marker['onclick'].'
			'; }
			$marker_output .= '
			});
			';
		}else{
			if ($marker['onclick']!="") { 
				$marker_output .= '
				google.maps.event.addListener(marker, "click", function() {
					'.$marker['onclick'].'
				});
				';
			}
		}
		
		if ($marker['draggable']) {
			if ($marker['ondragend']!="") { 
				$marker_output .= '
				google.maps.event.addListener(marker, "dragend", function() {
					'.$marker['ondragend'].'
				});
				';
			}
			if ($marker['ondragstart']!="") { 
				$marker_output .= '
				google.maps.event.addListener(marker, "dragstart", function() {
					'.$marker['ondragstart'].'
				});
				';
			}
		}
		
		$marker_output .= '
			markers.push(marker);
			lat_longs.push(marker.getPosition());
		';
		
		array_push($this->markers, $marker_output);
	
	}
	
	function add_polyline($params = array())
	{
		
		$polyline = array();
		
		$polyline['points'] = array();
		$polyline['strokeColor'] = '#FF0000';
		$polyline['strokeOpacity'] = '1.0';
		$polyline['strokeWeight'] = '2';
	
		$polyline_output = '';
		
		foreach ($params as $key => $value) {
		
			if (isset($polyline[$key])) {
			
				$polyline[$key] = $value;
				
			}
			
		}
		
		if (count($polyline['points'])) {

			$polyline_output .= '
				var polyline_plan_'.count($this->polylines).' = [';
			$i=0;
			$lat_long_output = '';
			foreach ($polyline['points'] as $point) {
				if ($i>0) { $polyline_output .= ','; }
				$lat_long_to_push = '';
				if ($this->is_lat_long($point)) {
					$lat_long_to_push = $point;
					$polyline_output .= '
					new google.maps.LatLng('.$point.')
					';
				}else{
					$lat_long = $this->get_lat_long_from_address($point);
					$polyline_output .= '
					new google.maps.LatLng('.$lat_long[0].', '.$lat_long[1].')';
					$lat_long_to_push = $lat_long[0].', '.$lat_long[1];
				}
				$lat_long_output .= '
					lat_longs.push(new google.maps.LatLng('.$lat_long_to_push.'));
				';
				$i++;
			}
			$polyline_output .= '];';
			
			$polyline_output .= $lat_long_output;
			
			$polyline_output .= '
				var polyline_'.count($this->polylines).' = new google.maps.Polyline({
    				path: polyline_plan_'.count($this->polylines).',
    				strokeColor: "'.$polyline['strokeColor'].'",
    				strokeOpacity: '.$polyline['strokeOpacity'].',
    				strokeWeight: '.$polyline['strokeWeight'].'
 				});
				
				polyline_'.count($this->polylines).'.setMap('.$this->map_name.');

			';
		
			array_push($this->polylines, $polyline_output);
			
		}
	
	}
	
	function add_polygon($params = array())
	{
		
		$polygon = array();
		
		$polygon['points'] = array();
		$polygon['strokeColor'] = '#FF0000';
		$polygon['strokeOpacity'] = '0.8';
		$polygon['strokeWeight'] = '2';
		$polygon['fillColor'] = '#FF0000';
		$polygon['fillOpacity'] = '0.3';
	
		$polygon_output = '';
		
		foreach ($params as $key => $value) {
		
			if (isset($polygon[$key])) {
			
				$polygon[$key] = $value;
				
			}
			
		}
		
		if (count($polygon['points'])) {

			$polygon_output .= '
				var polygon_plan_'.count($this->polygons).' = [';
			$i=0;
			$lat_long_output = '';
			foreach ($polygon['points'] as $point) {
				if ($i>0) { $polygon_output .= ','; }
				$lat_long_to_push = '';
				if ($this->is_lat_long($point)) {
					$lat_long_to_push = $point;
					$polygon_output .= '
					new google.maps.LatLng('.$point.')
					';
				}else{
					$lat_long = $this->get_lat_long_from_address($point);
					$polygon_output .= '
					new google.maps.LatLng('.$lat_long[0].', '.$lat_long[1].')';
					$lat_long_to_push = $lat_long[0].', '.$lat_long[1];
				}
				$lat_long_output .= '
					lat_longs.push(new google.maps.LatLng('.$lat_long_to_push.'));
				';
				$i++;
			}
			$polygon_output .= '];';
			
			$polygon_output .= $lat_long_output;
			
			$polygon_output .= '
				var polygon_'.count($this->polygons).' = new google.maps.Polygon({
    				path: polygon_plan_'.count($this->polygons).',
    				strokeColor: "'.$polygon['strokeColor'].'",
    				strokeOpacity: '.$polygon['strokeOpacity'].',
    				strokeWeight: '.$polygon['strokeWeight'].',
					fillColor: "'.$polygon['fillColor'].'",
					fillOpacity: '.$polygon['fillOpacity'].'
 				});
				
				polygon_'.count($this->polygons).'.setMap('.$this->map_name.');

			';
		
			array_push($this->polygons, $polygon_output);
			
		}
	
	}
	
	function add_circle($params = array())
	{
		
		$circle = array();
		
		$circle['center'] = '';
		$circle['radius'] = 0;
		$circle['strokeColor'] = '0.8';
		$circle['strokeOpacity'] = '0.8';
		$circle['strokeWeight'] = '2';
		$circle['fillColor'] = '#FF0000';
		$circle['fillOpacity'] = '0.3';
	
		$circle_output = '';
		
		foreach ($params as $key => $value) {
		
			if (isset($circle[$key])) {
			
				$circle[$key] = $value;
				
			}
			
		}
		
		if ($circle['radius']>0 && $circle['center']!="") {
			
			$lat_long_to_push = '';
			if ($this->is_lat_long($circle['center'])) {
				$lat_long_to_push = $circle['center'];
				$circle_output = '
				var circleCenter = new google.maps.LatLng('.$circle['center'].')
				';
			}else{
				$lat_long = $this->get_lat_long_from_address($circle['center']);
				$circle_output = '
				var circleCenter = new google.maps.LatLng('.$lat_long[0].', '.$lat_long[1].')';
				$lat_long_to_push = $lat_long[0].', '.$lat_long[1];
			}
			$circle_output .= '
				lat_longs.push(new google.maps.LatLng('.$lat_long_to_push.'));
			';
			
			$circle_output .= '
				var circleOptions = {
					strokeColor: "'.$circle['strokeColor'].'",
					strokeOpacity: '.$circle['strokeOpacity'].',
					strokeWeight: '.$circle['strokeWeight'].',
					fillColor: "'.$circle['fillColor'].'",
					fillOpacity: '.$circle['fillOpacity'].',
					map: '.$this->map_name.',
					center: circleCenter,
					radius: '.$circle['radius'].'
				};
				var circle_'.count($this->circles).' = new google.maps.Circle(circleOptions);
			';
		
			array_push($this->circles, $circle_output);
			
		}
	
	}
	
	function create_map()
	{
	
		$this->output_js = '';
		$this->output_js_contents = '';
		$this->output_html = '';
		
		$this->output_js .= '
		<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor='.$this->sensor;
		if ($this->region!="" && strlen($this->region)==2) { $this->output_js .= '&region='.strtoupper($this->region); }
		if ($this->adsense!="") { $this->output_js .= '&libraries=adsense'; }
		$this->output_js .= '"></script>';
		if ($this->jsfile=="") {
			$this->output_js .= '
			<script type="text/javascript">
			//<![CDATA[
			';
		}

		$this->output_js_contents .= '
			var '.$this->map_name.'; // Global declaration of the map
			var iw = new google.maps.InfoWindow(); // Global declaration of the infowindow
			var lat_longs = new Array();
			var markers = new Array();
			';
		if ($this->directions) { 
			$this->output_js_contents .= 'var directionsDisplay = new google.maps.DirectionsRenderer();
			var directionsService = new google.maps.DirectionsService();
			';
		}
		if ($this->adsense) { 
			$this->output_js_contents .= 'var adUnit;
			'; 
		}
		
		$this->output_js_contents .= 'function initialize() {
				
				';
				
		if ($this->is_lat_long($this->center)) { // if centering the map on a lat/long
			$this->output_js_contents .= 'var myLatlng = new google.maps.LatLng('.$this->center.');';
		}else{  // if centering the map on an address
			$lat_long = $this->get_lat_long_from_address($this->center);
			$this->output_js_contents .= 'var myLatlng = new google.maps.LatLng('.$lat_long[0].', '.$lat_long[1].');';
		}
		
		$this->output_js_contents .= '
				var myOptions = {
			  		';
		if ($this->zoom=="auto") { $this->output_js_contents .= 'zoom: 13,'; }else{ $this->output_js_contents .= 'zoom: '.$this->zoom.','; }
		$this->output_js_contents .= '
					center: myLatlng,
			  		mapTypeId: google.maps.MapTypeId.'.$this->map_type;
		if ($this->disableDefaultUI) {
			$this->output_js_contents .= ',
					disableDefaultUI: true';
		}
		if ($this->disableMapTypeControl) {
			$this->output_js_contents .= ',
					mapTypeControl: false';
		}
		if ($this->disableNavigationControl) {
			$this->output_js_contents .= ',
					navigationControl: false';
		}
		if ($this->disableScaleControl) {
			$this->output_js_contents .= ',
					scaleControl: false';
		}
		if ($this->disableDoubleClickZoom) {
			$this->output_js_contents .= ',
					disableDoubleClickZoom: true';
		}
		if (!$this->draggable) {
			$this->output_js_contents .= ',
					draggable: false';
		}
		if ($this->draggableCursor!="") {
			$this->output_js_contents .= ',
					draggableCursor: "'.$this->draggableCursor.'"';
		}
		if ($this->draggingCursor!="") {
			$this->output_js_contents .= ',
					draggingCursor: "'.$this->draggingCursor.'"';
		}
		if (!$this->keyboardShortcuts) {
			$this->output_js_contents .= ',
					keyboardShortcuts: false';
		}
		if ($this->mapTypeControlPosition!="") {
			$this->output_js_contents .= ',
					mapTypeControlOptions: {position: google.maps.ControlPosition.'.strtoupper($this->mapTypeControlPosition).'}';
		}
		if ($this->navigationControlPosition!="") {
			$this->output_js_contents .= ',
					navigationControlOptions: {position: google.maps.ControlPosition.'.strtoupper($this->navigationControlPosition).'}';
		}
		if ($this->scaleControlPosition!="") {
			$this->output_js_contents .= ',
					scaleControlOptions: {position: google.maps.ControlPosition.'.strtoupper($this->scaleControlPosition).'}';
		}
		if (!$this->scrollwheel) {
			$this->output_js_contents .= ',
					scrollwheel: false';
		}
		$this->output_js_contents .= '}
				'.$this->map_name.' = new google.maps.Map(document.getElementById("'.$this->map_div_id.'"), myOptions);
				';
		if ($this->directions) {
			$this->output_js_contents .= 'directionsDisplay.setMap('.$this->map_name.');
			';
			if ($this->directionsDivID!="") {
				$this->output_js_contents .= 'directionsDisplay.setPanel(document.getElementById("'.$this->directionsDivID.'"));
			';
			}
		}
		if ($this->onclick!="") { 
			$this->output_js_contents .= 'google.maps.event.addListener(map, "click", function(event) {
    			'.$this->onclick.'
  			});
			';
		}
		
		// add markers
		if (count($this->markers)) {
			foreach ($this->markers as $marker) {
				$this->output_js_contents .= $marker;
			}
		}	
		//
		
		// add polylines
		if (count($this->polylines)) {
			foreach ($this->polylines as $polyline) {
				$this->output_js_contents .= $polyline;
			}
		}	
		//
		
		// add polygons
		if (count($this->polygons)) {
			foreach ($this->polygons as $polygon) {
				$this->output_js_contents .= $polygon;
			}
		}	
		//
		
		// add circles
		if (count($this->circles)) {
			foreach ($this->circles as $circle) {
				$this->output_js_contents .= $circle;
			}
		}	
		//
		
		if ($this->zoom=="auto") { 
			$this->output_js_contents .= '
			var bounds = new google.maps.LatLngBounds();
			if (lat_longs.length>0) {
				for (var i=0; i<lat_longs.length; i++) {
					bounds.extend(lat_longs[i]);
				}
				'.$this->map_name.'.fitBounds(bounds);
			}
			';
		}
		
		if ($this->adsense) { 
			$this->output_js_contents .= '
			var adUnitDiv = document.createElement("div");

		    // Note: replace the publisher ID noted here with your own
		    // publisher ID.
		    var adUnitOptions = {
		    	format: google.maps.adsense.AdFormat.'.$this->adsenseFormat.',
		    	position: google.maps.ControlPosition.'.$this->adsensePosition.',
		    	publisherId: "'.$this->adsensePublisherID.'",
		    	';
		    if ($this->adsenseChannelNumber!="") { $this->output_js_contents .= 'channelNumber: "'.$this->adsenseChannelNumber.'",
		    	'; }
		    $this->output_js_contents .= 'map: map,
		    	visible: true
		    };
		    adUnit = new google.maps.adsense.AdUnit(adUnitDiv, adUnitOptions);
		    ';
		}
		
		if ($this->directions && $this->directionsStart!="" && $this->directionsEnd!="") {
			$this->output_js_contents .= '
				calcRoute(\''.$this->directionsStart.'\', \''.$this->directionsEnd.'\');
			';
		}
		
		$this->output_js_contents .= '
			
			}
		
		';
		
		if ($this->directions) {
			
			$this->output_js_contents .= 'function calcRoute(start, end) {
			var request = {
			    	origin:start,
			    	destination:end,
			    	travelMode: google.maps.TravelMode.'.$this->directionsMode.'
			    	';
			if ($this->region!="" && strlen($this->region)==2) { 
				$this->output_js_contents .= ',region: '.strtoupper($this->region).'
					'; 
			}
			if ($this->directionsAvoidTolls) { 
				$this->output_js_contents .= ',avoidTolls: true
					'; 
			}
			if ($this->directionsAvoidHighways) { 
				$this->output_js_contents .= ',avoidHighways: true
					'; 
			}
			
			$this->output_js_contents .= '
			};
			  	directionsService.route(request, function(response, status) {
			    	if (status == google.maps.DirectionsStatus.OK) {
			      		directionsDisplay.setDirections(response);
			    	}else{
			    		switch (status) { 	
			    			case "NOT_FOUND": { alert("Either the start location or destination were not recognised"); break }
			    			case "ZERO_RESULTS": { alert("No route could be found between the start location and destination"); break }
			    			case "MAX_WAYPOINTS_EXCEEDED": { alert("Maximum waypoints exceeded. Maximum of 8 allowed"); break }
			    			case "INVALID_REQUEST": { alert("Invalid request made for obtaining directions"); break }
			    			case "OVER_QUERY_LIMIT": { alert("This webpage has sent too many requests recently. Please try again later"); break }
			    			case "REQUEST_DENIED": { alert("This webpage is not allowed to request directions"); break }
			    			case "UNKNOWN_ERROR": { alert("Unknown error with the server. Please try again later"); break }
			    		}
			    	}
			  	});
			}
			';
			
		}
		
		$this->output_js_contents .= '
		  	window.onload = initialize;
		';
		
		if ($this->jsfile=="") { 
			$this->output_js .= $this->output_js_contents; 
		}else{ // if needs writing to external js file
			if (!$handle = fopen($this->jsfile, "w")) {
				$this->output_js .= $this->output_js_contents; 
			}else{
				if (!fwrite($handle, $this->output_js_contents)) {
					$this->output_js .= $this->output_js_contents; 
				}else{
					$this->output_js .= '
					<script src="'.$this->jsfile.'" type="text/javascript"></script>';
				}
			}	
		}
		
		if ($this->jsfile=="") { 
			$this->output_js .= '
			//]]>
			</script>';
		}
		
		
		
		// set height and width
		if (is_numeric($this->map_width)) { // if no width type set
			$this->map_width = $this->map_width.'px';
		}
		if (is_numeric($this->map_height)) { // if no height type set
			$this->map_height = $this->map_height.'px';
		}
		//
		
		$this->output_html .= '<div id="'.$this->map_div_id.'" style="width:'.$this->map_width.'; height:'.$this->map_height.';"></div>';
		
		return array('js'=>$this->output_js, 'html'=>$this->output_html);
	
	}
	
	function is_lat_long($input)
	{
		
		$input = str_replace(", ", ",", $input);
		$input = explode(",", $input);
		if (count($input)==2) {
		
			if (is_numeric($input[0]) && is_numeric($input[1])) { // is a lat long
				return true;
			}else{ // not a lat long - incorrect values
				return false;
			}
		
		}else{ // not a lat long - too many parts
			return false;
		}
		
	}
	
	function get_lat_long_from_address($address)
	{
		
		$lat = 0;
		$lng = 0;
		
		$data_location = "http://maps.google.com/maps/api/geocode/json?address=".str_replace(" ", "+", $address)."&sensor=".$this->sensor;
		if ($this->region!="" && strlen($this->region)==2) { $data_location .= "&region=".$this->region; }
		$data = file_get_contents($data_location);
		
		$data = json_decode($data);
		
		if ($data->status=="OK") {
			
			$lat = $data->results[0]->geometry->location->lat;
			$lng = $data->results[0]->geometry->location->lng;
			
		}
		
		return array($lat, $lng);
		
	}
	
}

?>