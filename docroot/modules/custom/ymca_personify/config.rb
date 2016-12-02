# Require any additional compass plugins here.
require 'compass'

css_dir = "css"
sass_dir = "scss"
environment = :development
relative_assets = true
line_comments = false
debug = false
asset_cache_buster :none
output_style = (environment == :development) ? :expanded : :compressed
sass_options = (environment == :development && debug == true) ? {:debug_info => true} : {}
Encoding.default_external = "utf-8"
