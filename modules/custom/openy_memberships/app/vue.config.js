module.exports = {
  "assetsDir": "assets",
  "filenameHashing": false,
  "configureWebpack": {
    "devtool": "inline-source-map",
  },
  "css": {
    "extract": {
      "filename": "assets/css/[name].css"
    },
    "loaderOptions": {
      "sass": {}
    }
  },
  "pluginOptions": {
    "svg": {
      "data": {}
    }
  },
  // "transpileDependencies": [
  //   "vuetify"
  // ]
}