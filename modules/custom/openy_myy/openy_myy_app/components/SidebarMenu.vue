<template>
  <div class="myy-sidebar_menu">
    <ul class="myy-sidebar_menu__nav">
      <li>
        <nuxt-link to="/myy/dashboard">dashboard</nuxt-link>
      </li>
      <li>
        <nuxt-link to="/myy/childcare">childcare</nuxt-link>
      </li>
      <li class="nav-item">
        <a href="#" v-on:click.prevent class="collapsed" data-toggle="collapse" data-target="#submenu-1">activities <i class="fa fa-chevron-up"></i><i class="fa fa-chevron-down"></i></a>
        <ul class="collapse" id="submenu-1" aria-expanded="false">
          <li class="nav-item">
            <a class="nav-link" href="/">Group Exercise schedules <i class="fa fa-external-link"></i></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/">Activity Finder <i class="fa fa-external-link"></i></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/">Personal Training <i class="fa fa-external-link"></i></a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/">Small Group Training</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/">Camps <i class="fa fa-external-link"></i></a>
          </li>
        </ul>
      </li>
      <li class="nav-item">
        <a href="#" v-on:click.prevent class="collapsed" data-toggle="collapse" data-target="#submenu-2">transactions <i class="fa fa-chevron-up"></i><i class="fa fa-chevron-down"></i></a>
        <ul class="collapse" id="submenu-2" aria-expanded="false">
          <li class="nav-item">
            <nuxt-link to="/myy/orders-receipts" class="nav-link">Orders & receipts</nuxt-link>
          </li>
          <li class="nav-item">
            <nuxt-link to="/myy/visits" class="nav-link">Visits</nuxt-link>
          </li>
        </ul>
      </li>
      <li>
        <nuxt-link to="/myy/membership">membership</nuxt-link>
      </li>
    </ul>
  </div>
</template>

<style lang="scss">
  .myy-sidebar_menu {
    &__top {
      background-color: #0a6eb4;
      color: #fff;
      font-family: "Cachet Medium", sans-serif;
      font-size: 18px;
      line-height: 21px;
      margin-bottom: 20px;
      padding: 15px 20px 14px;
      .fa {
        color: #fff;
      }
      button {
        display: inline-block !important;
      }
    }
  }
  .myy-sidebar_menu__nav {
    list-style: none;
    padding: 0;
    margin: 0;
    display: none;
    @media (min-width: 992px) {
      display: block;
    }
    > li {
      border-bottom: 1px solid #777;
      padding: 16px 0;
      &:first-child {
        border-top: 1px solid #777;
      }
      > a {
        color: #777;
        font-weight: bold;
        font-size: 14px;
        line-height: 17px;
        text-transform: uppercase;
        display: block;
        &.nuxt-link-exact-active,
        &.nuxt-link-active {
          color: #231F20;
        }
        i {
          float: right;
          width: 50px;
          text-align: center;
        }
        .fa-chevron-up {
          display: block;
        }
        .fa-chevron-down {
          display: none;
        }
        &.collapsed {
          .fa-chevron-up {
            display: none;
          }
          .fa-chevron-down {
            display: block;
          }
        }
      }
      ul {
        background-color: #f2f2f2;
        list-style: none;
        margin: 10px 0 -10px;
        padding: 0;
        li {
          a {
            color: #0060af;
            font-weight: bold;
            font-size: 14px;
            line-height: 20px;
            display: block;
            padding: 15px 40px 15px 10px;
            position: relative;
            &.nuxt-link-exact-active,
            &.nuxt-link-active {
              color: #231F20;
            }
            &:hover {
              text-decoration: underline;
            }
            i {
              font-size: 18px;
              text-align: center;
              position: absolute;
              right: 14px;
              top: 50%;
              margin-top: -8px;
            }
          }
        }
      }
    }
  }
  #myy-sidebar {
    background: #fff;
    left: 0;
    bottom: 0;
    display: block !important;
    height: auto;
    overflow: hidden;
    position: fixed;
    top: 0;
    visibility: visible;
    z-index: 40;
    &.collapse {
      display: none;
      width: 0 !important;
    }
    &.collapsing {
      display: none;
      width: 100% !important;
    }
    &.show {
      width: 100% !important;
      display: block;
    }
    &.collapse.in {
      display: block;
      width: 100% !important;
    }
    .myy-sidebar_menu__nav {
      margin: 0 20px;
      display: block;
    }
  }
</style>

