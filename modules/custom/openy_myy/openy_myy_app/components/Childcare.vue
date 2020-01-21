<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-childcare">
    <div class="row title-area">
      <div class="col-myy-sm-6">
        <h2>Childcare</h2>
      </div>
      <div class="col-myy-sm-6 text-right">
       <a :href="childcare_purchase_link_url" class="btn btn-primary text-uppercase">{{ childcare_purchase_link_title }} <i class="fa fa-external-link-square"></i></a>
      </div>
    </div>
    <div class="row headline">
      <div class="col-myy-sm-6">
        <h4>Upcoming events</h4>
      </div>
      <div class="col-myy-sm-6 text-right">
        <a href="#" class="view_all">View all</a> | <a href="#" class="purchases">Purchases</a>
      </div>
    </div>

    <div class="row content">
      <div class="col-myy-sm-12">
        <div class="event_row">
          <div class="event_head row">
            <div class="col-myy-sm-1">
              <span class="rounded_letter small blue">J</span>
            </div>
            <div class="col-myy-sm-5">
              <div class="program_name" v-if="data.length > 0"><strong>{{ data[0].program_name }}</strong></div>
              {{ data[0].branch_id }}
            </div>
            <div class="col-myy-sm-3">
              <div class="date"><strong>Dec 12-Dec 19</strong></div>
              <span class="weekdays">Mon - Fri</span>
            </div>
            <div class="col-myy-sm-3 text-right">
              <!--<a href="#" class="cancel"><strong>Cancel all</strong></a>-->
            </div>
          </div>
          <div v-for="(item, index) in data" v-bind:key="index" class="event_item row">
            <div class="col-myy-sm-1 no-padding-left text-center">
              <i class="fa fa-calendar-check-o"></i>
            </div>
            <div class="col-myy-sm-5 no-padding-left">
              <span class="date">{{ item.usr_day }}, {{ item.order_date }}</span>
            </div>
            <div class="col-myy-sm-3">
              <span class="duration">{{ item.type }}</span>
            </div>
            <div class="col-myy-sm-3 text-right no-padding-right">
              <a class="myy-modal__modal--myy-cancel-link cancel" role="button" href="#" v-on:click="populatePopupCancel(index)" data-toggle="modal" data-target=".myy-modal__modal--myy-cancel"><strong>Cancel</strong></a>
            </div>
          </div>
          <div class="event_add_item row">
            <div class="col-myy-sm-12">
              <i class="fa blue fa-calendar-plus-o"></i> <a href="#"><strong>Add Item</strong></a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!--modal > cancel -->
    <div class="modal fade myy-modal__modal myy-modal__modal--myy-cancel" tabindex="-1" role="dialog" aria-labelledby="myy-modal__modal--myy-cancel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">

          <div v-if="cancelPopup.denyCancel">
            <div class="myy-modal__modal--header">
              <h3>Cancel</h3>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times" aria-hidden="true"></i></button>
            </div>

            <div class="myy-modal__modal--body">
              <div class="col-myy-sm-12">
                <p class="text-center">
                  Cancellation of all days of <strong>{{ cancelPopup.content.program_name }}</strong> cannot be completed online.
                  Please contact our Customer Service Center at 612-230-9622 for assistance with your cancellation.
                </p>
              </div>
            </div>
          </div>

          <div v-else>
            <div class="myy-modal__modal--header">
              <h3>Confirm</h3>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times" aria-hidden="true"></i></button>
            </div>

            <div class="myy-modal__modal--body">
              <div class="col-myy-sm-12">
                <p class="text-center"> Are you sure you want to cancel the following session?</p>
                <div class="info row">
                  <div class="col-myy-sm-2">
                    <span class="rounded_letter small black">X</span>
                  </div>
                  <div class="col-myy-sm-10">
                    <p class="program_name"><strong>{{ cancelPopup.content.program_name }}</strong>
                    <p class="date"><strong>{{ cancelPopup.content.order_date }}</strong></p>
                    <p class="type">{{ cancelPopup.content.type }}</p>
                    <p class="location">{{ cancelPopup.content.branch_id }}</p>
                  </div>
                </div>
              </div>
              <div class="col-myy-sm-12 actions">
                <button type="button" class="btn btn-primary close-action" data-dismiss="modal" aria-label="Close">NO, KEEP IT</button>
                <button type="button" class="btn btn-primary text-uppercase cancel-action" data-dismiss="modal" @click="cancel(cancelPopup.content.order_number)">YES, CANCEL</button>
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
        data: {},
        baseUrl: '/',
        childcare_purchase_link_title: '',
        childcare_purchase_link_url: '',
        cancelPopup: {
          denyCancel: false,
          content: {}
        },
      }
    },
    methods: {
      runAjaxRequest: function() {
        let component = this;
        let url = component.baseUrl + 'myy-model/data/childcare/scheduled';

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
      cancel: function(order, date) {
        let component = this;
        let url = component.baseUrl + 'myy-model/data/childcare/session-cancel/' + order+ '/' + date;

        component.loading = true;
        jQuery.ajax({
          url: url,
          xhrFields: {
            withCredentials: true
          }
        }).done(function(data) {
          component.loading = false;
        });
      },
      populatePopupCancel: function(index) {
        // Check if there only 1 item so user doesn't allow to cancel it.
        if (this.data.length == 1) {
          this.cancelPopup.denyCancel = true;
        }
        this.cancelPopup.content = this.data[index];
      },
    },
    mounted: function() {
      let component = this;

      if (typeof window.drupalSettings === 'undefined') {
        var drupalSettings = {
          path: {
            baseUrl: 'http://openy-demo.docksal/'
          },
          myy: {
          childcare_purchase_link_title: 'Purchase Care',
          childcare_purchase_link_url: '/myy'
          }
        };
        window.drupalSettings = drupalSettings;
      }
      component.baseUrl = window.drupalSettings.path.baseUrl;
      component.childcare_purchase_link_title = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.childcare_purchase_link_title : '';
      component.childcare_purchase_link_url = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.childcare_purchase_link_url : '';

      component.runAjaxRequest();
    }
  }
