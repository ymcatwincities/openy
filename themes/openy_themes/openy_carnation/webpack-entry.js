import './src/scss/style.scss'
//import './src/js/**/*.js'

require.context('./src/js/', true, /\.(js)$/im);
// let files = glob.sync(path.resolve(__dirname) + './src/js/**/*.js')
// this.logger.log(files)