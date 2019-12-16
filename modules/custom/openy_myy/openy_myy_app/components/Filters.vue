<template>
  <section class="myy-orders-filters">
    <div class="myy-filters">
      <h3>FILTERS</h3>
      <div class="myy-filters__wrapper">
        <div class="form-item form-item-date">
          <label for="edit-start-date">Start Date</label>
          <input class="myy-datepicker form-text form-control text" data-drupal-selector="edit-start-date" data-disable-refocus="true" type="text" id="edit-start-date" v-model="start_date" name="start_date" value="" size="60" maxlength="128" />
          <i class="fa fa-calendar"></i>
        </div>
        <div class="form-item form-item-date">
          <label for="edit-end-date">End Date</label>
          <input class="myy-datepicker form-text form-control text" data-drupal-selector="edit-end-date" data-disable-refocus="true" type="text" id="edit-end-date" v-model="end_date" name="end_date" value="" size="60" maxlength="128" />
          <i class="fa fa-calendar"></i>
        </div>
      </div>
    </div>
  </section>
</template>

<style lang="scss">
  .myy-orders-filters {
    h3 {
      color: #636466;
      font-size: 18px;
      line-height: 18px;
      margin: 0 0 20px;
    }
  }
</style>

