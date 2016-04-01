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

function initializeClock(id, endtime, fn) {
  var clock = document.getElementById(id);
  var daysSpan = clock.querySelector('.days');
  var hoursSpan = clock.querySelector('.hours');
  var minutesSpan = clock.querySelector('.minutes');
  var secondsSpan = clock.querySelector('.seconds');

  function updateClock() {
    var t = getTimeRemaining(endtime);

		if( t.days > 0 ) { 
			daysSpan.innerHTML = t.days; 
		} else { 
			daysSpan.parentNode.removeChild(daysSpan); 
		}
		if( t.hours > 0 ) { 
			hoursSpan.innerHTML = ('0' + t.hours).slice(-2); 
		} else { 
			hoursSpan.parentNode.removeChild(hoursSpan); 
		}
		if( t.minutes > 0 && t.total > 60 ) { 
			minutesSpan.innerHTML = ('0' + t.minutes).slice(-2); 
		} else { 
			minutesSpan.parentNode.removeChild(minutesSpan); 
		}
    if( t.seconds > 0 && t.total > 0) { 
			secondsSpan.innerHTML = ('0' + t.seconds).slice(-2); 
		} else { 
			secondsSpan.parentNode.removeChild(secondsSpan); 
		}

    if (t.total <= 0) {
			if( fn ) fn();
      clearInterval(timeinterval);
    }
  }

  updateClock();
  var timeinterval = setInterval(updateClock, 1000);
}

//var deadline = new Date(Date.parse(new Date()) + 15 * 24 * 60 * 60 * 1000);
//initializeClock('clockdiv', deadline);