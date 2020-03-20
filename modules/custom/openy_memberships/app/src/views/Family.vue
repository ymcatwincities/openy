<template>
  <section class="container">
    <div>
      <div class="">
        <div class="">
          <h1 class="title">
            Membership Builder
          </h1>
        </div>
      </div>
      <div class="description">  
        <div class="description-text">
          How many people will be included in your membership?
        </div>
      </div>
        
      <div class="family-wrapper">
        <div class="label-row">
          <div class="label">Adults (18-54 yrs)</div><div class="value"><integer-minus-plus @input="updateFamily('adults', $event)" /></div>   
        </div>
        <div class="label-row">
          <div class="label">Youth (0-17 yrs)</div><div class="value"><integer-minus-plus @input="updateFamily('youth', $event)" /></div>
        </div>
        <div class="label-row">
          <div class="label">Seniors (55+ yrs)</div><div class="value"><integer-minus-plus @input="updateFamily('seniors', $event)" /></div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import IntegerMinusPlus from '../components/IntegerMinusPlus'
export default {
  mounted() {
  },
  computed: {
    totalCount() {
      let count = 0;
      Object.keys(this.$store.state.family).forEach(element => {
        count = count + this.$store.state.family[element]
      });
      return count;
    }
  },
  components: {
    IntegerMinusPlus
  },
  data () {
    return {
      family: {
        ...this.$store.state.family
      }
    }
  },
  methods: {
    updateFamily(key, value) {
      this.family[key] = value
      this.$store.commit('setFamily', this.family)
    }
  }
}
</script>

<style lang="scss">
.family-wrapper {
  border: 1px solid #F2F2F2;
  margin-bottom: 10px;
}
.label-row {
  display: flex;
  padding: 10px;
  align-items: center;
  .label {
    width: 200px;
    text-align: left;
    font: Bold 14px/21px Verdana;
    letter-spacing: 0;
    color: #231F20;
  }
}
</style>

