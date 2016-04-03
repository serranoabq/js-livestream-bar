<?php
/*
	Plugin Name: Livestream Notification Bar
	Description: Plugin to create a notification bar at the top of your site to notify when your Livestream is live
	Version: 1.0.0
	Author: Justin R. Serrano
*/

class JS_LivestreamBar {
	
	private $ls_status;
  	private $ls_time;
	private $ls_data;
  
	function __construct(){
		// Need to get settings: account_name
		// Use customizer 
		add_action( 'customize_register', array( $this, 'customize_register' ), 11 );
		$account_name = get_theme_mod( 'livestream_account' );

		if( ! $account_name ) {
			add_action( 'admin_notices', array( $this, 'setting_admin_notice__error' ) );
		}
		
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_action( 'wp_footer', array( $this, 'script_footer' ) );
		
	}
	
	function setting_admin_notice__error() {
		$class = 'notice notice-error';
		$message = sprintf( __( 'The Livestream account name must be set in the <a href="%s">customizer</a>.', 'js_livestream' ), admin_url( 'customize.php?autofocus[control]=livestream_account' ) );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
	}
	
	// Customizer settings
	function customize_register( $wp_customize ){
		
		$this->customize_createSection( $wp_customize, array(
			'id' => 'livestream',
			'title' => _x( 'Livestream Notification Bar', 'Customizer section title', 'js_livestream' ),
			'description' => _x( 'Settings for Livestream notification bar', 'Customizer section description', 'js_livestream' ),
		) );
		
		$this->customize_createSetting( $wp_customize, array(
			'id' => 'livestream_account',
			'label' => _x( 'Livestream username', 'Customizer setting', 'js_livestream' ),
			'type' => 'text',
			'default' => '',
			'section' => 'livestream',
		) );
		
	}
	
	function add_scripts(){
		$this->ls_data = $this->parse_livestream();
		$this->ls_status = $this->ls_data[ 'streaming' ];
      	$this->ls_time = $this->ls_data[ 'start_time' ];
		wp_enqueue_script( 'js_livestream_bar', plugin_dir_path(__FILE__) . '/countdown.js', array( 'jquery' ) );
		wp_localize_script( 'js_livestream_bar', 'js_livestream', array(
			'start_time'	=> $this->ls_time
		));
	}
	
	function script_footer(){
      $js_livestream_obj = array(
        'live' => sprintf( __( '<a href="%s" class="jsls_link">Livestream event is LIVE. Click here to watch.</a>' , 'js_livestream' ), $this->ls_data[ 'url' ] ),
        'upcoming' => __( 'Livestream event starts in <span id="clock"></span>' )
      );
		if( $this->ls_status ){
			// Streaming
			$bartext = $js_livestream_obj[ 'live'];
		} elseif( $this->ls_data[ 'url' ] ) {
			// Upcoming
			$bartext = $js_livestream_obj[ 'upcoming' ];
		} else {
			// Nothing
			$bartext = '';
		}
	?>
	<script>
		(function($){
			//js_livestream.start_time;
			var bar_text = <?php echo $bartext; ?>;
			var streaming = <?php echo $this->ls_status; ?>;
			if( bar_text ){
				var start_time = <?php echo $this->ls_time; ?>;
				$('body').prepend('<div id="jsls_bar">' + bar_text + '</div>' );
				if( ! streaming ){
                  initializeClock( 'clock' , js_livestream.start_time, function(){ $('#jsls_bar').innerHTML = <?php echo $js_livestream_obj[ 'live' ]; ?>; } );
				}
			} 
		})(jQuery);
		
	</script>
<?php 		
	}

	
	function shortcode(){
		// Get livestream data
		// Parse data to get status
		// Generate code
		
	}
	
	// Fetch data
	function fetch_livestream_data(){
		// Get account name
		$name = get_theme_mod( 'livestream_account' );
		if( ! $name ) {
			error_log( __CLASS__ . ':' .  __FUNCTION__ . ': No account name given' ); 
			return false;
		}
		
		// Use transients for cache control
		$transient = get_transient( 'JS_livestream_JSON' );
		if( ! empty( $transient ) && !is_user_logged_in()){
			return $transient;
		} else {
			$url = "http://api.new.livestream.com/accounts/$name";
			$response = wp_remote_get( $url );
			if( is_array( $response ) ){
				$data = json_decode( $response[ 'body' ] );
              	
				if( count( $data->upcoming_events->data ) > 0 ){
					// Upcoming event, set the transient to 1 minute to capture the status change
					set_transient( 'JS_livestream_JSON', $data, MINUTE_IN_SECONDS );
				} else {
					// No upcoming event, set the transient to 15 minutes
					set_transient( 'JS_livestream_JSON', $data, 15 * MINUTE_IN_SECONDS );
				}
				return $data;
			} else {
				error_log( __CLASS__ . ':' . __FUNCTION__ . ': Invalid response from ' . $url ); 
				return false;
			}
		}
	}
	
	// Parse Livestream status data
	function parse_livestream(){
		$data  = $this->fetch_livestream_data();
		$account_id = $data->id;
		if( count( $data->upcoming_events->data ) > 0 ){
			$event_id = $data->upcoming_events->data[0]->id;
			// There's an upcoming event
			$livestream = array(
				'account_id' => $account_id,
				'streaming' => false,
				'url' => "http://livestream.com/accounts/$account_id/events/$event_id",
				'event_id' => $event_id,
				'start_time' => $data->upcoming_events->data[0]->start_time,
			);
			if( $data->upcoming_events->data[0]->in_progress ){
				$livestream[ 'streaming' ] = true;
			}
		} else {
			// Nothing on tap
			$livestream = array(
				'account_id' => $account_id,
				'streaming' => null,
				'url' => null,
				'event_id' => null,
				'start_time' => null,
			);
		}
		return $livestream;
	}
	
	
	// Customizer shortcuts
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

	// Some Customizer shortcuts
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

}
new JS_LivestreamBar();