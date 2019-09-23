<template>
  <div v-if="loading" id="nuxt-loading" aria-live="polite" role="status"><div>Loading...</div></div>
  <section v-else class="myy-household">
    <div class="row headline">
      <div class="col">
        <h3>Household</h3>
      </div>
    </div>
    <div class="row content">
      <div class="col">
        <div class="row">
          <div v-for="(item, index) in data.household" v-bind:key="index" class="col-md-3">
            <span :class="'rounded_letter color-' + index">{{ item.name.charAt(0) }}</span>
            <div class="name">{{ item.name }}</div>
            <div class="age">{{ item.age }}</div>
            <div class="dropdown">
              <button class="btn btn-primary dropdown-toggle" type="button" :id="'dropdownMenu' + index" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Select
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenu1">
                <button class="dropdown-item" type="button">Action</button>
                <button class="dropdown-item" type="button">Another action</button>
                <button class="dropdown-item" type="button">Something else here</button>
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
        data: {
          household: []
        }
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
        let url = drupalSettings.path.baseUrl + 'myy/data/profile/family-list';

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
  .myy-household {
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
    .content {
      border-left: 1px solid #636466;
      border-right: 1px solid #636466;
      border-bottom: 1px solid #636466;
      margin: 0;
      padding: 20px 5px;
      .col {
        text-align: center;
      }
      .rounded_letter {
        margin-bottom: 20px;
      }
      .name {
        color: #231F20;
        font-weight: bold;
        line-height: 21px;
      }
      .btn {
        background-color: #92278F;
        border-color: #92278F;
        text-align: left;
        font-size: 14px;
        margin-top: 10px;
        width: 100%;
        &:after {
          position: absolute;
          right: 10px;
          top: 25px;
        }
      }
    }
  }
</style>

