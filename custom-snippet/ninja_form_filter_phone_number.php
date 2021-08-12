<!-- This will sanitize user phone number and all only digit phone number -->
<?php

add_filter( 'ninja_forms_submit_data', function( $form_data ){

foreach ($form_data['fields'] as $field) {
	if (in_array('phone_1623930654823', $field)) {
       if( !preg_match("/^[\+0-9\-\(\)\s]*$/", $field['value'] ) ) { // Add check here.

       	$errors = [
       		'fields' => [
       			'6' => __( 'This is not valid phone number.' )
       		],
       	];

         echo wp_json_encode( $response );
         wp_die(); // this is required to terminate immediately and return a proper response
     }

  }
}

// If no errors, be sure to return the $form_data.
return $form_data;

});
