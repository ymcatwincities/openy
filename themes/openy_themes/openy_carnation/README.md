# OpenY Carnation theme Readme.

##1. General info.
Carnation is an OpenY profile theme based on Twitter Bootstrap 4.


##2. Theme development

Carnation uses Webpack compiler. If you want to make any changes in css
or js, please install Node.js and follow next instructions.

##2.1 Go to the theme's folder and install packages that, required for compilation.

`npm install`

##2.2 Use dev mode for development (watcher wil scan for your changes and generate compiled version on fly)

`npm run dev`

##2.3 For final compilation, please use build command.

`npm run build`

##2.4 YARN (alternative to NPM) support

If you prefer yarn rather than npm, it is also supported

To install it, use (`brew install yarn`)

Dev mode: `yarn run dev`

Production build: `yarn run build`