<template>
  <section class="myy-header">
    <div class="myy-header__image">
      <!--<img src="~/assets/header-image.jpg" />-->
    </div>
    <div class="myy-header__content">
      <div class="container">
        <div class="row">
          <div class="col-myy-1 d-myy-lg-none">
            <button aria-controls="myy-sidebar" aria-expanded="false" class="collapsed myy-navbar-toggle visible-xs visible-sm" data-target="#myy-sidebar" data-toggle="collapse" type="button">
              <i class="fa fa-th"></i>
            </button>
          </div>
          <div class="col-myy-3">
            <h1>MY Y</h1>
          </div>
          <div v-if="$store.state.isLoggedIn" class="col-myy-8 col-myy-lg-9 text-right">
            <span class="account_name" v-if="data.household.length > 0">Hello, {{ data.household[0].name }}!</span>
            <a @click="logout" href="#" class="sign_out">Sign out</a>
          </div>
          <div v-else class="col-myy-8 col-myy-lg-9 text-right">
            <a href="/myy-model/login" class="sign_in">Login</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
<script>
  import VueCookie from 'vue-cookie';
  import Vue from 'vue'
  Vue.use(VueCookie);

  export default {
    components: {
    },
    data () {
      return {
        loading: true,
        baseUrl: '/',
        data: {
          household: []
        }
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
            var url = component.baseUrl + 'myy-model/data/profile/family-list';

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
      logout: function () {
        Vue.cookie.delete('Drupal.visitor.personify_authorized');
        Vue.cookie.delete('Drupal.visitor.personify_id');
        Vue.cookie.delete('Drupal.visitor.personify_time');
        window.location.pathname = '/myy-model/logout';
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
  .myy-header {
    background-color: #231F20;
    position: relative;
    height: 50px;
    overflow: hidden;
    @media (min-width: 992px) {
      height: 140px;
    }
    &__image {
      position: relative;
      z-index: 1;
    }
    &__content {
      position: absolute;
      top: 50%;
      margin-top: -25px;
      z-index: 2;
      width: 100%;
      h1 {
        font-family: "Cachet Medium";
        color: #fff;
        display: inline;
        font-size: 18px;
        line-height: 50px;
        @media (min-width: 992px) {
          font-size: 48px;
          line-height: 48px;
        }
      }
      .account_name {
        color: #fff;
        font-size: 12px;
        line-height: 48px;
        @media (min-width: 992px) {
          margin-right: 15px;
          font-size: 14px;
        }
      }
      a {
        color: #fff;
      }
      .profile_settings {
        border-radius: 20px;
        display: inline-block;
        background: #fff;
        line-height: 40px;
        width: 40px;
        text-align: center;
        margin: 0 20px;
        .fa {
          color: #0060af;
          font-size: 25px;
          vertical-align: middle;
        }
      }
      .sign_out,
      .sign_in {
        font-weight: bold;
        font-size: 12px;
        line-height: 48px;
        color: #fff;
        @media (min-width: 992px) {
          font-size: 14px;
        }
      }
    }
    .myy-navbar-toggle {
      position: relative;
      margin: 0;
      background: none;
      border: none;
      z-index: 3;
      .fa {
        color: #fff;
        line-height: 50px;
      }
    }
  }
</style>

