<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-visits">
    <div class="row headline">
      <div class="col-myy-6">
        <h3>Visits</h3>
      </div>
      <div class="col-myy-6 text-right">
        <span class="date">{{ currentMonth }}</span> | <nuxt-link to="/myy/visits" class="view_all">View all</nuxt-link>
      </div>
    </div>
    <div class="row content">
      <div v-for="(item, index) in data" v-bind:key="index" class="col-myy-md-6 col-myy-12">
        <div class="row">
          <div class="col-myy-3">
            <span :class="'rounded_letter ' + getUserColor(item.name)" v-if="getUserColor(item.name)">{{ item.short_name }}</span>
          </div>
          <div class="col-myy-3">
            <span class="square_number">{{ item.unique_total }}</span>
          </div>
          <div class="col-myy-6">
            <div class="name">{{ item.name }}</div>
            <div class="unique_visits">{{ item.unique_total }} unique visits*</div>
            <div class="total_visits">{{ item.total }} total visits</div>
          </div>
        </div>
      </div>
    </div>
    <div class="row description">
      <i>*Unique visits are the first visit on any given day. Only unique visits apply to health incentive goals.</i>
    </div>
  </section>
</template>

<script>
  export default {
    data () {
      return {
        loading: true,
        baseUrl: '/',
        data: {},
        currentMonth: '',
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
          if (data.isLogined === 1) {
            var url = component.baseUrl + 'myy-model/data/profile/visits-overview';

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
      component.uid = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.uid : '';
      component.householdColors = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.householdColors : [];

      component.currentMonth = moment().format('MMM YYYY');

      component.runAjaxRequest();
    }
  }
</script>

<style lang="scss">
  .myy-visits {
    line-height: 17px;
    margin-bottom: 20px;
    @media (min-width: 992px) {
      margin-bottom: 40px;
    }
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
    .date {
      color: #231F20;
      font-weight: bold;
    }
    .view_all {
      font-weight: bold;
    }
    .content {
      border-left: 1px solid #636466;
      border-right: 1px solid #636466;
      border-bottom: 1px solid #636466;
      margin: 0;
      padding: 20px 5px;
      .name {
        color: #0060AF;
        font-weight: bold;
        line-height: 21px;
        @media (min-width: 992px) {
          margin-top: 10px;
        }
      }
      .unique_visits {
        color: #231F20;
        line-height: 21px;
        font-weight: bold;
      }
      .total_visits {
        font-size: 12px;
        line-height: 21px;
      }
      .row {
        border-bottom: 1px solid #636466;
        padding-bottom: 10px;
        margin-bottom: 10px;
        @media (min-width: 768px) {
          border-bottom: none;
          padding-bottom: 0;
        }
      }
      > div:last-child {
        .row {
          border-bottom: none;
          padding-bottom: 0;
          margin-bottom: 0;
        }
      }
    }
    .description {
      margin: 10px 0;
      i {
        font-size: 10px;
        line-height: 15px;
        @media (min-width: 992px) {
          font-size: 12px;
          line-height: 18px;
        }
      }
    }
  }
</style>

