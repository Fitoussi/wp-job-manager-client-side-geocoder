jQuery(document).ready(function($) {
	
	submitForm   = false;
	submitButton = ( $('form#submit-job-form input[name="submit_job"]').length ) ? $('form#submit-job-form').find('input[name="submit_job"]') : $('form#submit-resume-form').find('input[name="submit_resume"]');
	addressInput = ( $('form#submit-job-form #job_location').length ) ? $('form#submit-job-form').find('#job_location') : $('form#submit-resume-form').find('#candidate_location');
	
	//trigger geocoder on update location click
	$(submitButton).on( 'click', function(e) {				
		if ( !submitForm ) {
			e.preventDefault();
			$('.jmcsg-address-field').val('');
			submitForm = true;
			jmcsgGeocodeAddress(); 			
		}
	});
				
	//geocode the address
	function jmcsgGeocodeAddress() {

		newAddress = $.trim( addressInput.val() );
		
		//prevent geocoder and submit form if no address entered
		if ( !newAddress.length ) {
			submitButton.click();
			return;
		} 

		geocoder    = new google.maps.Geocoder();	
		countryCode = 'us';
		
		//geocode the address
	   	geocoder.geocode( { 'address': newAddress, 'region': countryCode }, function(results, status) {
	      	if ( status == google.maps.GeocoderStatus.OK ) {	
        		jmcsgGetAddressFields(results);
	    	} else {   
	    		submitButton.click();
				return;
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
			}
			
			if ( address[x].types == 'route' ) {
				street = address[x].long_name;  
				if ( street_number != false ) {
					street = street_number + ' ' + street;
				} 
				$('#jmcsg_street').val(street);
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
		
		//submit the form
		setTimeout(function() {
			submitButton.click();
		}, 500);
	}
});