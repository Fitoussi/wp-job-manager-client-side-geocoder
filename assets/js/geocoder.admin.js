jQuery(document).ready(function($) {
			
	addressInput = ( $('#_job_location').length ) ? $("#_job_location") : $("#_candidate_location");
	
	var geocodedMessage    = '<em style="color:green">Location geocoded.</em>';
	var geocodedNotMessage = '<em style="color:red">Location is not geocoded.</em>';
	var geocoderActions    = '<div id="jmcsg-geocoder-action-wrapper">';
	    geocoderActions   += '<input type="submit" id="jmcsg-geocode" class="button button-small" style="width:110px;" value="Geocode Address" />';
	    geocoderActions   += '<span id="jmcsg-geocode-message" style="width:200px;margin-left:10px;"></span></div>';
	
    $(geocoderActions).insertAfter(addressInput);
    				
	if ( $('#jmcsg-geocoded').val() == 'true' ) {
		$('#jmcsg-geocode-message').html(geocodedMessage);
		$('#jmcsg-geocode').attr('disabled','disabled');
	} else {
		$('#jmcsg-geocode-message').html(geocodedNotMessage);
	}
	
	$(addressInput).on('input', function() {
		$('#jmcsg-geocode-message').html(geocodedNotMessage);
		$('.jmcsg_address_fields').val('');
		$('#jmcsg-geocode').removeAttr('disabled');
	});
	
	//trigger geocoder on update location click
	$('#jmcsg-geocode').on( 'click', function(e) {		
		e.preventDefault();
		jmcsgGeocodeAddress();
	});
				
	//geocode the address
	function jmcsgGeocodeAddress() {

		newAddress = $.trim( addressInput.val() );
		
		//prevent geocoder and submit form if no address entered or address is the same
		if ( ( !newAddress.length || ( newAddress == $("#jmcsg_original_location").val() ) ) && $('#jmcsg-geocoded').val() != 'false' ) {
			return;
		} 

		geocoder    = new google.maps.Geocoder();	
		countryCode = 'us';
					
		//geocode the address
	   	geocoder.geocode( { 'address': newAddress, 'region': countryCode }, function(results, status) {
	      	if ( status == google.maps.GeocoderStatus.OK ) {	
	      		$('#jmcsg-geocode-message').html(geocodedMessage);
	      		$('#jmcsg-geocoded').val('true');	
	      		$('#jmcsg-geocode').attr('disabled','disabled');
	      		$("#jmcsg_original_location").val('')
        		jmcsgGetAddressFields(results);
	    	} else {   
	    		alert('Geocode was not successful for the following reason: ' + status);
	    		$('#jmcsg-geocode-message').html(geocodedNotMessage);
	    		$('.jmcsg_address_fields').val('');
	    		$('#jmcsg-geocode').removeAttr('disabled');
	    		$("#jmcsg_original_location").val('')
	    		$('#jmcsg-geocoded').val('false');	 
	    	}
	   	}); 
	};
	
	//Break down the address fields
	function jmcsgGetAddressFields(results) {
			
		var street_number = false;
		var street 		  = false;
		var address 	  = results[0].address_components;
			    
	    $('#jmcsg_lat').val(results[0].geometry.location.lat());
	    $('#jmcsg_long').val(results[0].geometry.location.lng());
	    $('#jmcsg_formatted_address').val(results[0].formatted_address);
	    
	    /* check for each of the address components and if exist save it in a cookie */
		for ( x in address ) {
			
			if ( address[x].types == 'street_number' ) {
				street_number = address[x].long_name; 
				$('#jmcsg_street_number').val(street_number);
			}
			
			if ( address[x].types == 'route' ) {
				street = address[x].long_name;
				$('#jmcsg_street').val(street);
				/*
				$('#jmcsg_street_name').val(street);
				if ( street_number != false ) {
					street = street_number + ' ' + street;
				} 
				$('#jmcsg_street').val(street);
				*/
			}
	
			if ( address[x].types == 'administrative_area_level_1,political' ) {
	          	$('#jmcsg_state_short').val(address[x].short_name);
	          	$('#jmcsg_state_long').val(address[x].long_name);
	         } 
	         
	         if(address[x].types == 'locality,political') {
	          	$('#jmcsg_city').val(address[x].long_name);
	         } 
	         
	         if (address[x].types == 'postal_code') {
	          	$('#jmcsg_postcode').val(address[x].long_name);
	        } 
	        
	        if (address[x].types == 'country,political') {
	          	$('#jmcsg_country_short').val(address[x].short_name);
	          	$('#jmcsg_country_long').val(address[x].long_name);
	         } 
		}	
	}
});