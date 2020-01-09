<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-visits-results">
    <div class="row top-line">
      <div class="col-myy-6">
        <strong>{{ data.length }} results</strong>
      </div>
      <div class="col-myy-6">
        <select class="form-control form-select">
          <option value="date_ASC">Sort by date (ascending)</option>
          <option value="date_DESC">Sort by date (descending)</option>
        </select>
      </div>
    </div>
    <div class="row content">
      <div class="col-myy-12">
        <div v-for="(item, index) in data" v-bind:key="index" class="item-row row">
          <div class="col-myy-sm-1 no-padding-left">
            <span :class="'rounded_letter small color-' + getUserColor(item.USR_LAST_FIRST_NAME)" v-if="getUserColor(item.USR_LAST_FIRST_NAME)">{{ item.USR_LAST_FIRST_NAME.charAt(0) }}</span>
          </div>
          <div class="col-myy-sm-4">
            <span class="user_name">{{ item.USR_LAST_FIRST_NAME }}</span>
          </div>
          <div class="col-myy-sm-4">
            <span class="date">{{ item.CUSTOM_USR_DATE }}</span><br/>
            <span class="duration">{{ item.CUSTOM_USR_TIME }}</span>
          </div>
          <div class="col-myy-sm-3">
            <span class="branch">{{ item.USR_BRANCH }}</span>
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
        data: {},
        userColors: []
      }
    },
    methods: {
      runAjaxRequest: function() {
        var component = this,
            start = typeof this.$route.query.start == 'undefined' ? '2018-01-01' : this.$route.query.start,
            end = typeof this.$route.query.end == 'undefined' ? '2019-12-12' : this.$route.query.end,
            ids = component.uid,
            url = component.baseUrl + 'myy-model/data/profile/visits-details/' + ids + '/' + start + '/' + end;

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
      },
      mapUserColors: function () {
        var mapUserColors = {},
            counter = 1;
        for (var i in this.data) {
          mapUserColors[this.data[i].USR_LAST_FIRST_NAME] = 1;
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

      if (typeof window.drupalSettings === 'undefined') {
        var drupalSettings = {
          path: {
            baseUrl: 'http://openy-demo.docksal/',
          }
        };
        window.drupalSettings = drupalSettings;
      }
      component.baseUrl = window.drupalSettings.path.baseUrl;
      component.uid = typeof window.drupalSettings.myy !== 'undefined' ? window.drupalSettings.myy.uid : '';

      component.runAjaxRequest();
    },
    watch: {
      '$route': function() {
        if (typeof this.$route.query.start != 'undefined' && typeof this.$route.query.end != 'undefined') {
          this.runAjaxRequest();
        }
      }
    }
  }
</script>

<style lang="scss">
  .myy-visits-results {
    line-height: 17px;
    margin-bottom: 40px;
    .top-line {
      margin-bottom: 20px;
      line-height: 40px;
      display: none;
      @media (min-width: 992px) {
        display: flex;
      }
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
      @media (min-width: 992px) {
        padding: 0 20px;
      }
      .row {
        padding: 20px 0;
        margin: 0;
      }
    }
    .item-row.row {
      border-bottom: 1px solid #636466;
      position: relative;
      padding: 5px 0 10px 40px;
      @media (min-width: 576px) {
        padding: 20px 0;
      }
      .no-padding-left {
        position: absolute;
        left: 0;
        top: 10px;
        @media (min-width: 576px) {
          position: static;
          left: 0;
          top: 0;
        }
      }
      &:last-child {
        border-bottom: none;
      }
      .user_name {
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

