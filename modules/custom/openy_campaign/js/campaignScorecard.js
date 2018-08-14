(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.campaignScorecard = {
    attach: function (context, settings) {

      $('.path-team-member-registration, .path-campaign-scorecards').find('select[name="campaign_id"]').once('campaignScorecard').each(function(){
        $(this).on('change', function (){
          $('#scorecard-wrapper').html('');

          $.ajax({
            url : '/ajaxCallback/campaignScorecard/' + $(this).val(),
            data: {"node": $(this).val() },
            type: 'GET',

            success: function(data){
              $('#scorecard-wrapper').html(data);
            }
          });
        })
      })
    }
  }
})(jQuery, Drupal);
