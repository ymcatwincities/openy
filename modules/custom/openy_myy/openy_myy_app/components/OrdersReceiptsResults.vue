<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-orders-receipts-results">
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
    </div>
    <div class="row content">
      <div class="col-myy">
      <!-- @todo Uncomment once we have info with payments data -->
      <!-- <div class="result_row">
          <div class="row">
            <div class="col-myy-sm-1">
              <span class="rounded_letter small black">X</span>
            </div>
            <div class="col-myy-sm-3">
              <div class="type"><a href="#"><strong>Personal Training Sessions</strong></a></div>
            </div>
            <div class="col-myy-sm-4">
              <div><strong>Full Sessions (60min)</strong></div>
              <span class="weekdays">20 sessions</span>
            </div>
            <div class="col-myy-sm-2 text-right">
              $1,009.00K
            </div>
            <div class="col-myy-sm-2 text-right">
              <strong>PENDING</strong>
            </div>
          </div>
          <div class="row due-now">
            <div class="col-myy">
              <i class="fa fa-exclamation-triangle"></i>
              <p><strong>Payment due: 1/1/19</strong></p>
              <i>Automatic Payments: Visa 1234</i>
            </div>
            <div class="col-myy text-right">
              <a class="btn btn-primary" href="#">Pay now <i class="fa fa-external-link-square"></i></a>
            </div>
          </div>
        </div>
        <div class="result_row">
          <div class="row">
            <div class="col-myy-sm-1">
              <span class="rounded_letter small black">X</span>
            </div>
            <div class="col-myy-sm-3">
              <div class="type"><a href="#"><strong>Personal Training Sessions</strong></a></div>
            </div>
            <div class="col-myy-sm-4">
              <div><strong>Full Sessions (60min)</strong></div>
              <span class="weekdays">20 sessions</span>
            </div>
            <div class="col-myy-sm-2 text-right">
              $1,009.00
            </div>
            <div class="col-myy-sm-2 text-right">
              <strong class="red">PAST DUE</strong>
            </div>
          </div>
          <div class="row due-now">
            <div class="col-myy">
              <p class="red"><strong>Payment due: 12/1/18</strong></p>
              <i>Automatic Payments: Visa 1234</i>
            </div>
            <div class="col-myy text-right">
              <a class="btn btn-primary" href="#">Pay now <i class="fa fa-external-link-square"></i></a>
            </div>
          </div>
        </div>
        <div class="result_row">
          <div class="row">
            <div class="col-myy-sm-1">
              <span class="rounded_letter small black">X</span>
            </div>
            <div class="col-myy-sm-3">
              <div class="type"><a href="#"><strong>Membership Fees</strong></a></div>
              Family
            </div>
            <div class="col-myy-sm-4">
              <div><strong>December 2019</strong></div>
              <span class="weekdays">Monthly</span>
            </div>
            <div class="col-myy-sm-2 text-right">
              $129.00
            </div>
            <div class="col-myy-sm-2 text-right">
              <strong>PAID</strong>
            </div>
          </div>
        </div>-->
        <div class="result_row" v-for="(item, index) in data" v-bind:key="index">
          <div class="row">
            <div class="col-myy-sm-1 user-icon-wrapper no-padding-left">
              <span :class="'rounded_letter small color-' + getUserColor(item.name)" v-if="getUserColor(item.name)">{{ item.short_name }}</span>
            </div>
            <div class="col-myy-sm-4 title-wrapper">
              <div class="type"><strong>{{ item.title }}</strong></div>
            </div>
            <div class="col-myy-sm-5 description-wrapper">
              <div><strong>{{ item.description }}</strong></div>
            </div>
            <div class="col-myy-sm-2 total-wrapper">
              ${{ item.total }}
            </div>
            <div class="col-myy-sm-2 text-right status-wrapper no-padding-right">
              <strong v-if="item.payed == 1">PAID</strong>
              <strong v-if="item.payed == 0">PENDING</strong>
            </div>
          </div>
          <div v-if="item.due_amount && item.due_date" class="row due-now">
            <div class="col-myy">
              <i class="fa fa-exclamation-triangle"></i>
              <p><strong>Payment due: {{ item.due_date }}</strong></p>
            </div>
            <div class="col-myy text-right">
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- @todo Uncomment once we need pager -->
    <!--<div class="pager-wrapper">
      <div>
        <div class="pager-controls row">
          <div class="col-myy">
            <a href="#" class="btn btn-primary disabled">|&lt;</a>
            <a href="#" class="btn btn-primary disabled">&lt;</a>
          </div>
          <div class="col-myy text-center">
            <div class="page-of">Page 1 of 5</div>
          </div>
          <div class="col-myy text-right">
            <a href="#" class="btn btn-primary">&gt;</a>
            <a href="#" class="btn btn-primary">&gt;|</a>
          </div>
        </div>
      </div>
    </div>-->
  </section>
</template>

<script>
  export default {
    data () {
      return {
        loading: true,
        data: {},
        baseUrl: '/',
        type: 'A',
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
                url = component.baseUrl + 'myy-model/data/orders/' + ids + '/' + start + '/' + end;

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
              component.mapUserColors();
              jQuery('.myy-sub-header .count span').text(data.length);
            });
          } else {
            // Redirect user to login page.
            window.location.pathname = component.baseUrl + 'myy-model/login'
          }
        });
      },
      mapUserColors: function () {
        var mapUserColors = {},
            counter = 1;
        for (var i in this.data) {
          mapUserColors[this.data[i].name] = 1;
        }
        for (var j in mapUserColors) {
          mapUserColors[j] = counter++;
        }
        this.userColors = mapUserColors;
      },
      getUserColor: function (username) {
        for (var i in this.userColors) {
          if (i == username) {
            return this.userColors[i];
          }
        }
      }
    },
    mounted: function() {
      let component = this;

      component.baseUrl = window.drupalSettings.path.baseUrl;
      component.uid = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.uid : '';

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
  .myy-orders-receipts-results {
    .top-line {
      line-height: 38px;
      margin-bottom: 20px;
      display: none;
      @media (min-width: 992px) {
        display: flex;
      }
    }
    .content {
      border: 1px solid #636466;
      margin: 0;
      @media (min-width: 992px) {
        padding: 5px;
      }
      > .col-myy {
        padding-left: 10px;
        padding-right: 10px;
      }
    }
    .due-now {
      margin-top: 20px;
      line-height: 21px;
      .fa-exclamation-triangle {
        line-height: 52px;
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
    .result_row {
      border-bottom: 1px solid #636466;
      padding: 5px 70px 10px 50px;
      position: relative;
      @media (min-width: 576px) {
        padding: 5px 70px 10px 60px;
      }
      .user-icon-wrapper {
        position: absolute;
        left: 0;
        top: 10px;
      }
      .title-wrapper {
        margin-bottom: 10px;
      }
      .description-wrapper {
        margin-bottom: 10px;
      }
      .total-wrapper {
        @media (min-width: 576px) {
          text-align: right;
        }
      }
      .status-wrapper {
        position: absolute;
        right: 0;
        top: 10px;
      }
      .type {
        color: #0060AF;
      }
      &:last-child {
        border-bottom: none;
      }
    }
    .pager-wrapper {
      padding: 10px 0;
      .btn {
        background: none;
        border: none;
        color: #231F20;
        font-size: 14px;
        &.disabled {
          opacity: 0.5;
        }
      }
    }
  }
</style>

