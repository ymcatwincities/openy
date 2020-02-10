<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-visits-results">
    <div class="row top-line">
      <div class="col-myy-6">
        <strong>{{ data.length }} results</strong>
      </div>
      <div class="col-myy-6">
        <select v-model="sort" class="form-control form-select">
          <option value="date_ASC">Sort by date (ascending)</option>
          <option value="date_DESC">Sort by date (descending)</option>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col-myy">
        <p>Note: Visit history is only available online for the previous 18 months. Please contact our Customer Service Center at 612-230-9622 for full visit history.</p>
      </div>
    </div>
    <div class="row content">
      <div class="col-myy-12">
        <div v-for="(item, index) in data" v-bind:key="index" class="item-row row">
          <div class="col-myy-sm-1 no-padding-left">
            <span :class="'rounded_letter small ' + getUserColor(item.USR_LAST_FIRST_NAME)" v-if="getUserColor(item.USR_LAST_FIRST_NAME)">{{ item.SHORT_NAME }}</span>
          </div>
          <div class="col-myy-sm-4 user_name_wrapper">
            <span class="user_name">{{ item.USR_LAST_FIRST_NAME }}</span>
          </div>
          <div class="col-myy-sm-4 date_wrapper">
            <span class="date">{{ item.CUSTOM_USR_DATE }}</span><br/>
            <span class="duration">{{ item.CUSTOM_USR_TIME }}</span>
          </div>
          <div class="col-myy-sm-3">
            <span class="branch">{{ item.USR_BRANCH }}</span>
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
        data: {},
        userColors: [],
        sort: 'date_ASC',
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
            var start = typeof component.$route.query.start == 'undefined' ? moment().subtract(12, 'months').format('YYYY-MM-DD') : component.$route.query.start,
                end = typeof component.$route.query.end == 'undefined' ? moment().format('YYYY-MM-DD') : component.$route.query.end,
                ids = typeof component.$route.query.household == 'undefined' ? component.uid : component.$route.query.household,
                sort = typeof component.$route.query.sort == 'undefined' ? '' : component.$route.query.sort,
                url = component.baseUrl + 'myy-model/data/profile/visits-details/' + ids + '/' + start + '/' + end;

            if (sort !== '') {
              url += '?sort=' + sort;
            }

            component.loading = true;
            jQuery.ajax({
              url: url,
              xhrFields: {
                withCredentials: true
              }
            }).done(function(data) {
              component.data = data;
              component.loading = false;
              jQuery('.myy-sub-header .count span').text(data.length);
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

      component.runAjaxRequest();
    },
    watch: {
      '$route': function() {
        if (typeof this.$route.query.start != 'undefined' && typeof this.$route.query.end != 'undefined' && typeof this.$route.query.household != 'undefined') {
          this.runAjaxRequest();
        }
      },
      'sort': function(newValue, oldValue) {
        let component = this;
        var query = component.$route.query;
        query.sort = newValue;
        component.$router.push({query: query});
        component.runAjaxRequest();
      }
    }
  }
</script>

<style lang="scss">
  .myy-visits-results {
    line-height: 17px;
    margin-bottom: 40px;
    .top-line {
      margin-bottom: 20px;
      line-height: 40px;
      display: none;
      @media (min-width: 992px) {
        display: flex;
      }
      .count {
        font-weight: bold;
      }
      .form-select {
        border-color: #636466;
      }
    }
    .content {
      border: 1px solid #636466;
      margin: 0;
      @media (min-width: 992px) {
        padding: 0 20px;
      }
      .row {
        padding: 20px 0;
        margin: 0;
      }
    }
    .item-row.row {
      border-bottom: 1px solid #636466;
      position: relative;
      padding: 5px 0 10px 40px;
      @media (min-width: 576px) {
        padding: 20px 0;
      }
      .no-padding-left {
        position: absolute;
        left: 0;
        top: 10px;
        @media (min-width: 576px) {
          position: static;
          left: 0;
          top: 0;
        }
      }
      &:last-child {
        border-bottom: none;
      }
      .user_name_wrapper {
        margin-bottom: 5px;
      }
      .user_name {
        font-weight: bold;
        line-height: 18px;
        @media (min-width: 992px) {
          line-height: 21px;
        }
      }
      .date_wrapper {
        margin-bottom: 5px;
      }
      .date {
        font-weight: bold;
        line-height: 18px;
        @media (min-width: 992px) {
          line-height: 21px;
        }
      }
      .duration {
        line-height: 18px;
        @media (min-width: 992px) {
          line-height: 21px;
        }
      }
      .branch {
        line-height: 18px;
        @media (min-width: 992px) {
          line-height: 21px;
        }
      }
      .status {
        font-weight: bold;
        line-height: 18px;
        @media (min-width: 992px) {
          line-height: 21px;
        }
      }
    }
  }
</style>

