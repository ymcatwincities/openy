<template>
  <section class="myy-guest-passes">
    <div class="row headline">
      <div class="col">
        <h3>Guest passes<sup>*</sup></h3>
      </div>
      <div class="col text-right">
        <a href="#" class="link"><strong>Guest pass policy</strong> <i class="fa fa-external-link-square"></i></a>
      </div>
    </div>
    <div class="row content">
      <div class="col">
        <span class="square_number small">3</span> <strong>Available</strong>
      </div>
      <div class="col">
        <span class="square_number small">1</span> <strong>Used</strong>
      </div>
    </div>
    <div class="row description">
      <i><sup>*</sup>Guest pass balance will renew on MM/DD/YYYY.</i>
    </div>
  </section>
</template>

<style lang="scss">
  .myy-guest-passes {
    margin-bottom: 40px;
    line-height: 17px;
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
    .content {
      border-left: 1px solid #636466;
      border-right: 1px solid #636466;
      border-bottom: 1px solid #636466;
      margin: 0;
      padding: 20px 5px;
      strong {
        display: inline-block;
        vertical-align: top;
        line-height: 60px;
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

