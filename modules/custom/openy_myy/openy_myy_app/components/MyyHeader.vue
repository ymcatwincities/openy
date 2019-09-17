<template>
  <section class="myy-header">
    <div class="myy-header__image">
      <!--<img src="~/assets/header-image.jpg" />-->
    </div>
    <div class="myy-header__content">
      <div class="container">
        <div class="row">
          <div class="col-sm-7">
            <h1>MY Y</h1>
          </div>
          <div v-if="isLoggedIn" class="col-sm-5 text-right">
            <span class="account_name">Hello, John!</span>
            <a href="/myy/logout" class="sign_out">Sign out</a>
          </div>
          <div v-else class="col-sm-5 text-right">
            <a href="/myy/login" class="sign_in">Login</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
  <script>
  export default {
    components: {
    },
    created: function() {
      this.isLoggedIn = this.$cookie.get('Drupal.visitor.personify_authorized');
      // Redirect user to login page if authorization cookie is not present.
/*      if (!this.isLoggedIn) {
        window.location.pathname = '/myy/login';
      }*/
    },
    methods: {
    }
  }
  </script>
<style lang="scss">
  .myy-header {
    background-color: #231F20;
    position: relative;
    height: 140px;
    overflow: hidden;

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
        font-size: 48px;
        line-height: 48px;
      }
      .account_name {
        color: #fff;
        font-size: 14px;
        line-height: 48px;
        margin-right: 15px;
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
        font-size: 14px;
        line-height: 48px;
      }
    }
  }
</style>

