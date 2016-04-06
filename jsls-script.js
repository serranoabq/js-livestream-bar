/* 	
	Countdown clock for js-livestream-bar
*/
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

function initializeClock(id, endtime, fn) {
	var clock = document.getElementById( id );
	
	function updateClock() {
		var t = getTimeRemaining( endtime );
		var sclock = "";
		if( t.days > 0 ) { sclock = t.days + 'd '; }
		if( t.hours > 0 && t.total > 3600 ) { sclock = sclock + ('0' + t.hours).slice(-2) + 'h '; }
		if( t.minutes > 0 && t.total > 60 ) { sclock = sclock + ('0' + t.minutes).slice(-2) + 'm '; }
		if( t.seconds > 0 && t.total > 0 )  { sclock = sclock + ('0' + t.seconds).slice(-2) + 's '; }

		clock.innerHTML = sclock;
		if( t.total <= 0 ) {
			fn();
			clearInterval( timeinterval );
		}
		
	} 

	updateClock();
	var timeinterval = setInterval( updateClock, 1000 );
}

(function($) {
	$( jsls_data.inject ).prepend('<div id="jsls_bar">' + (jsls_data.streaming ? jsls_data.live : jsls_data.upcoming) + '</div>' );
	var jsls_bar = $( '#jsls_bar' );
	if( ! jsls_data.streaming ){
		initializeClock( 'jsls_clock' , jsls_data.start_time, function(){ 
			jsls_bar.html( jsls_data.live ); 
		} );
	}
})( jQuery );