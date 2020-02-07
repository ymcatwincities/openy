<template>
  <section class="myy-membership">
    <div class="row headline">
      <div class="col-myy">
        <h3>Membership</h3>
      </div>
    </div>
    <div class="row content">
      <div class="col-myy">
        <div class="row payments">
          <div class="col-myy">
            <strong class="type">{{ data.title }}</strong>
          </div>
          <div class="col-myy">
            <span class="period">{{ data.orderType }}</span>
          </div>
          <div class="col-myy text-right">
            <span class="price">${{ data.productPrice }}</span>
          </div>
          <div class="col-myy text-right">
            <span class="status">{{ data.status }}</span>
          </div>
        </div>
        <!--@todo: uncomment once we have info about payment methods -->
        <!--<div class="row due-now">
          <div class="col-myy">
            <i class="fa fa-exclamation-triangle red"></i>
            <p class="red"><strong>Payment due: 12/1/18</strong></p>
            <i>Automatic Payments: Visa 1234</i>
          </div>
          <div class="col-myy text-right">
            <a class="btn btn-primary" href="#">Pay now <i class="fa fa-external-link-square"></i></a>
          </div>
        </div>-->
      </div>
    </div>
  </section>
</template>

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
        }).done(function (data) {
          if (data.isLogined === 1) {
            var url = component.baseUrl + 'myy-model/data/profile/membership';

            component.loading = true;
            jQuery.ajax({
              url: url,
              xhrFields: {
                withCredentials: true
              }
            }).done(function (data) {
              component.data = data;
              component.loading = false;
            });
          } else {
            // Redirect user to login page.
            window.location.pathname = component.baseUrl + 'myy-model/login'
          }
        });
      }
    },
    mounted: function() {
      let component = this;

      component.baseUrl = window.drupalSettings.path.baseUrl;

      component.runAjaxRequest();
    }
  }
</script>

<style lang="scss">
  .myy-membership {
    line-height: 17px;
    margin-bottom: 40px;
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
    }
    .payments {
      margin-bottom: 20px;
    }
    .due-now {
      margin-top: 20px;
      line-height: 21px;
      .fa-exclamation-triangle {
        line-height: 22px;
        float: left;
        margin-left: 10px;
        margin-right: 8px;
        font-size: 18px;
      }
      p {
        margin-top: 4px;
        margin-bottom: 0;
        line-height: 21px;
        text-transform: uppercase;
      }
      .btn {
        background-color: #92278F;
        border-color: #92278F;
        color: #fff;
        font-family: "Cachet Medium", sans-serif;
        font-size: 18px;
        line-height: 38px;
        padding: 6px 25px;
        text-transform: uppercase;
        &:hover {
          color: #fff;
        }
      }
    }
    .status {
      text-transform: uppercase;
      font-weight: bold;
      font-size: 14px;
      line-height: 17px;
      color: #231F20;
    }
  }
</style>

