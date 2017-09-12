var JMCGS = {

	// in admin ?
	is_admin : jmcsgParams.is_admin == 1 ? true : false,

	// address field input element
	address_input_field : false,

	// new address value
	new_address : '',

	// fron-end form submission button element
	form_submit_button : false,

	// admin's geocode button element 
	geocoder_button_element : '',

	// submission status
	submission_status : false,

    // region
    region : jmcsgParams.region,

    // 
    geocode_ok_message : '<em style="color:green">Location geocoded.</em>',

    geocode_missing_message : '<em style="color:red">Location is not geocoded.</em>',

    geocoding_failed_message : 'Geocoding failed for the following reason:',

    /**
     * Run on page load
     * 
     * @return {[type]} [description]
     */
    init : function() {
        
        // in ddmin
        if ( JMCGS.is_admin ) {
        	
        	// get the input field element
        	JMCGS.address_input_field = jQuery( '#_job_location' ).length ? jQuery( '#_job_location' ) : jQuery( '#_candidate_location' )
        	
        	// generate admin's geocoder button
        	JMCGS.generate_admin_fields();

        // in front-end
        } else {

        	// get input field and submit button elements
        	JMCGS.form_submit_button  = jQuery( 'form#submit-job-form input[name="submit_job"]' ).length ? jQuery( 'form#submit-job-form' ).find( 'input[name="submit_job"]' ) : jQuery( 'form#submit-resume-form' ).find( 'input[name="submit_resume"]' );
        	JMCGS.address_input_field = jQuery( 'form#submit-job-form #job_location' ).length ? jQuery( 'form#submit-job-form' ).find( '#job_location' ) : jQuery( 'form#submit-resume-form' ).find( '#candidate_location' );
        	
        	//trigger geocoder on update location click
			jQuery( JMCGS.form_submit_button ).on( 'click', function( event ) {				
		
				if ( ! JMCGS.submission_status ) {

					event.preventDefault();

					jQuery( '.jmcsg-address-field' ).val( '' );

					JMCGS.submission_status = true;

					JMCGS.geocode(); 			
				}
			});
        }
    },

    /**
     * Generate admin's button element
     * 
     * @return {[type]} [description]
     */
    generate_admin_fields : function() {

    	JMCGS.geocoder_button_element += '<div id="jmcsg-geocoder-action-wrapper">';
		JMCGS.geocoder_button_element += '<input type="button" id="jmcsg-geocode" class="button button-small" style="width:110px;" value="Geocode Address" />';
		JMCGS.geocoder_button_element += '<span id="jmcsg-geocode-message" style="width:200px;margin-left:10px;"></span>';
		JMCGS.geocoder_button_element += '</div>';
		
	    jQuery( JMCGS.geocoder_button_element ).insertAfter( JMCGS.address_input_field );
	    					
	   	// if address already geocoded
		if ( jQuery( '#jmcsg-geocoded' ).val() == 'true' ) {
			
			// show sucess message
			jQuery( '#jmcsg-geocode-message' ).html( JMCGS.geocode_ok_message );

			// disable geocoder button
			jQuery( '#jmcsg-geocode' ).attr( 'disabled','disabled' );

		// ottherwise, show not geocoded message
		} else {

			jQuery('#jmcsg-geocode-message').html( JMCGS.geocode_missing_message );
		}
		
		// if address changed allow geocoding
		jQuery( JMCGS.address_input_field ).on( 'input', function() {

			// show message
			jQuery( '#jmcsg-geocode-message' ).html( JMCGS.geocode_missing_message );

			// clear fields
			jQuery( '.jmcsg_address_fields' ).val('');

			// enable geocoder button
			jQuery( '#jmcsg-geocode' ).removeAttr( 'disabled' );
		});

		// trigger geocoder on click
		jQuery( '#jmcsg-geocode' ).on( 'click', function(e) {		
			e.preventDefault();
			JMCGS.geocode();
		});
    },

    /**
     * Geocoder function
     * 
     * @return {[type]} [description]
     */
    geocode : function() {

    	JMCGS.new_address = jQuery.trim( JMCGS.address_input_field.val() );
		
		if ( JMCGS.is_admin ) {
			
			//prevent geocoder and submit form if no address entered or address is the same
			if ( ! JMCGS.new_address.length || jQuery( '#jmcsg-geocoded' ).val() == 'true' ) {
				return;
			}

		} else {

			if ( ! JMCGS.new_address.length ) {
				JMCGS.form_submit_button.click();
			}
		} 

		geocoder = new google.maps.Geocoder();	

		//geocode the address
	   	geocoder.geocode( { 'address': JMCGS.new_address, 'region': JMCGS.region }, function( results, status ) {

	   		// if geocoded
	      	if ( status == google.maps.GeocoderStatus.OK ) {	

	      		JMCGS.get_address_fields( results );

	      		if ( JMCGS.is_admin ) {

		      		jQuery( '#jmcsg-geocode' ).attr( 'disabled','disabled' );
		      		jQuery( '#jmcsg-geocode-message' ).html( JMCGS.geocode_ok_message );
		      		jQuery( '#jmcsg-geocoded' ).val( 'true' );	
		      		jQuery( '#jmcsg_location_update' ).val( '1' )
		      	}
        		
	    	} else {   

	    		if ( JMSGS.is_admin ) {
		    		
		    		alert( JMCGS.geocoding_failed_message + ' ' + status );

		    		jQuery( '#jmcsg-geocode' ).removeAttr( 'disabled' );
		    		jQuery( '#jmcsg-geocode-message' ).html( JMCGS.geocode_missing_message );
		    		jQuery( '#jmcsg-geocoded' ).val( 'false' );
		    		jQuery( '#jmcsg_location_update' ).val( '1' );
		    		jQuery( '.jmcsg_address_fields' ).val( '' );
		    	
		    	} else {

		    		JMCGS.form_submit_button.click();
		    	}	 
	    	}
	   	}); 
    },

    /**
     * Get address fields and populate values in hidden fields
     * 
     * @param  {[type]} results [description]
     * @return {[type]}         [description]
     */
	get_address_fields : function ( results ) {
			
		var address = results[0].address_components;

	    jQuery( '#jmcsg_lat' ).val( results[0].geometry.location.lat() );
	    jQuery( '#jmcsg_long' ).val( results[0].geometry.location.lng() );
	    jQuery( '#jmcsg_formatted_address' ).val( results[0].formatted_address );
	    
	    // check for each of the address components 
		for ( x in address ) {
			
			if ( address[x].types == 'street_number' ) {

				street_number = address[x].long_name; 

				jQuery( '#jmcsg_street_number' ).val( street_number );
			}
			
			if ( address[x].types == 'route' ) {

				street = address[x].long_name;
				
				jQuery( '#jmcsg_street' ).val( street );
			}
	
			if ( address[x].types == 'administrative_area_level_1,political' ) {
	          	
	          	jQuery( '#jmcsg_state_short' ).val( address[x].short_name );
	          	jQuery( '#jmcsg_state_long' ).val( address[x].long_name );
	         } 
	         
	         if ( address[x].types == 'locality,political' ) {
	          	jQuery( '#jmcsg_city' ).val( address[x].long_name );
	         } 
	         
	         if ( address[x].types == 'postal_code' ) {
	          	jQuery( '#jmcsg_postcode' ).val( address[x].long_name );
	        } 
	        
	        if ( address[x].types == 'country,political' ) {

	          	jQuery( '#jmcsg_country_short' ).val( address[x].short_name );
	          	jQuery( '#jmcsg_country_long' ).val( address[x].long_name );
	         } 
		}	

		if ( ! JMCGS.is_admin ) {
			//submit the form
			setTimeout(function() {
				JMCGS.form_submit_button.click();
			}, 500);
		}
	}
}
jQuery( document ).ready( function(jQuery) {
	JMCGS.init();
});