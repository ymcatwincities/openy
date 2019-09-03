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
          <div class="col-sm-5 text-right">
            <span class="account_name">Hello, John!</span>
            <nuxt-link to="/myy/profile-settings" class="profile_settings"><i class="fa fa-gear"></i></nuxt-link>
            <nuxt-link to="/myy/sign-out" class="sign_out">Sign out</nuxt-link>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

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
      .sign_out {
        font-weight: bold;
        font-size: 14px;
        line-height: 48px;
      }
    }
  }
</style>

