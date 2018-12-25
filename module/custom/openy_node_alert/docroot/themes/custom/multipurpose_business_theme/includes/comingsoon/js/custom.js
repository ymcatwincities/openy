$(function(){

	//Enter your end date here, follow same format
	var endDay = new Date('May 24, 2016 11:30:00');

	var countDown = setInterval(function(){
	    var currentDate = new Date();
	    var daysdecimal = (endDay - currentDate)/(1000*60*60*24);
	    var daysLeft = Math.floor(daysdecimal);
	    var hoursdecimal = (daysdecimal - Math.floor(daysdecimal))*24;
	    var hoursLeft = Math.floor(hoursdecimal);
	    var minLeft = 60 - currentDate.getMinutes();
	    var secLeft = 60 - currentDate.getSeconds();
	    
	    $('#days').text(daysLeft);
	    $('#hours').text(hoursLeft);
	    $('#minutes').text(minLeft);
	    $('#seconds').text(secLeft);   
	},1000);
});