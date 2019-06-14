<template>
  <div>
    <div class="text-center">
      <h1 class="program-search__form--title">Program Search</h1>
      <form v-if="!isSearchBoxDisabled" class="program-search__form" role="search">
        <div class="row row-eq-height justify-content-center">
          <div class="col-12 col-xs-12 col-md-6 d-flex">
            <input type="text" class="program-search__keywords" v-model="keywords" id="program-search__keywords" placeholder="Keywords ..."/>
            <button @click.prevent="submitSearch()" v-bind:disabled="isSearchSubmitDisabled" type="submit" class="btn btn-primary text-white rounded-0">
              <i class="fa fa-search" aria-hidden="true"><span class="visually-hidden">Submit</span></i>
            </button>
          </div>
        </div>
      </form>

      <a v-bind:href="this.$parent.programSearchUrl"><strong>View all programs</strong></a>
      <br/>
      <p v-if="this.$parent.loading">
        <spinner></spinner>
      </p>
      <span v-if="!this.$parent.loading">{{ count }} results</span>

      <h2 class="program-search__af-title">Activity Finder</h2>
      <p>
        We can help you find the right activities for you and your family!
        <b>How should we begin?</b>
      </p>

      <div class="af-routing--links">
        <router-link :to="{name: 'af-age'}" @click.native="setInitialStep('age')" class="af-routing--link btn">By Age</router-link>
        <router-link :to="{name: 'af-day'}" @click.native="setInitialStep('day')"  class="af-routing--link btn">By Day Of Week</router-link>
        <router-link :to="{name: 'af-activity'}" @click.native="setInitialStep('activity')"  class="af-routing--link btn">By Activity</router-link>
        <router-link :to="{name: 'af-location'}" @click.native="setInitialStep('age')"  class="af-routing--link btn">By Location</router-link>
      </div>

    </div>
  </div>
</template>

<script>
  import Spinner from '../components/Spinner.vue'

  export default {
    data () {
      return {
        keywords: '',
        isSearchSubmitDisabled: true,
        isSearchBoxDisabled: drupalSettings.activityFinder.is_search_box_disabled
      };
    },
    components: {
      Spinner
    },
    computed: {
      count: function() {
        return this.$parent.table.count;
      }
    },
    methods: {
      setInitialStep: function(stepName) {
        this.$parent.$emit('setInitialStep', stepName);
      },
      submitSearch: function () {
        // Redirect to Search page.
        //window.location.href = this.programSearchUrl + window.location.search;
        this.$parent.$emit('submitSearch');
      }
    },
    watch: {
      'keywords': function(newValue, oldValue) {
        var component = this.$parent;
        component.keywords = newValue;
        this.isSearchSubmitDisabled = newValue === '' ?  true : false;
      }
    }
  }
</script>