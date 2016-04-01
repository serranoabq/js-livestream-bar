<?php
/*
	Plugin Name: Livestream Notification Bar
	Description: Plugin to create a notification bar at the top of your site to notify when your Livestream is live
	Version: 1.0.0
	Author: Justin R. Serrano
*/
new JS_LivestreamBar();

class JS_LivestreamBar(){
	
	function __construct(){
		// Need to get settings: account_name
		// Use customizer 
		add_action( 'customize_register', array( $this, 'customize_register' ), 11 );
		$account_name = get_theme_mod( 'livestream_account' );

		if( ! $account_name ) {
			add_action( 'admin_notices', array( $this, 'setting_admin_notice__error' ) );
		}
		
		add_action( 'wp_enqueue_scripts', 'add_scripts' );
		add_action( 'wp_footer', 'script_footer' );
		
	}
	
	function setting_admin_notice__error() {
		$class = 'notice notice-error';
		$message = sprintf( __( 'The Livestream account name must be set in the <a href="%s">customizer</a>.', 'js_livestream' ), admin_url( 'customize.php?autofocus[control]=livestream_account' );

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
		$ls_data = parse_livestream();
		wp_enqueue_script( 'js_livestream_bar', plugin_dir_path(__FILE__) . '/countdown.js', array( 'jquery' ) );
		wp_localize_script( 'js_livestream_bar', 'js_livestream', array(
			'start_time'	=> $ls_data[ 'start_time' ];
		));
	}
	
	function script_footer(){
		if( $ls_data[ 'streaming' ] ){
			// Streaming
			$text = sprintf( __( '<a href="%s">Livestream event is LIVE. Click here to watch.</a>', 'js_livestream' ), $ls_data[ 'url' ] );
		} elseif( $ls_data[ 'url' ] ) {
			// Upcoming
			$text = 'Livestream event starts in <span class="clock"><span class="</span>';
		} else {
			// Nothing
			$text = '';
		}
	?>
	<script>
		(function($){
			//js_livestream.start_time;
			
			var start_time = <?php echo $ls_data[ 'start_time' ]; ?>;
			
			function getTimeRemaining(endtime) {
				var t = Date.parse(endtime) - Date.parse(new Date());
				var seconds = Math.floor((t / 1000) % 60);
				var minutes = Math.floor((t / 1000 / 60) % 60);
				var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
				var days = Math.floor(t / (1000 * 60 * 60 * 24));
				return {
					'total': t,
					'days': days,
					'hours': hours,
					'minutes': minutes,
					'seconds': seconds
				};
			}
			
			function initializeClock(id, endtime) {
				var clock = document.getElementById(id);
				var daysSpan = clock.querySelector('.days');
				var hoursSpan = clock.querySelector('.hours');
				var minutesSpan = clock.querySelector('.minutes');
				var secondsSpan = clock.querySelector('.seconds');

				function updateClock() {
					var t = getTimeRemaining(endtime);

					daysSpan.innerHTML = t.days;
					hoursSpan.innerHTML = ('0' + t.hours).slice(-2);
					minutesSpan.innerHTML = ('0' + t.minutes).slice(-2);
					secondsSpan.innerHTML = ('0' + t.seconds).slice(-2);

					if (t.total <= 0) {
						clearInterval(timeinterval);
					}
				}

				updateClock();
				var timeinterval = setInterval(updateClock, 1000);
			}
		})(jQuery);
		
	</script>
<?php 		
		}

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
			error_log( __CLASS__ . ':' __FUNCTION__ . ': No account name given' ); 
			return false;
		}
		
		// Use transients for cache control
		$transient = get_transient( 'JS_livestream_JSON' );
		if( ! empty( $transient ) ){
			return $transient;
		} else {
			$url = "http://api.new.livestream.com/accounts/$name";
			$response = wp_remote_get( $url );
			if( is_array( $resposnse ) ){
				$data = json_decode( $response[ 'body' ] );
				set_transient( 'JS_livestream_JSON', $data, MINUTE_IN_SECONDS );
				return $data;
			} else {
				error_log( __CLASS__ . ':' __FUNCTION__ . ': Invalid response from ' . $url ); 
				return false;
			}
		}
	}
	
	// Parse Livestream status data
	function parse_livestream(){
		$data  = $this->fetch_livestream_data();
		$account_id = $data->id;
		if( $data->upcoming_events->data[0] ){
			$event_id = $data->upcoming_events->data[0]->id;
			// There's an upcoming event
			$livestream = array(
				'account_id' => $account_id,
				'streaming' => false,
				'url' => "http://livestream.com/accounts/$account_id/events/$event_id",
				'event_id' => $event_id,
				'start_time' => $data->upcoming_events->data[0]->start_time,
			);
			if ( $data->upcoming_events->data[0]->in_progress ){
				$livestream[ 'streaming' ] = true;
			}
		} else {
			// Nothing on tap
			$livestream = array(
				'account_id' => $account_id,
				'streaming' => false,
				'url' => '',
				'event_id' => '',
				'start_time' => '',
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
