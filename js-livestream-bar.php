<?php
/*
	Plugin Name: Livestream Notification Bar
	Description: Plugin to create a notification bar at the top of your site to notify when your Livestream is live
	Version: 1.1
	Author: Justin R. Serrano
*/

class JS_LivestreamBar {
	
	private $ls_status;
	private $ls_time;
	private $ls_data;
	private $account_name;
	private $debug_enabled = false;
	
	function __construct(){
		// Use Custommizer to set settings
		add_action( 'customize_register', array( $this, 'customize_register' ), 11 );
		$this->account_name = get_theme_mod( 'livestream_account' );

		if( ! $this->account_name ) {
			add_action( 'admin_notices', array( $this, 'setting_admin_notice__error' ) );
		}
		
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
		
	}
	
	function setting_admin_notice__error() {
		$class = 'notice notice-error';
		$message = sprintf( __( 'The Livestream account name must be set in the <a href="%s">customizer</a>.', 'js_livestream' ), admin_url( 'customize.php?autofocus[control]=livestream_account' ) );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
	}
	
	// Customizer settings
	function customize_register( $wp_customize ){
		
		// Add new section
		$this->customize_createSection( $wp_customize, array(
			'id' => 'livestream',
			'title' => _x( 'Livestream Notification Bar', 'Customizer section title', 'js_livestream' ),
			'description' => _x( 'Settings for Livestream notification bar', 'Customizer section description', 'js_livestream' ),
		) );
		
		// Add controls
		// Username
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_account',
			'label' => _x( 'Livestream username', 'Customizer setting', 'js_livestream' ),
			'type' => 'text',
			'default' => '',
			'section' => 'livestream',
		) );
		
		// Location
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_location',
			'label' => _x( 'Location', 'Customizer setting label', 'js_livestream' ),
			'type' => 'select',
			'choices' => array(
				'front' => 'Front Page',
				'all' => 'Everywhere'
			),
			'description' => _x( 'Choose whether to display the notification bar on the front page only or everywhere.', 'Customizer setting description', 'js_livestream' ),
			'default' => 'front',
			'section' => 'livestream',
		) );
		
		// Injection point
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_inject',
			'label' => _x( 'DOM Element to inject into', 'Customizer setting label', 'js_livestream' ),
			'type' => 'text',
			'description' => _x( 'Enter the CSS selector of the parent element the bar will be injected into (e.g. <code>body</code>, <code>#header</code>, <code>.top</code>).', 'Customizer setting description', 'js_livestream' ),
			'default' => 'body',
			'section' => 'livestream',
		) );
		
		// Live link text
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_livelink',
			'label' => _x( 'Link text', 'Customizer setting label', 'js_livestream' ),
			'type' => 'text',
			'description' => _x( 'Enter the text to include in the link when the event is live', 'Customizer setting description', 'js_livestream' ),
			'default' => __( 'Livestream event is LIVE. Click here to watch.' , 'js_livestream' ),
			'section' => 'livestream',
		) );
		
		// Upcoming text
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_upcomingtext',
			'label' => _x( 'Upcoming  text', 'Customizer setting label', 'js_livestream' ),
			'type' => 'text',
			'description' => _x( 'Enter the text to include bar when the event is scheduled. <code>{{CLOCK}}</code> indicates where the countdown will be placed', 'Customizer setting description', 'js_livestream' ),
			'default' => __( 'Livestream starts in {{CLOCK}}' , 'js_livestream' ),
			'section' => 'livestream',
		) );
		
		// Custom CSS
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_css',
			'label' => _x( 'Custom CSS', 'Customizer setting label', 'js_livestream' ),
			'type' => 'textarea',
			'description' => _x( 'Enter any custom CSS to apply to the notification bar. The following ids are used: <code>jsls_bar</code>, <code>jsls_link</code>, and <code>jsls_clock</code>', 'Customizer setting description', 'js_livestream' ),
			'default' => '',
			'section' => 'livestream',
		) );
		
		// Cache time
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_unsched_cache',
			'label' => _x( 'Unscheduled Cache Time', 'Customizer setting label', 'js_livestream' ),
			'type' => 'select',
			'choices'=> array(
				'1440' => '24 hours',
				'720' => '12 hours',
				'360' => '6 hours',
				'60' => '1 hour',
				'30' => '30 minutes',
				'15' => '15 minutes',
			),
			'description' => _x( 'Choose the periodicity to flush the cache of the Livestream request if there is no event scheduled. If an event is scheduled', 'Customizer setting description', 'js_livestream' ),
			'default' => '60',
			'section' => 'livestream',
		) );
		
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_sched_cache',
			'label' => _x( 'Scheduled Cache Time', 'Customizer setting label', 'js_livestream' ),
			'type' => 'select',
			'choices'=> array(
				'30' => '30 minutes',
				'15' => '15 minutes',
				'10' => '10 minutes',
				'5' => '5 minutes',
				'1' => '1 minutes',
			),
			'description' => _x( 'Choose the periodicity to flush the cache of the Livestream request if there is an event scheduled.', 'Customizer setting description', 'js_livestream' ),
			'default' => '1',
			'section' => 'livestream',
		) );
		
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_showupcoming',
			'label' => _x( 'Show bar when upcoming', 'Customizer setting label', 'js_livestream' ),
			'type' => 'checkbox',
			'description' => _x( 'Check to display the bar and countdown when an event is scheduled, but not live yet. ', 'Customizer setting description', 'js_livestream' ),
			'default' => true,
			'section' => 'livestream',
		) );

	}
	
	function add_scripts(){
		// Fetch and parse the data first
		$this->ls_data = $this->parse_livestream();
		
		$location = get_theme_mod( 'livestream_location' );
		if( 'front' == $location && ! is_front_page() ) return;
				
		if( $this->ls_data ){
			// If there's valid data, proceed
			$this->ls_status = $this->ls_data[ 'streaming' ];
			$this->ls_time   = $this->ls_data[ 'start_time' ];
			
			// Add scripts
			wp_enqueue_script( 'jsls-script', plugins_url( 'jsls-script.js', __FILE__ ), array( 'jquery') );
			
			$jsls_data = array(
				'live'     => sprintf( '<a href="%s" class="jsls_link">' . get_theme_mod( 'livestream_livelink' ) . '</a>', $this->ls_data[ 'url' ] ),
				'upcoming' => str_replace( '{{CLOCK}}', '<span id="jsls_clock"></span>', get_theme_mod( 'livestream_upcomingtext' ) ),
				'streaming' => $this->ls_status,
				'start_time' => $this->ls_time,
				'inject' => get_theme_mod( 'livestream_inject' ),
				'show_up' => get_theme_mod( 'livestream_showupcoming' ),
			);
			wp_localize_script( 'jsls-script', 'jsls_data', $jsls_data );
			add_action( 'wp_footer', array( $this, 'style_footer' ) );
		}
	}
	
	function style_footer(){
		if( $this->ls_data ){
			// There is a live or scheduled event
			// Add custom CSS
			if( get_theme_mod( 'livestream_css' ) ){
				echo '<style>' . get_theme_mod( 'livestream_css' ) . '</style>';
			}
		}
	}
	
	// Fetch data
	function fetch_livestream_data(){
		// Get account name
		$name = get_theme_mod( 'livestream_account' );
		if( ! $name ) {
			$this->debug( __CLASS__ . ':' .  __FUNCTION__ . ': No account name given' ); 
			return false;
		}
		
		// Use transients for cache control
		$transient = get_transient( 'JS_livestream_JSON' );
		if( ! empty( $transient ) && ! is_customize_preview() ){
			
			// Use transients to avoid excessive remote calls
			return $transient;
			
		} else {
			
			// Build URL and fetch data
			$url = "http://api.new.livestream.com/accounts/$name";
			$response = wp_remote_get( $url );
			
			// Process the response
			if( is_array( $response ) ){
				
				$data = json_decode( $response[ 'body' ] );    
				
				if( json_last_error() === JSON_ERROR_NONE ){
					// Check for valid JSON
					$cache_time = intval( get_theme_mod( 'livestream_unsched_cache' ) );
					if( count( $data->upcoming_events->data ) > 0 || is_customize_preview() ){
						$cache_time = intval( get_theme_mod( 'livestream_sched_cache' ) ); 
					}
					set_transient( 'JS_livestream_JSON', $data, $cache_time * MINUTE_IN_SECONDS );
					return $data;
					
				} else {
					// Invalid JSON
					
					$this->debug( __CLASS__ . ':' . __FUNCTION__ . ': Invalid JSON response' );
					return false;
				}
			} else {
				// Invalid response
				
				$this->debug( __CLASS__ . ':' . __FUNCTION__ . ': Invalid response from ' . $url ); 
				return false;
				
			}
		}
	}
	
	// Parse Livestream status data
	// Returns an array object with the event information or FALSE if there is nothing scheduled or live
	function parse_livestream(){
		// Get the data
		$data  = $this->fetch_livestream_data();
		
		// Start parsing the data
		if( ! $data ) return false;
		
		if( count( $data->upcoming_events->data ) > 0 ){
			// There's an upcoming event
			
			// Return account_id, streaming status, url, event_id, and start_time
			$account_id = $data->id;
			$event_id   = $data->upcoming_events->data[0]->id;
			return array(
				'account_id' => $account_id,
				'streaming'  =>  $data->upcoming_events->data[0]->in_progress,
				'url'        => "http://livestream.com/accounts/$account_id/events/$event_id",
				'event_id'   => $event_id,
				'start_time' => $data->upcoming_events->data[0]->start_time,
			);
			
		} else {
			$this->debug( __CLASS__ . ':' . __FUNCTION__ . ': Nothing scheduled' ); 
			
			// Nothing on tap
			return false;
			
		}
	}
	
	
	// Customizer shortcut for section creation
	function customize_createSection( $wp_customize, $args ) {
		$default_args = array(
			'id' 	            => '', // required
			'title'           => '', // required
			'priority'        => '', // optional
			'description'     => '', // optional
			'active_callback' => '', // optional
			'panel'           => '', // optional
		);
		
		// Check for required inputs
		if( ! ( isset( $args[ 'id' ] ) AND isset( $args[ 'title' ] ) ) ) return;
		if( empty( $args[ 'id' ] ) ||  empty( $args[ 'title' ] ) ) return;
		
		$id = $args[ 'id' ];
		unset( $args[ 'id' ] );
		$wp_customize->add_section( $id, $args );
	}

	// Customizer shortcut for setting creation
	function customize_createSetting( $wp_customize, $args ) {
		$default_args = array(
			'id' 	              => '', // required
			'type'              => 'text', // required. This refers to the control type. 
																		 // All settings are theme_mod and accessible via get_theme_mod.  
																		 // Other types include: 'number', 'checkbox', 'textarea', 'radio',
																		 // 'select', 'dropdown-pages', 'email', 'url', 'date', 'hidden',
																		 // 'image', 'color'
			'label'             => '', // required
			'default'           => '', // required
			'section'           => '', // required
			'sanitize_callback' => '', // optional
			'transport'         => '', // optional
			'description'       => '', // optional
			'priority'          => '', // optional
			'choices'           => '', // optional
			'panel'             => '', // optional
		);
		
		// Available types and arguments
		$available_types = array( 'text', 'number', 'checkbox', 'textarea', 'radio', 'select', 'dropdown-pages', 'email', 'url', 'date', 'hidden', 'image', 'color' );
		$setting_def_args = array( 'default'=> '', 'sanitize_callback'=>'', 'transport'=>'' );
		$control_def_args = array( 'type'=>'', 'label'=>'', 'description'=>'', 'priority'=>'', 'choices'=>'', 'section'=>'' );
		// Check for required inputs
		if( ! ( isset( $args[ 'id' ] ) AND 
						isset( $args[ 'default' ] ) AND 
						isset( $args[ 'section' ] ) AND 
						isset( $args[ 'type' ] ) ) )
			return;
		// Check for non-empty inputs, too
		if( empty( $args[ 'id' ] ) ||  
				empty( $args[ 'section' ] ) ||  
				empty( $args[ 'type' ] ) )
			return;
			
		// Check for a right type
		if( ! in_array( $args[ 'type' ], $available_types ) ) $args[ 'type' ] = 'text';
		
		$id = $args[ 'id' ];
		unset( $args[ 'id' ] );
		
		// Split setting arguments and control arguments
		$setting_args = array_intersect_key( $args, $setting_def_args );
		$control_args = array_intersect_key( $args, $control_def_args );
		
		$wp_customize->add_setting( $id, $setting_args );
		
		if( 'image' == $args[ 'type' ] ) {
			$wp_customize->add_control( new WP_Customize_Image_Control(
				$wp_customize,
				$id,
				array(
					'label'      => $args[ 'label' ] ? $args[ 'label' ] : '',
					'section'    => $args[ 'section' ],
					'settings'   => $id,
					'description'=> $args[ 'description' ] ? $args[ 'description' ] : ''
				)
			) );
		} elseif( 'color' == $args[ 'type' ] ) {
			$wp_customize->add_control( new WP_Customize_Image_Control(
				$wp_customize,
				$id,
				array(
					'label'      => $args[ 'label' ] ? $args[ 'label' ] : '',
					'section'    => $args[ 'section' ],
					'settings'   => $id,
					'description'=> $args[ 'description' ] ? $args[ 'description' ] : ''
				)
			) );
		} else {
			$wp_customize->add_control( $id, $control_args );
		}
	}
	
	// Debug function
	function debug( $msg ){
		if( is_user_logged_in() && $this->debug_enabled ){
			error_log( $msg );
		}
	}
	
}
new JS_LivestreamBar();