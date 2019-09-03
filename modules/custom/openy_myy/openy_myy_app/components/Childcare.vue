<template>
  <section class="myy-childcare">
    <div class="row title-area">
      <div class="col">
        <h2>Childcare</h2>
      </div>
      <div class="col text-right">
       <a class="btn btn-primary">PURCHASE CARE <i class="fa fa-external-link-square"></i></a>
      </div>
    </div>
    <div class="row headline">
      <div class="col">
        <h3>Upcoming events</h3>
      </div>
      <div class="col text-right">
        <a href="#" class="view_all">View all</a> | <a href="#" class="purchases">Purchases</a>
      </div>
    </div>
    <div class="row content">
      <div class="col">
        <div class="event_row">
          <div class="event_head row">
            <div class="col-sm-1">
              <span class="rounded_letter small blue">J</span>
            </div>
            <div class="col-sm-5">
              <div class="program_name"><strong>Program Name</strong></div>
              YMCA at Gaviidae - DT Minneapolis
            </div>
            <div class="col-sm-4">
              <div class="date"><strong>Dec 12-Dec 19</strong></div>
              <span class="weekdays">Mon - Fri</span>
            </div>
            <div class="col-sm-2 text-right">
              <a href="#" class="cancel"><strong>Cancel all</strong></a>
            </div>
          </div>
          <div class="event_item row">
            <div class="col-sm-1 no-padding-left text-center">
              <i class="fa fa-calendar-check-o"></i>
            </div>
            <div class="col-sm-5 no-padding-left">
              <span class="date">Wed, 12/15/2029</span>
            </div>
            <div class="col-sm-4">
              <span class="duration">All Day</span>
            </div>
            <div class="col-sm-2 text-right no-padding-right">
              <a href="#" class="cancel"><strong>Cancel</strong></a>
            </div>
          </div>
          <div class="event_item row">
            <div class="col-sm-1 no-padding-left text-center">
              <i class="fa fa-calendar-check-o"></i>
            </div>
            <div class="col-sm-5 no-padding-left">
              <span class="date">Wed, 12/16/2029</span>
            </div>
            <div class="col-sm-4">
              <span class="duration">All Day</span>
            </div>
            <div class="col-sm-2 text-right no-padding-right">
              <a href="#" class="cancel"><strong>Cancel</strong></a>
            </div>
          </div>
          <div class="event_add_item row">
            <div class="col">
              <i class="fa blue fa-calendar-plus-o"></i> <a href="#"><strong>Add Item</strong></a>
            </div>
          </div>
        </div>
        <div class="event_row">
          <div class="event_head row">
            <div class="col-sm-1">
              <span class="rounded_letter small purple">D</span>
            </div>
            <div class="col-sm-5">
              <div class="program_name"><strong>Program Name</strong></div>
              YMCA at Gaviidae - DT Minneapolis
            </div>
            <div class="col-sm-4">
              <div class="date"><strong>Dec 12-Dec 19</strong></div>
              <span class="weekdays">Mon - Fri</span>
            </div>
            <div class="col-sm-2 text-right">
              <a href="#" class="cancel"><strong>Cancel all</strong></a>
            </div>
          </div>
          <div class="event_item row">
            <div class="col-sm-1 no-padding-left text-center">
              <i class="fa fa-calendar-check-o"></i>
            </div>
            <div class="col-sm-5 no-padding-left">
              <span class="date">Wed, 12/15/2029</span>
            </div>
            <div class="col-sm-4">
              <span class="duration">All Day</span>
            </div>
            <div class="col-sm-2 text-right no-padding-right">
              <a href="#" class="cancel"><strong>Cancel</strong></a>
            </div>
          </div>
          <div class="event_item row">
            <div class="col-sm-1 no-padding-left text-center">
              <i class="fa fa-calendar-check-o"></i>
            </div>
            <div class="col-sm-5 no-padding-left">
              <span class="date">Thu, 12/16/2029</span>
            </div>
            <div class="col-sm-4">
              <span class="duration">All Day</span>
            </div>
            <div class="col-sm-2 text-right no-padding-right">
              <a href="#" class="cancel"><strong>Cancel</strong></a>
            </div>
          </div>
          <div class="event_add_item row">
            <div class="col">
              <i class="fa blue fa-calendar-plus-o"></i> <a href="#"><strong>Add Item</strong></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

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
    h3 {
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
</style>

