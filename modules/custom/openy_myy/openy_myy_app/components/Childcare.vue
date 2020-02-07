<template>
  <section class="myy-childcare">
    <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
    <div v-else>
      <div class="row title-area">
        <div class="col-myy-6">
          <h2>Childcare</h2>
        </div>
        <div class="col-myy-6 text-right">
         <a :href="childcare_purchase_link_url" class="btn btn-primary text-uppercase">{{ childcare_purchase_link_title }} <i class="fa fa-external-link-square"></i></a>
        </div>
      </div>
      <div class="row headline" v-if="data">
        <div class="col-myy-5">
          <h4>Upcoming <span class="d-myy-none d-myy-lg-inline">events</span></h4>
        </div>
        <div class="col-myy-7 text-right">
          <a href="#" class="view_all">View all</a> | <a href="#" class="purchases">Purchases</a>
        </div>
      </div>
      <div v-else>
        <p>You have not purchased any childcare sessions.</p>
      </div>

      <div class="row content" v-if="data">
        <div v-for="(item, index) in data" v-bind:key="index">
          <div class="col-myy-sm-12" v-for="(item2, index2) in item" v-bind:key="index2">
          <div class="event_row">
            <div class="event_head row">
              <div class="col-myy-sm-1 user-icon-wrapper">
                <span :class="'rounded_letter small ' + getUserColor(item2.program_data.family_member_name)" v-if="getUserColor(item2.program_data.family_member_name)">{{ item2.program_data.family_member }}</span>
              </div>
              <div class="col-myy-sm-5">
                <div class="program_name"><strong>{{ item2.program_data.program_name }}</strong></div>
               {{ item2.program_data.branch }}
              </div>
              <div class="col-myy-sm-4">
                <div class="date"><strong>{{ item2.program_data.start_date }}-{{ item2.program_data.end_date }}</strong></div>
              </div>
            </div>
            <div v-for="(week, weekday_id) in item2.weeks" v-bind:key="weekday_id">
              <div class="weekday-number">{{ weekday_id }}</div>
              <div v-for="(order, index3) in week" v-bind:key="index3">
                <div v-if="order.scheduled == 'Y'" class="event_item row">
                  <div class="col-myy-2 col-myy-lg-1 no-padding-left no-padding-right text-center">
                    <i class="fa fa-calendar-check-o"></i>
                  </div>
                  <div class="col-myy-7 col-myy-lg-5 no-padding-left">
                    <span class="date">{{ order.usr_day }}, {{ order.order_date }}</span>
                    <div class="duration d-myy-lg-none">{{ order.type }}</div>
                  </div>
                  <div class="col-myy-3 col-myy-lg-3 d-myy-none d-myy-lg-block">
                    <span class="duration">{{ order.type }}</span>
                  </div>
                  <div class="col-myy-3 col-myy-lg-3 text-right no-padding-right">
                    <a class="myy-modal__modal--myy-cancel-link cancel" role="button" href="#" v-on:click="populatePopupCancel(
                      week,
                      order,
                      item2.program_data.program_name,
                      item2.program_data.family_member,
                      item2.program_data.family_member_name,
                      item2.program_data.branch
                    )" data-toggle="modal" data-target=".myy-modal__modal--myy-cancel"><strong>Cancel</strong></a>
                  </div>
                </div>
              </div>
              <div v-if="!ifAllSessionsScheduled(week)" class="event_empty_week">
                empty week (all items are unscheduled)
              </div>
              <div class="event_add_item row">
                <div class="col-myy-sm-12">
                  <i class="fa blue fa-calendar-plus-o"></i>
                  <a class="myy-modal__modal--myy-additem-link additem" role="button" href="#" v-on:click="populatePopupAdditem(
                    week,
                    item2.program_data.program_name,
                    item2.program_data.family_member,
                    item2.program_data.family_member_name,
                    item2.program_data.start_date,
                    item2.program_data.end_date,
                    item2.program_data.branch
                    )" data-toggle="modal" data-target=".myy-modal__modal--myy-additem"><strong>Add Item</strong></a>
                </div>
              </div>
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
                    Cancelation of all days of <strong>{{ cancelPopup.content.program_name }}</strong> cannot be completed online.
                    Please contact our Customer Service Center at 612-230-9622 for assistance with your cancelation.
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
                      <span :class="'rounded_letter small ' + getUserColor(cancelPopup.info.family_member_name)" v-if="getUserColor(cancelPopup.info.family_member_name)">{{ cancelPopup.info.family_member }}</span>
                    </div>
                    <div class="col-myy-sm-10">
                      <p class="program_name"><strong>{{ cancelPopup.info.program_name }}</strong>
                      <p class="date"><strong>{{ cancelPopup.content.order_date }}</strong></p>
                      <p class="type">{{ cancelPopup.content.type }}</p>
                      <p class="location">{{ cancelPopup.info.branch }}</p>
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
                    <div class="col-myy-2">
                      <span :class="'rounded_letter small '+ getUserColor(addItemPopup.info.family_member_name)" v-if="getUserColor(addItemPopup.info.family_member_name)">{{ addItemPopup.info.family_member }}</span>
                    </div>
                    <div class="col-myy-10">
                      <div class="program_name" v-if="addItemPopup.content.length > 0"><strong>{{ addItemPopup.info.program_name }}</strong></div>
                      {{ addItemPopup.info.branch }}
                      <div class="date"><strong>{{ addItemPopup.info.start_date }}-{{ addItemPopup.info.end_date }}</strong></div>
                    </div>
                  </div>
                  <div v-for="(item, index) in addItemPopup.content" v-bind:key="index" class="event_item row">
                    <div class="col-myy-2 no-padding-left text-center">
                      <input type="checkbox" v-model="addItemChecked" :value="item.date + '+' + item.type" :id="'checkbox-' + item.date + '+' + item.type" />
                    </div>
                    <div class="col-myy-10 no-padding-left">
                      <div class="date">{{ item.usr_day }}, {{ item.order_date }}</div>
                      <div class="duration">{{ item.type }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-myy-sm-12 actions">
                <button type="button" class="btn btn-primary close-action" data-dismiss="modal" aria-label="Close">Cancel</button>
                <button type="button" class="btn btn-primary text-uppercase additem-action" :disabled="addItemPopup.disableScheduleButton" data-dismiss="modal" @click="scheduleItems(addItemPopup.content)">Schedule</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!--modal > cancel ok -->
    <div class="modal fade myy-modal__modal myy-modal__modal--myy-cancel-ok" tabindex="-1" role="dialog" aria-labelledby="myy-modal__modal--myy-cancel-ok">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="myy-modal__modal--header">
            <h3>Message</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times" aria-hidden="true"></i></button>
          </div>

          <div class="myy-modal__modal--body">
            <div class="col-myy-sm-12">
              <p class="text-center">
                You've successfully canceled a session.
              </p>
            </div>
            <div class="col-myy-sm-12 actions text-center">
              <button type="button" class="btn btn-primary close-action" data-dismiss="modal" aria-label="Ok">Ok</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!--modal > cancel fail -->
    <div class="modal fade myy-modal__modal myy-modal__modal--myy-cancel-fail" tabindex="-1" role="dialog" aria-labelledby="myy-modal__modal--myy-cancel-fail">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="myy-modal__modal--header">
            <h3>Error</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times" aria-hidden="true"></i></button>
          </div>

          <div class="myy-modal__modal--body">
            <div class="col-myy-sm-12">
              <p class="text-center">
                Something went wrong.
              </p>
            </div>
            <div class="col-myy-sm-12 actions text-center">
              <button type="button" class="btn btn-primary close-action" data-dismiss="modal" aria-label="Ok">Ok</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!--modal > add item ok -->
    <div class="modal fade myy-modal__modal myy-modal__modal--myy-add-item-ok" tabindex="-1" role="dialog" aria-labelledby="myy-modal__modal--myy-add-item-ok">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="myy-modal__modal--header">
            <h3>Message</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times" aria-hidden="true"></i></button>
          </div>

          <div class="myy-modal__modal--body">
            <div class="col-myy-sm-12">
              <p class="text-center">
                You've successfully updated the schedule.
              </p>
            </div>
            <div class="col-myy-sm-12 actions text-center">
              <button type="button" class="btn btn-primary close-action" data-dismiss="modal" aria-label="Ok">Ok</button>
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
          info: {},
          content: {}
        },
        addItemPopup: {
          content: {},
          info: {},
          disableScheduleButton: false
        },
        addItemChecked: [],
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
            var url = component.baseUrl + 'myy-model/data/childcare/scheduled';

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
          component.runAjaxRequest();
          if (typeof data.status !== 'undefined') {
            if (data.status == 'ok') {
              // Show modal window with ok message.
              jQuery('.myy-modal__modal--myy-cancel-ok').modal();
            }
            if (data.status == 'fail') {
              // Show modal window with fail message.
              jQuery('.myy-modal__modal--myy-cancel-fail').modal();
            }
          }
        });
      },
      scheduleItems: function(week) {
        let component = this;

        var newScheduled = {};
        for (var i in week) {
          newScheduled[week[i].date + '+' + week[i].type] = 'N';
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
          component.runAjaxRequest();
          if (typeof data[0].error !== 'undefined') {
            // Show modal window with fail message.
            jQuery('.myy-modal__modal--myy-cancel-fail').modal();
          }
          else {
            // Show modal window with ok message.
            jQuery('.myy-modal__modal--myy-add-item-ok').modal();
          }
        });
      },
      populatePopupCancel: function(
        week,
        order,
        program_name,
        family_member,
        family_member_name,
        branch
      ) {
        var numberOfScheduled = 0;
        this.cancelPopup.denyCancel = false;
        // Check if there only 1 item so user doesn't allow to cancel it.
        for (var i in week) {
          if (week[i].scheduled == 'Y') {
            numberOfScheduled++;
          }
        }
        if (numberOfScheduled == 1) {
          this.cancelPopup.denyCancel = true;
        }
        this.cancelPopup.content = order;
        this.cancelPopup.info = {
          program_name: program_name,
          family_member: family_member,
          family_member_name: family_member_name,
          branch: branch,
        };
      },
      populatePopupAdditem: function(
        week,
        program_name,
        family_member,
        family_member_name,
        start_date,
        end_date,
        branch
      ) {
        this.addItemChecked = [];
        for (var i in week) {
          if (week[i].scheduled == 'Y') {
            this.addItemChecked.push(week[i].date + '+' + week[i].type);
          }
        }
        if (this.addItemChecked.length === 0) {
          this.addItemPopup.disableScheduleButton = true;
        }
        this.addItemPopup.content = week;
        this.addItemPopup.info = {
          program_name: program_name,
          family_member: family_member,
          family_member_name: family_member_name,
          start_date: start_date,
          end_date: end_date,
          branch: branch,
        };
      },
      getWeekNumber: function (weeks, weekday_id) {
        var count = 1;
        for (var i in weeks) {
          if (i === weekday_id) {
            return count;
          }
          count++;
        }
      },
      ifAllSessionsScheduled: function (week) {
        var scheduled = false;
        for (var i in week) {
          // If at least 1 item is scheduled mark week as scheduled.
          if (week[i].scheduled == 'Y') {
            scheduled = true;
          }
        }
        return scheduled;
      },
      getUserColor: function (username) {
        for (var i in this.householdColors) {
          if (i == username) {
            return this.householdColors[i];
          }
        }
      }
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

      component.baseUrl = window.drupalSettings.path.baseUrl;
      component.childcare_purchase_link_title = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.childcare_purchase_link_title : '';
      component.childcare_purchase_link_url = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.childcare_purchase_link_url : '';
      component.householdColors = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.householdColors : [];

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
      font-size: 24px;
      line-height: 36px;
      margin: 0;
      color: #636466;
      @media (min-width: 992px) {
        font-size: 36px;
        line-height: 48px;
      }
    }
    .title-area {
      margin-bottom: 20px;
      @media (min-width: 992px) {
        margin-bottom: 40px;
      }
      .btn {
        background-color: #92278F;
        border-color: #92278F;
        border-radius: 5px;
        color: #fff;
        font-family: "Cachet Medium", sans-serif;
        font-size: 14px;
        line-height: 27px;
        padding: 6px 15px;
        @media (min-width: 992px) {
          font-size: 18px;
          line-height: 38px;
          padding: 6px 25px;
        }
        &:hover {
          color: #fff;
        }
      }
    }
    .headline {
      padding: 15px 0;
      margin: 0;
      border: 1px solid #636466;
      @media (min-width: 992px) {
        padding: 20px 5px;
      }
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
      padding: 15px 0px;
      @media (min-width: 992px) {
        padding: 20px 5px;
      }
      .row {
        margin: 0;
      }
    }
    .event_row {
      border: 1px solid #F2F2F2;
      margin-bottom: 20px;
      .event_head {
        background-color: #F2F2F2;
        padding: 10px 5px 10px 55px;
        line-height: 21px;
        position: relative;
        @media (min-width: 992px) {
          padding: 20px 5px;
        }
        .user-icon-wrapper {
          position: absolute;
          left: 0;
          top: 10px;
          @media (min-width: 992px) {
            position: static;
            left: 0;
            top: 0;
          }
        }
        .weekdays {
          font-size: 12px;
        }
      }
      .event_week {
        padding: 10px 0;
        margin: 10px 20px;
      }
      .event_item {
        padding: 10px 0;
        border-bottom: 1px solid #636466;
        margin: 0 15px;
        @media (min-width: 992px) {
          padding: 20px 0;
          margin: 0 20px;
        }
        .date {
          margin-left: 0px;
          @media (min-width: 992px) {
            margin-left: 5px;
          }
        }
      }
      .weekday-number {
        background-color: #F2F2F2;
        padding: 10px 15px;
        margin-bottom: 10px;
        @media (min-width: 992px) {
          padding: 10px 30px;
        }
      }
      .event_add_item {
        padding: 15px 0;
        margin: 0 15px 20px;
        @media (min-width: 992px) {
          padding: 20px 0;
          margin: 0 20px 20px 20px;
        }
      }
      .event_empty_week {
        margin: 20px 20px 5px 30px;
      }
      .fa {
        font-size: 18px;
      }
    }
  }
  .myy-modal__modal--myy-cancel {
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
        margin: 0;
        &:last-child {
          border-bottom: none;
        }
        .date {
          margin-left: 0;
        }
      }
    }
  }

  .myy-modal__modal {
    .myy-modal__modal--body {
      .text-center {
        margin: 10px 0;
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
        &:hover {
          color: #FFF;
        }
      }

      .cancel-action,
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

