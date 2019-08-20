module.exports = {
  buildDir: 'nuxt-dist',
  mode: 'spa',
  /*
  ** Headers of the page
  */
  head: {
    title: 'openy_myy_app',
    meta: [
      { charset: 'utf-8' },
      { name: 'viewport', content: 'width=device-width, initial-scale=1' },
      { hid: 'description', name: 'description', content: 'OpenY MyY project' }
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
    extend (config, { isDev, isClient }) {
      if (isDev && isClient) {
        config.module.rules.push({
          enforce: 'pre',
          test: /\.(js|vue)$/,
          loader: 'eslint-loader',
          exclude: /(node_modules)/
        })
      }
    },
    extractCSS: {
      allChunks: true
    },
    filenames: {
      app: ({ isDev }) => !isDev ? '[name].js' : 'app.js',
      chunk: ({ isDev }) => !isDev ? '[name].js' : '[chunkhash].js',
      css: ({ isDev }) => !isDev ? '[name].css' : '[name].css',
    },
    splitChunks: {
      layouts: false,
      pages: false,
      commons: false
    },
    css: {
      loaderOptions: {
        sass: {
          data: `@import "node_modules/bootstrap/scss/bootstrap";`
        }
      }
    }
  }
}

