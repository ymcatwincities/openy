 <template>
  <span>
    <integer-plusminus @input="$emit('input', $event)" :min="ips_min"
      :max="ips_max"
      :step="ips_step"
      :vertical="ips_vertical"
      :disabled="ips_disabled"
      v-model="ips_value">
      <template slot="decrement"> .</template>
    
      <template slot="increment"> .</template>
    </integer-plusminus>
  </span>
 </template>

<script>
import { IntegerPlusminus } from 'vue-integer-plusminus'

export default {
  props: ['min', 'max', 'step', 'vertical', 'disabled', 'value'],
  data() {
    return {
      'ips_min': this.min || 0, 
      'ips_max': this.max || 10, 
      'ips_step': this.step || 1, 
      'ips_vertical': this.vertical || false, 
      'ips_disabled': this.disable || false, 
      'ips_value': this.value || 0
    }
  },
  components: {
    IntegerPlusminus
  }
}
</script>

<style lang="scss">
  .int-pm {
    display: inline-flex !important;
    align-items: center;
    .int-pm-value.int-pm-value {
      border: 1px solid #707070;
      padding: 15px;
      width: 50px;
      height: 50px;
    }
    .int-pm-btn.int-pm-btn {
      margin: 5px;
      background-color: transparent !important;
      border-radius: 50%;
      width: 32px;
      height: 32px;
      border: none !important;
      position: relative;
      color: transparent;
      font-size: 12px;
      line-height: 12px;
      font-weight: bold;
      &::before {
          content: '';
          color: #0060AF;
          display: block;
          position: absolute;
          width: 18px;
          height: 18px;
          top: 8px;
          left: 8px;
          padding: 1px 2px;
          border: 2px solid #0060AF;
          border-radius: 50%;
      }
      &.int-pm-decrement {
        &::before {
          content: '-';
        }
      }
      &.int-pm-increment {
        &::before {
          content: '+';
        }
      }
      &.disabled {
        color: transparent;
        &.int-pm-increment {
          &::before {
            border: 2px solid rgba(0, 96, 175, 0.5);
            color: rgba(0, 96, 175, 0.8);;
          }
        }
        &.int-pm-decrement {
          &::before {
            border: 2px solid rgba(0, 96, 175, 0.5);
            color: rgba(0, 96, 175, 0.8);;
          }
        }
      }
    }
  }
</style>