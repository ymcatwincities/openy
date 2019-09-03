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
      //{ rel: 'stylesheet', href: 'https://fonts.googleapis.com/css?family=Roboto' }
    ],
    script: [
      { src: 'https://code.jquery.com/jquery-2.2.4.min.js' },
      { src: 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.bundle.js' },
      { src: 'https://use.fontawesome.com/95fd5fcc01.js' }
    ],
  },
  /*
  ** Customize the progress bar color
  */
  loading: { color: '#3B8070' },
  /*
  ** Build configuration
  */
  build: {
    devtools: true,
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
      allChunks: ({ isDev }) => !isDev,
    },
    filenames: {
      app: ({ isDev }) => !isDev ? '[name].js' : 'app.js',
      chunk: ({ isDev }) => !isDev ? '[id].[name].js' : '[id].[name].js',
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

