var config = {
  buildDir: 'nuxt-dist',
  mode: 'spa',
  plugins: [
    {src: '~plugins/vue-cookie', ssr: false, injectAs: 'cookie'}
  ],
  /*
  ** Headers of the page
  */

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
      app: ({ isDev }) => !isDev ? '[name].js' : '[name].js',
      chunk: ({ isDev }) => !isDev ? '[id].[name].js' : '[id].[name].js',
      css: ({ isDev }) => !isDev ? '[name].css' : '[name].css',
    },
    splitChunks: {
      layouts: false,
      pages: false,
      commons: false
    }
  },
  css: [
    '@/assets//scss/myy_global.scss',
    '@/assets//scss/myy_global_build.scss',
  ]
}

if (process.env.NODE_ENV === 'dev') {
  /*css = ['bootstrap','~/node_modules/bootstrap/scss/bootstrap'];*/
  let custom_css = [
    '@/assets//scss/myy_global.scss',
    '@/assets//scss/myy_global_dev.scss',
  ];
  config.css = custom_css;
  config.head = {
    meta: [
      { name: 'viewport', content: 'width=device-width, initial-scale=1' },
    ],
    script: [
      { src: 'https://code.jquery.com/jquery-2.2.4.min.js' },
      { src: 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.bundle.js' },
      { src: 'https://use.fontawesome.com/95fd5fcc01.js' },
      { src: 'https://cdnjs.cloudflare.com/ajax/libs/datepicker/0.6.5/datepicker.min.js' },
    ],
    link: [
      { rel: 'stylesheet', href: 'https://cdnjs.cloudflare.com/ajax/libs/datepicker/0.6.5/datepicker.css' }
    ]
  }
}

module.exports = config;
