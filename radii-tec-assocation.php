<?php
/*
Plugin Name: Radii TEC Association Plugin
Description: This is a co-plugin to customize the main plugin of The Events Calendar Version 3.9.3  (required), The Events Calendar PRO Version 3.9.3  (required), The Filter bar Version 3.9.3  (required), Event RocketVersion 2.5.2 (required) - <strong>Feature A)</strong> Include WP Page content into an event category page if they have the same slug.<strong>Feature B)</strong> Related workshops can be attached to any event as long a you use the custom field WORKSHOP-PARENT-EVENT-ID <strong>Feature C)</strong> TEC Filter Bar modification - Event Type, Speaker, Region are added to the filter bar when you create the following event category parents  (event-region,event-type,event-speakers)
Version: 0.1
Author: Radii Production Inc.
Author URI: http://www.goradii.com
Text Domain: radii-tec-association
License: GPLv2 or later
*/


/*
		Features:

		FEATURE A) Include WP Page content into an event category page if they have the same slug.

		FEATURE B) Related workshops can be attached to any event as long a you 
					- you need a copy of /radii-tec-association/resources/related-workshops.php to your theme directory [theme]/tribe-events/pro/related-workshops.php
					- and WORKSHOP-PARENT-EVENT-ID is a custom field in each event workshop that contain the parent event ID.

		FEATURE C) TEC Filter Bar modifiction - Event Type, Speaker, Region are added to the filter bar
*/


/*
 *  dyoshida June 19, 2015
 *
 *	FEATURE A)  If a page slug matches the event category, the page content will be included in the pages.
 *
 */
add_action( 'tribe_events_before_template', 'TEC_action_hook_intro' ); 

function TEC_action_hook_intro () {

	// init
	$PARENT_SECTION_SLUG_NAME = '';  // THIS IS THE PARENT SLUG THAT WE WILL SEARCH FOR THESE PAGES!!
	$PARENT_SECTION_SLUG_NAME = apply_filters('radii_tec_parent_section_slug', $PARENT_SECTION_SLUG_NAME);

	// Figure out the TED category listing page that you are on
	$cat = get_queried_object();

	$pageName = '';
	if(!empty($cat)){ // FYI - the events landing page has no quieried object, so do nothing

		if(!empty($cat->slug)){  // This check was needed for the month view /events/month view
			// NOT EMPTY 
			$pageName = 'pagename='. $PARENT_SECTION_SLUG_NAME .  $cat->slug;    //  i.e. 'pagename=events/industry-events'
		}

		//var_dump($cat);
 		//echo $pageName; // debug
	}
 
	//JEFFREY'S ADDED CODE

	// Query for the industry events page
	$industry_events = new WP_Query( $pageName );
	// The Loop
	if ( $industry_events->have_posts() ) {
		echo '<div class="events-padding">';
		while ( $industry_events->have_posts() ) {
			$industry_events->the_post();
			the_content();
		}
		echo '</div>';
	} else {
		echo '';
	}
	// Reset post data (important!)
	wp_reset_postdata();

	//#JEFFREY'S ADDED CODE


} // End TEC_action_hook_intro()
// FEATURE A) END




/*
 *  dyoshida June 19, 2015
 *
 *	FEATURE B)  Related Workshop feature to single TEC page.
 * 
 * File reference \events-calendar-pro\events-calendar-pro.php
 */
add_action( 'tribe_events_single_event_after_the_meta', 'register_related_workshops_view', 11 );

function register_related_workshops_view() {
	if ( show_related_workshops() ) {
		tribe_single_related_workshops();
	}
}

function show_related_workshops() {
	if ( tribe_get_option('hideRelatedWorkshops', false) == true ) {
		return false;
	}

	return true;
}

add_filter( 'tribe_settings_tab_fields', 'radii_filter_settings_tab_fields', 11, 2 );

