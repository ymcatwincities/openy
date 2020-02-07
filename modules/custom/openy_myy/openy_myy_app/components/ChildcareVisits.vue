<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-childcare-visits">
    <div class="row results-sorting">
      <div class="col-myy-sm-7">
        <span class="count">{{ data.length }} results</span>
      </div>
      <div class="col-myy-sm-4">
        <select class="form-control form-select">
          <option value="date_ASC">Sort by date (ascending)</option>
          <option value="date_DESC">Sort by date (descending)</option>
        </select>
      </div>
    </div>
    <div class="row content">
      <div class="col-myy-sm-12">
          <div v-for="(item, index) in data" v-bind:key="index" class="item-row row">
            <div class="col-myy-sm-1 no-padding-left">
              <span class="rounded_letter small black">X</span>
            </div>
            <div class="col-myy-sm-3">
              <span class="program_name">{{ item.program_name }}</span>
            </div>
            <div class="col-myy-sm-3">
              <span class="date">{{ item.usr_day }}, {{ item.order_date }}</span><br/>
              <span class="duration">{{ item.type }}</span>
            </div>
            <div class="col-myy-sm-3">
              <span class="branch">{{ item.branch_id }}</span>
            </div>
            <div class="col-myy-sm-2 no-padding-right text-right">
              <span v-if="item.scheduled == 'Y'" class="status">SCHEDULED</span>
              <span v-if="item.attended == 'Y'" class="status">Attended</span>
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
            var query = [],
            url = window.drupalSettings.path.baseUrl + 'myy-model/data/childcare/scheduled';

          if (this.$route.query.start_date !== 'undefined') {
            query.push('start_date=' + this.$route.query.start_date);
          }
          if (this.$route.query.end_date !== 'undefined') {
            query.push('end_date=' + this.$route.query.end_date);
          }

          if (query.length > 0) {
            url += '?' + query.join('&');
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
    },
    watch: {
      '$route': function () {
        if (typeof this.$route.query.start_date !== 'undefined') {
          this.runAjaxRequest();
        }
      }
    }
  }
</script>

<style lang="scss">
  .myy-childcare-visits {
    line-height: 17px;
    margin-bottom: 40px;
    .results-sorting {
      margin-bottom: 20px;
      line-height: 40px;
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
      padding: 0 20px;
      .row {
        padding: 20px 0;
        margin: 0;
      }
    }
    .item-row {
      border-bottom: 1px solid #636466;
      &:last-child {
        border-bottom: none;
      }
      .program_name {
        color: #0060AF;
        font-weight: bold;
        line-height: 21px;
      }
      .date {
        font-weight: bold;
        line-height: 21px;
      }
      .duration {
        line-height: 21px;
      }
      .branch {
        line-height: 21px;
      }
      .status {
        font-weight: bold;
        line-height: 21px;
      }
    }
  }
</style>

