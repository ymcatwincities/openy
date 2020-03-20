<template>
  <div id="app" :class="$router.currentRoute.name">
    <router-view />
    <div class="navigation container" v-if="$store.state.location">
      <button class="btn btn-next" @click="goNext">Next</button>
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
    .btn {
      &.btn-next {
        background: #92278F 0% 0% no-repeat padding-box;
        border-radius: 5px;
        text-align: center;
        font: Medium 24px/26px Cachet;
        letter-spacing: 0;
        color: #FFFFFF;
        text-transform: uppercase;
        margin: 10px;
      }
    }
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