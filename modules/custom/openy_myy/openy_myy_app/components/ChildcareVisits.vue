<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-childcare-visits">
    <div class="row content">
      <div class="col">
        <div class="event_row">
          <div class="event_head row">
            <div class="col-sm-1">
              <span class="rounded_letter small blue">J</span>
            </div>
            <div class="col-sm-5">
              <div class="program_name" v-if="data.length > 0"><strong>{{ data[0].program_name }}</strong></div>
              {{ data[0].branch_id }}
            </div>
            <div class="col-sm-4">
              <div class="date"><strong>Dec 12-Dec 19</strong></div>
              <span class="weekdays">Mon - Fri</span>
            </div>
            <div class="col-sm-2 text-right">
              <a href="#" class="cancel"><strong>Cancel all</strong></a>
            </div>
          </div>
          <div v-for="(item, index) in data" v-bind:key="index" class="event_item row">
            <div class="col-sm-1 no-padding-left text-center">
              <i class="fa fa-calendar-check-o"></i>
            </div>
            <div class="col-sm-5 no-padding-left">
              <span class="date">{{ item.usr_day }}, {{ item.order_date }}</span>
            </div>
            <div class="col-sm-4">
              <span class="duration">{{ item.type }}</span>
            </div>
            <div class="col-sm-2 text-right no-padding-right">
              <a href="#" class="cancel"><strong>Cancel</strong></a>
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
        data: {}
      }
    },
    methods: {
      runAjaxRequest: function() {
        let component = this;

        // @todo: find solution how to configure dev environment.
        if (typeof drupalSettings === 'undefined') {
          var drupalSettings = {
            path: {
              baseUrl: 'http://openy-demo.docksal/'
            }
          };
        }
        let url = drupalSettings.path.baseUrl + 'myy/data/childcare/scheduled';

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
      },
    },
    mounted: function() {
      let component = this;
      component.runAjaxRequest();
    }
  }
</script>

<style lang="scss">
  .myy-childcare-visits {
    line-height: 17px;
    margin-bottom: 40px;

    .content {
      border: 1px solid #636466;
      margin: 0;
      padding: 20px 5px;
      .row {
        margin: 0;
      }
    }
    .event_row {
      border: 1px solid #F2F2F2;
      margin-bottom: 20px;
      .event_head {
        background-color: #F2F2F2;
        padding: 20px 5px;
        line-height: 21px;
        .weekdays {
          font-size: 12px;
        }
      }
      .event_item {
        padding: 20px 0;
        border-bottom: 1px solid #636466;
        margin: 0 20px;
        .date {
          margin-left: 5px;
        }
      }
      .event_add_item {
        padding: 20px 0;
        margin: 0 20px;
      }
      .fa {
        font-size: 18px;
      }
    }
  }
</style>

