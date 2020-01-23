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
              <i class="fa blue fa-calendar-plus-o"></i>
              <a class="myy-modal__modal--myy-additem-link additem" role="button" href="#" v-on:click="populatePopupAdditem" data-toggle="modal" data-target=".myy-modal__modal--myy-additem"><strong>Add Item</strong></a>
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
                <button type="button" class="btn btn-primary text-uppercase cancel-action" data-dismiss="modal" @click="cancel(cancelPopup.content.date, cancelPopup.content.type)">YES, CANCEL</button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!--modal > add item -->
    <div class="modal fade myy-modal__modal myy-modal__modal--myy-additem" tabindex="-1" role="dialog" aria-labelledby="myy-modal__modal--myy-additem">
      <div class="modal-dialog" role="document">
        <div class="modal-content">

          <div class="myy-modal__modal--header">
            <h3>Add Item</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times" aria-hidden="true"></i></button>
          </div>

          <div v-if="addItemPopup.content.length > 0" class="myy-modal__modal--body">
            <div class="col-myy-sm-12">
              <div class="event_row">
                <div class="event_head row">
                  <div class="col-myy-sm-2">
                    <span class="rounded_letter small blue">J</span>
                  </div>
                  <div class="col-myy-sm-10">
                    <div class="program_name" v-if="addItemPopup.content.length > 0"><strong>{{ addItemPopup.content[0].program_name }}</strong></div>
                    {{ addItemPopup.content[0].branch_id }}
                    <div class="date"><strong>Dec 12-Dec 19</strong></div>
                    <span class="weekdays">Mon - Fri</span>
                  </div>
                </div>
                <div v-for="(item, index) in addItemPopup.content" v-bind:key="index" class="event_item row">
                  <div class="col-myy-sm-2 no-padding-left text-center">
                    <input type="checkbox" v-bind:checked="item.scheduled == 'Y'" v-model="addItemChecked" :value="item.date + '+' + item.type" :id="'checkbox-' + item.date + '+' + item.type" />
                  </div>
                  <div class="col-myy-sm-10 no-padding-left">
                    <div class="date">{{ item.usr_day }}, {{ item.order_date }}</div>
                    <div class="duration">{{ item.type }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-myy-sm-12 actions">
              <button type="button" class="btn btn-primary close-action" data-dismiss="modal" aria-label="Close">Cancel</button>
              <button type="button" class="btn btn-primary text-uppercase additem-action" :disabled="addItemPopup.disableScheduleButton" data-dismiss="modal" @click="scheduleItems()">Schedule</button>
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
        addItemPopup: {
          content: {},
          disableScheduleButton:false
        },
        addItemChecked: [],
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
      cancel: function(date, type) {
        let component = this;
        let url = component.baseUrl + 'myy-model/data/childcare/session-cancel/' + date+ '/' + type;

        component.loading = true;
        jQuery.ajax({
          url: url,
          xhrFields: {
            withCredentials: true
          }
        }).done(function(data) {
          if (typeof data.status !== 'undefined') {
            if (data.status == 'ok') {
              //@todo: show modal window with ok message.
            }
            if (data.status == 'fail') {
              //@todo: show modal window with error.
            }
          }
          component.loading = false;
          component.runAjaxRequest();
        });
      },
      scheduleItems: function() {
        let component = this;

        var newScheduled = {};
        for (var i in component.data) {
          newScheduled[component.data[i].date + '+' + component.data[i].type] = 'N';
        }
        for (var j in newScheduled) {
          for (var k in component.addItemChecked) {
            if (component.addItemChecked[k] == j) {
              newScheduled[j] = 'Y';
            }
          }
        }
        var dataToSend = [];
        for (var l in newScheduled) {
          dataToSend.push(l + '+' + newScheduled[l]);
        }
        let url = component.baseUrl + 'myy-model/data/childcare/session-add/' + dataToSend.join(',');

        component.loading = true;
        jQuery.ajax({
          url: url,
          xhrFields: {
            withCredentials: true
          }
        }).done(function(data) {
          if (typeof data[0].error !== 'undefined') {
            //@todo: show modal window with error.
          }
          component.loading = false;
          component.runAjaxRequest();
        });
      },
      populatePopupCancel: function(index) {
        // Check if there only 1 item so user doesn't allow to cancel it.
        if (this.data.length == 1) {
          this.cancelPopup.denyCancel = true;
        }
        this.cancelPopup.content = this.data[index];
      },
      populatePopupAdditem: function() {
        if (this.addItemChecked.length === 0) {
          for (var i in this.data) {
            if (this.data[i].scheduled == 'Y') {
              this.addItemChecked.push(this.data[i].date + '+' +this.data[i].type);
            }
          }
        }
        this.addItemPopup.content = this.data;
      },
    },
    watch: {
      addItemChecked: function(newValue, oldValue) {
        if (newValue.length === 0) {
          this.addItemPopup.disableScheduleButton = true;
        }
        else {
          this.addItemPopup.disableScheduleButton = false;
        }
      }
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
  .myy-modal__modal--myy-additem {
    .modal-dialog {
      max-width: 340px;
    }
    .col-myy-sm-12 {
      padding-left: 10px;
      padding-right: 10px;
    }
    .event_row {
      .event_head {
        padding: 10px 0;
        line-height: 18px;
        margin-right: -10px;
        margin-left: -10px;
      }
      .event_item {
        padding: 10px 0;
        margin: 10px 0;
        &:last-child {
          border-bottom: none;
        }
        .date {
          margin-left: 0;
        }
      }
    }
    .actions {
      background-color: #f2f2f2;
      margin-top: 15px;
      padding-top: 15px;
      padding-bottom: 15px;
      .close-action {
        border: 2px solid #0060AF;
        border-radius: 4px;
        color: #0060AF;
        font-family: "Cachet Medium", sans-serif;
        font-size: 18px;
        height: 50px;
        line-height: 50px;
        padding: 0 20px;
        text-transform: uppercase;
      }
      .additem-action {
        background-color: #92278F;
        border: 2px solid #92278F;
        border-radius: 4px;
        color: #FFF;
        float: right;
        font-family: "Cachet Medium", sans-serif;
        font-size: 18px;
        height: 50px;
        line-height: 50px;
        padding: 0 20px;
        text-transform: uppercase;
      }
    }
  }

</style>

