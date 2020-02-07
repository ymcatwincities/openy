<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-household">
    <div class="row headline">
      <div class="col-myy-sm-12 col-myy-12">
        <h3>Household</h3>
      </div>
    </div>
    <div class="row content">
      <div class="col-myy-sm-12 col-myy-12">
        <div class="row">
          <div v-for="(item, index) in data.household" v-bind:key="index" class="item col-myy-md-3 col-myy-6 col-myy-md-3">
            <span :class="'rounded_letter ' + getUserColor(item.name)" v-if="getUserColor(item.name)">{{ item.short_name }}</span>
            <div class="name">{{ item.name }}</div>
            <div class="age">{{ item.age }}</div>
            <div class="dropdown">
              <button class="btn btn-primary select dropdown-toggle" type="button" :id="'dropdownMenu' + index" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Select
              </button>
              <div class="dropdown-menu select" aria-labelledby="dropdownMenu1">
                <a :href="index" class="dropdown-item" type="button" v-bind:key="index" v-for="(item, index) in item.ProfileLinks">{{ item }}</a>
              </div>
            </div>
          </div>
        </div>
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
      getUserColor: function (username) {
        for (var i in this.householdColors) {
          if (i == username) {
            return this.householdColors[i];
          }
        }
      }
    },
    mounted: function() {
      let component = this;

      component.baseUrl = window.drupalSettings.path.baseUrl;
      component.householdColors = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.householdColors : [];

      component.runAjaxRequest();
    }
  }
</script>

<style lang="scss">
  .myy-household {
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
      padding: 20px 5px 5px;
      .item {
        text-align: center;
        margin-bottom: 15px;
      }
      .rounded_letter {
        margin-bottom: 10px;
        @media (min-width: 992px) {
          margin-bottom: 20px;
        }
      }
      .name {
        color: #231F20;
        font-weight: bold;
        line-height: 21px;
      }
      .btn {
        background-color: #92278F;
        border-color: #92278F;
        text-align: left;
        font-size: 14px;
        margin-top: 10px;
        width: 100%;
        &:after {
          position: absolute;
          right: 10px;
          top: 25px;
        }
      }
    }
  }
</style>

