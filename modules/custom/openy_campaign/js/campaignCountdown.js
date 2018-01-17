Drupal.behaviors.campaignCountdown = {
    attach: function (context, settings) {
        if (Drupal.behaviors.campaignCountdown.length){
            return;
        }
        jQuery('.countdown').html('');

        // Parse campaign end registration date to the Date object
        var dateObj = moment(settings.campaignSettings.endRegDate + '.0000Z');
        var campaignRegEndDate = new Date(dateObj);

        simplyCountdown('.countdown', {
            year: campaignRegEndDate.getFullYear(), // required
            month: campaignRegEndDate.getMonth() + 1, // required
            day: campaignRegEndDate.getDate(), // required
            hours: campaignRegEndDate.getHours(), // Default is 0 [0-23] integer
            minutes: campaignRegEndDate.getMinutes(), // Default is 0 [0-59] integer
            seconds: campaignRegEndDate.getSeconds(), // Default is 0 [0-59] integer
            words: { //words displayed into the countdown
                days: 'day',
                hours: 'hour',
                minutes: 'minute',
                seconds: 'second',
                pluralLetter: 's'
            },
            plural: true, //use plurals
            inline: false, //set to true to get an inline basic countdown like : 24 days, 4 hours, 2 minutes, 5 seconds
            inlineClass: 'simply-countdown-inline', //inline css span class in case of inline = true
            // in case of inline set to false
            enableUtc: false,
            onEnd: function () {
                // your code
                return;
            },
            refresh: 1000, //default refresh every 1s
            sectionClass: 'simply-section', //section css class
            amountClass: 'simply-amount', // amount css class
            wordClass: 'simply-word' // word css class
        });
    }
};