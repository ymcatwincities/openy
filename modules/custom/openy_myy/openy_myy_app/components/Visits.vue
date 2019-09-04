<template>
  <section class="myy-visits">
    <div class="row headline">
      <div class="col">
        <h3>Visits</h3>
      </div>
      <div class="col text-right">
        <span class="date">Dec 2019</span> | <a href="#" class="view_all">View all</a>
      </div>
    </div>
    <div class="row content">
      <div class="col">
        <div class="row">
          <div class="col-sm-3">
            <span class="rounded_letter green"> J</span>
          </div>
          <div class="col-sm-3">
            <span class="square_number">15</span>
          </div>
          <div class="col-sm-6">
            <div class="name">John Doe</div>
            <div class="unique_visits"> 15 unique visits*</div>
            <div class="total_visits">17 total visits</div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="row">
          <div class="col-sm-3">
            <span class="rounded_letter red"> J</span>
          </div>
          <div class="col-sm-3">
            <span class="square_number">11</span>
          </div>
          <div class="col-sm-6">
            <div class="name">Jane Doe</div>
            <div class="unique_visits"> 11 unique visits*</div>
            <div class="total_visits">11 total visits</div>
          </div>
        </div>
      </div>
    </div>
    <div class="row description">
      <i>*Unique visits are the first visit on any given day. Only unique visits apply to health incentive goals.</i>
    </div>
  </section>
</template>

<style lang="scss">
  .myy-visits {
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
        margin-top: 10px;
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
    }
    .description {
      margin: 10px 0;
      i {
        font-size: 12px;
        line-height: 18px;
      }
    }
  }
</style>

