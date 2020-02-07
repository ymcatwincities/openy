<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-guest-passes">
    <div class="row headline">
      <div class="col-myy">
        <h3>Guest passes<sup>*</sup></h3>
      </div>
      <div class="col-myy text-right">
        <a href="#" class="link"><strong>Guest pass policy</strong> <i class="fa fa-external-link-square"></i></a>
      </div>
    </div>
    <div class="row content">
      <div class="col-myy">
        <span class="square_number small">{{ data.available }}</span> <strong>Available</strong>
      </div>
      <div class="col-myy">
        <span class="square_number small">{{ data.used }}</span> <strong>Used</strong>
      </div>
    </div>
    <div class="row description">
      <i><sup>*</sup>Guest pass balance will reset on Jan 1.</i>
    </div>
  </section>
</template>

<style lang="scss">
  .myy-guest-passes {
    margin-bottom: 40px;
    line-height: 17px;
    .headline {
      padding: 20px 5px;
      margin: 0;
      border: 1px solid #636466;
    }
    h3 {
      font-size: 14px;
      text-transform: uppercase;
      line-height: 17px;
      color: #636466;
      font-weight: bold;
      margin: 0;
    }
    .content {
      border-left: 1px solid #636466;
      border-right: 1px solid #636466;
      border-bottom: 1px solid #636466;
      margin: 0;
      padding: 20px 5px;
      strong {
        display: inline-block;
        vertical-align: top;
        line-height: 60px;
      }
    }
    .description {
      margin: 10px 0;
      i {
        font-size: 12px;
        line-height: 18px;
      }
    }
  }
</style>

<script>
  export default {
    data () {
      return {
        loading: true,
        baseUrl: '/',
        data: {}
      }
    },
    methods: {
      runAjaxRequest: function() {
        var component = this;
        // Check if user still logged in first.
        jQuery.ajax({
          url: component.baseUrl + 'myy-model/check-login',
          xhrFields: {
            withCredentials: true
          }
        }).done(function(data) {
          if (data.isLogined  ===  1) {
            var url = component.baseUrl + 'myy-model/data/profile/guest-passes';

          component.loading = true;
          jQuery.ajax({
            url: url,
            xhrFields: {
              withCredentials: true
            }
          }).done(function(data) {
            component.data = data;
            component.loading = false;
          });
          } else {
            // Redirect user to login page.
            window.location.pathname = component.baseUrl + 'myy-model/login'
          }
        });
      },
    },
    mounted: function() {
      let component = this;

      component.baseUrl = window.drupalSettings.path.baseUrl;

      component.runAjaxRequest();
    }
  }
</script>
