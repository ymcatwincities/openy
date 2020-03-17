module.exports = {
  buildModules: [
    '@nuxtjs/vuetify',
    '@nuxtjs/svg',
    'nuxt-leaflet',
  ],
  /*
  ** Headers of the page
  */
  head: {
    title: 'openy_memberships',
    meta: [
      { charset: 'utf-8' },
      { name: 'viewport', content: 'width=device-width, initial-scale=1' },
      { hid: 'description', name: 'description', content: 'Open Y Memberships' }
    ],
    link: [
      { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' }
    ]
  },
  /*
  ** Customize the progress bar color
  */
  loading: { color: '#3B8070' },
  /*
  ** Build configuration
  */
  build: {
    /*
    ** Run ESLint on save
    */
    publicPath: '/',
    filenames: {
      chunk: '[name].js',
      app: '[name].js',
      css: '[name].css',
    },
    extend (config, { isDev, isClient }) {
      if (isDev && isClient) {
        config.module.rules.push({
          enforce: 'pre',
          test: /\.(js|vue)$/,
          loader: 'eslint-loader',
          exclude: /(node_modules)/
        })
      }
      // if (isClient) {
      //   config.devtool = 'eval-source-map'
      // }
    },
    splitChunks: {
      layouts: false,
      pages: false,
      commons: false
    },
    extractCSS: true,
    optimization: {
      splitChunks: {
        chunks: 'all',
        cacheGroups: {
          styles: {
            name: 'styles',
            test: /\.(css|vue)$/,
            chunks: 'all',
            enforce: true
          },
        }
      }
    }
  }
}