function radii_filter_settings_tab_fields($fields, $tab){


			switch ( $tab ) {
				case 'display':

					$fields = Tribe__Events__Main::array_insert_after_key(
						'tribeDisableTribeBar', $fields, array(
							'hideRelatedWorkshops' => array(
								'type'            => 'checkbox_bool',
								'label'           => __( 'Hide related workshops', 'tribe-events-calendar-pro' ),
								'tooltip'         => __( 'Remove related workshops from the single event view', 'tribe-events-calendar-pro' ),
								'default'         => false,
								'validation_type' => 'boolean',
							),
						)
					);					

					break;
			}

			return $fields;
}					

/*
 * File reference \events-calendar-pro\public\template-tags\general.php
 */
function tribe_get_related_workshops ($count=3) {
	_deprecated_function( __FUNCTION__, '3.9', "tribe_get_workshop_posts" );

	$posts = tribe_get_related_workshop_posts( $count );  // TODO:  SEE further down to change to CUSTOM FIELD

	if ( has_filter( 'tribe_get_related_workshops' ) ) {
		_deprecated_function( "The 'tribe_get_related_workshops' filter", '3.9', " the 'tribe_get_related_workshop_posts' filter" );
		$posts = apply_filters( 'tribe_get_related_workshops', $posts );
	}

		return $posts;
}	

function tribe_related_workshops ($title, $count=3, $thumbnails=false, $start_date=false, $get_title=true) {
	_deprecated_function( __FUNCTION__, '3.9', 'tribe_single_related_workshops' );
	if ( has_filter( 'tribe_related_workshops' ) ) {
		_deprecated_function( "The 'tribe_related_workshops' filter", '3.9', " the 'tribe_after_get_template_part' action for pro/related-workshops" );

		return apply_filters( 'tribe_related_workshops', tribe_single_related_workshops() );
	} else {
		tribe_single_related_workshops();
	}
}


function tribe_single_related_workshops( ) {
	
	// Reference - tribe_get_template_part( 'pro/related-workshops' ); // From parent plugin directory - Works!

	// Grab work stop template from co-plugin directory or the THEME directory
	if(is_file(get_template_directory() . "/related-workshops.php")){
	  	
	  	require_once( get_template_directory() . "/related-workshops.php");  // From Theme directory - Works!

	} else {

	   require_once( plugin_dir_path(__FILE__) . '/resources/related-workshops.php' ); // From child plugin directory - Works!
	}

}	

function tribe_get_related_workshop_posts( $count = 3, $post = false ) {

	$postid = get_the_ID();

	// Override: replace with custom  version of tribe_get_related_posts()
	$posts = tribe_get_events( array(
	'post_status' => 'publish',
	'posts_per_page' => $count,
	'meta_query' => array(
		array(
			'key' => 'WORKSHOP-PARENT-EVENT-ID',
			'value' => $postid,
		)),

	));


	return apply_filters( 'tribe_get_related_workshop_posts',  $posts ) ;
}
// FEATURE B) END




/*
 *  dyoshida June 19, 2015
 *
 *	FEATURE C) TEC Filter Bar modifiction - Event Type, Speaker, Region are added to the filter bar
 *
 */
//require_once(plugin_dir_path( __FILE__ ) . '../the-events-calendar-filterbar/lib/tribe-filter.class.php');
require_once(plugin_dir_path( __FILE__ ) . '../the-events-calendar-filterbar/src/Tribe/Filter.php');

require_once('TribeEventsFilter_CategoryEventType.php');
require_once('TribeEventsFilter_CategoryEventSpeaker.php');
require_once('TribeEventsFilter_CategoryEventRegion.php');

add_action( 'tribe_events_filters_create_filters', 'radii_tribe_events_filters_create_filters' ); 

function radii_tribe_events_filters_create_filters() {

	new TribeEventsFilter_CategoryEventType('Event Type', 'type_filter'); 
	new TribeEventsFilter_CategoryEventSpeaker('Speaker', 'taxonomy_filter');
	new TribeEventsFilter_CategoryEventRegion('Region', 'region_filter');
}
// FEATURE C) END


