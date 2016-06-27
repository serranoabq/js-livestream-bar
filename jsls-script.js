/* 	
	Countdown clock for js-livestream-bar
*/
(function($) {
	$(document).ready( function() {
		
		// Create the notification bar
		var jsls_bar = $('<div id="jsls_bar"></div>' ).html( jsls_data.streaming ? jsls_data.live : jsls_data.upcoming );
		jsls_bar.addClass( jsls_data.streaming ? 'live' : 'upcoming' );
		
		// If upcoming message will not be displayed, just hide the bar
		if( ! jsls_data.show_up ){ 
			jsls_bar.addClass( 'jsls-hiddenbar' );
		}
		var jsls = $( jsls_data.inject ).prepend( jsls_bar ); 
		
		if( ! jsls_data.streaming ){
			var jsls_clock = jsls_bar.find( '#jsls_clock' );
			if( ! jsls_clock ) { jsls_clock = $( '<span id="jsls_clock"></span>' ); }
			initializeClock( jsls_clock , jsls_data.start_time, function(){ 
				jsls_bar.html( jsls_data.live );
				jsls_bar.removeClass( 'jsls-hiddenbar' );
			} );
		}
		jsls_data.bar = jsls_bar;
	} );
	
	function getTimeRemaining( endtime ){
		var t = Date.parse( endtime ) - Date.parse( new Date() );
		var seconds = Math.floor( (t / 1000) % 60 );
		var minutes = Math.floor( (t / 1000 / 60) % 60 );
		var hours = Math.floor( (t / (1000 * 60 * 60)) % 24 );
		var days = Math.floor( t / (1000 * 60 * 60 * 24) );
		return {
			'total': t,
			'days': days,
			'hours': hours,
			'minutes': minutes,
			'seconds': seconds
		};
	}

	function initializeClock( element, endtime, fn ) {
		var clock = $( element );
		
		function updateClock() {
			var t = getTimeRemaining( endtime );
			var sclock = "";
			if( t.days > 0 ) { sclock = t.days + 'd '; }
			if( t.hours > 0 && t.total > 3600 ) { sclock = sclock + t.hours + 'h '; }
			if( t.minutes > 0 && t.total > 60 ) { sclock = sclock + ('0' + t.minutes).slice(-2) + 'm '; }
			if( t.total > 0 )  { sclock = sclock + ('0' + t.seconds).slice(-2) + 's '; }

			clock.html( sclock );
			
			if( t.total <= 0 ) {
				fn();
				clearInterval( timeinterval );
			}
			
		} 

		updateClock();
		var timeinterval = setInterval( updateClock, 1000 );
	}

	function debug( msg ){
		if( jsls_data.debug && console.log ){
			console.log( msg );
		}
	}
})( jQuery );