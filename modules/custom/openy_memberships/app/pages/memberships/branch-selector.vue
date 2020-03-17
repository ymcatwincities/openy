<template>
  <section class="container">
    <div>
      <div>  
        <h1 class="title">
          Memberships Builder
        </h1>
      </div>
      <div>  
        <p>
          <b>Step 1 of 2:</b> Select your preffered YMCA baranch <a @click="next">NEXT</a>
        </p>
      </div>
        
      <v-tabs v-model="tab" background-color="primary" dark>
        <v-tab>
          Zip Code
        </v-tab>
        <v-tab>
          Manual Section
        </v-tab>
      </v-tabs>

      <v-tabs-items v-model="tab">
        <v-tab-item>
          <div>
            <label>Postal Code</label> <v-text-field v-model="zip" class="zip-code">
            <v-btn slot="append" color="green">Go</v-btn>
            </v-text-field> <a>Use my current location</a>
          </div>
          <div class="results">
            <div class="left">
              <p>Nearest to <span v-text="zip"></span></p>
              <div>
                <locations :locations="locations" />
              </div>
            </div>
            <div class="right">
              <div class="map-wrap">
                <no-ssr>
                  <l-map :zoom=13 :center="[55.9464418,8.1277591]">
                    <l-tile-layer url="http://{s}.tile.osm.org/{z}/{x}/{y}.png"></l-tile-layer>
                    <l-marker :lat-lng="[55.9464418,8.1277591]"></l-marker>
                  </l-map>
                </no-ssr>
              </div>
            </div>
          </div>
        </v-tab-item>
        <v-tab-item>
          <div>
            <locations :locations="locations" />
          </div>
        </v-tab-item>
      </v-tabs-items>
      <div>  
        <p>
          <b>Step 1 of 2:</b> Select your preffered YMCA baranch <a @click="next">NEXT</a>
        </p>
      </div>
    </div>
  </section>
</template>

<script>
import Locations from '~/components/Locations';

export default {
  methods: {
    next() {
      this.$router.push({
          path: '/memberships/summary'
      })
    }
  },
  components: {
    Locations
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
          name: "Test",
          address: "Address"
        },
        {
          name: "Test",
          address: "Address"
        }
      ]
    }
  }
}
</script>

<style lang="scss">
.results {
  display: flex;
  .left {
    min-width: 50%;
  }
  .left, .right {
    padding: 10px;
  }
}
.v-input.zip-code {
  display: inline-block;
  .v-input__slot {
    border: 1px solid #000;
    &::before {
      border: none !important;
    }
  }
  .v-input__append-inner {
    margin: 0;
  }
}
.map-wrap {
  height: 300px;
  width: 400px;
}
</style>

