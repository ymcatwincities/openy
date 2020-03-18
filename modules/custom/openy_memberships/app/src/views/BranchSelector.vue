<template>
  <section class="container">
    <div>
      <div class="row">
        <div class="col">
          <h1 class="title">
            Membership Builder
          </h1>
        </div>
      </div>
      <div class="row">  
        <div class="col col-6">
          Select your preferred YMCA branch.
        </div>
        <div class="col col-6 text-align-right">
          <a >link</a>
        </div>
      </div>
        

      <div>
        <div>
          <div>
            <label>Postal Code</label> 
            <div class="zip-code">
              <input v-model="zip" />
              <button slot="append" color="green">Go</button>
              <a>Use my current location</a>
            </div>
          </div>
          <div class="results">
            <div>
              <p>Nearest to <span v-text="zip"></span></p>
              <div>
                <locations :locations="locations" />
              </div>
            </div>
          </div>
        </div>
        <div>
          <div>
            <locations :locations="locations" />
          </div>
        </div>
      </div>
      <div>  
        <p>
          <b>Step 1 of 2:</b> Select your preffered YMCA baranch <a @click="next">NEXT</a>
        </p>
      </div>
    </div>
  </section>
</template>

<script>
import Locations from '@/components/Locations';

// import { LMap, LTileLayer, LMarker } from 'vue2-leaflet';
// import 'leaflet/dist/leaflet.css';
// import { Icon } from 'leaflet';

// delete Icon.Default.prototype._getIconUrl;
// Icon.Default.mergeOptions({
//   iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png'),
//   iconUrl: require('leaflet/dist/images/marker-icon.png'),
//   shadowUrl: require('leaflet/dist/images/marker-shadow.png'),
// });

export default {
  mounted() {
    jQuery.ajax({
      url: '/'
    })
  },
  methods: {
    next() {
      this.$router.push({
          path: '/memberships/summary'
      })
    }
  },
  components: {
    Locations,
  },
  data () {
    return {
      tab: null,
      zip: null,
      items: [
        { tab: 'zip'},
        { tab: 'manual'},
      ],
      locations: [
        {
          name: "Test 1",
          address: "Address",
          value: 1,
        },
        {
          name: "Test 2",
          address: "Address",
          value: 2,
        }
      ]
    }
  }
}
</script>

<style lang="scss">

</style>

