<template>
  <div id="app" :class="$router.currentRoute.name">
    <router-view />
    <div class="navigation container" v-if="$store.state.location">
      <button @click="goNext">Next</button>
    </div>
  </div>
</template>

<script>

export default {
  name: 'App',
  computed: {
    step() {
      return this.$store.state.step
    }
  },
  mounted() {
    
    let step = this.$store.state.step;
    let steps = this.$store.state.steps;
    if (steps[step] && this.$route.name != steps[step]) {
      this.$router.replace({ name:  steps[step] })
    }
  },
  data: () => ({
    //
  }),
  methods: {
    goNext() {
      let currentStep = this.$store.state.steps.indexOf(this.$route.name);
      if(currentStep !== -1 && currentStep + 1 < this.$store.state.steps.length) {
        this.$store.commit('setStep', currentStep + 1)
      }
    }
  },
  watch: {
    '$route' (to) {
      let step = this.$store.state.step;
      let currentStep = this.$store.state.steps.indexOf(to.name);
      if (currentStep != -1 && step != currentStep) {
        this.$store.commit('setStep', currentStep)
      }
    },
    step() {
      let step = this.$store.state.step;
      let steps = this.$store.state.steps;
      if (steps[step] && this.$route.name != steps[step]) {
        this.$router.push({ name:  steps[step] })
      }
    }
  }
};
</script>
<style lang="scss">
  #app {
    h1.title {
      margin: 30px 0;
    }
    .description {
      .description-text {
        margin-bottom: 30px;
      }
    }
  }
</style>