</script>

<style lang="scss">
  .myy-childcare {
    line-height: 17px;
    margin-bottom: 40px;
    h2 {
      font-family: "Cachet Medium", sans-serif;
      font-size: 36px;
      line-height: 48px;
      margin: 0;
      color: #636466;
    }
    .title-area {
      margin-bottom: 40px;
      .btn {
        background-color: #92278F;
        border-color: #92278F;
        color: #fff;
        font-family: "Cachet Medium", sans-serif;
        font-size: 18px;
        line-height: 38px;
        padding: 6px 25px;
        &:hover {
          color: #fff;
        }
      }
    }
    .headline {
      padding: 20px 5px;
      margin: 0;
      border: 1px solid #636466;
    }
    h4 {
      color: #636466;
      font-size: 14px;
      font-weight: bold;
      line-height: 17px;
      margin: 0;
      text-transform: uppercase;
    }
    .view_all,
    .purchases {
      font-weight: bold;
    }
    .content {
      border-left: 1px solid #636466;
      border-right: 1px solid #636466;
      border-bottom: 1px solid #636466;
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
  .myy-modal__modal--myy-cancel {
    .text-center {
      font-size: 12px;
      line-height: 18px;
      margin: 10px 0;
    }
    .info {
      background-color: #f2f2f2;
      margin: 0;
      padding: 15px 0 0;
      font-size: 12px;
      line-height: 18px;
      .program_name {
      }
      .date {
        margin: 0;
      }
    }
    .actions {
      background-color: #f2f2f2;
      margin-top: 15px;
      padding-top: 15px;
      padding-bottom: 15px;
      .close-action {
        border: 2px solid #0060AF;
        color: #0060AF;
        font-family: "Cachet Medium", sans-serif;
        font-size: 18px;
        height: 50px;
        line-height: 50px;
        padding: 0 20px;
      }
      .cancel-action {
        background-color: #92278F;
        border: 2px solid #92278F;
        color: #FFF;
        float: right;
        font-family: "Cachet Medium", sans-serif;
        font-size: 18px;
        height: 50px;
        line-height: 50px;
        padding: 0 20px;
      }
    }
  }
</style>

