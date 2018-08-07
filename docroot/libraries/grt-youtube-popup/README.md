# GRT Youtube Popup - jQuery Plugin
Simple and lightweight jQuery plugin for playing youtube embed videos in a popup.

You can check the demo here: [grt107.github.io/grt-youtube-popup/](http://grt107.github.io/grt-youtube-popup/)

# Screenshot:
![Alt text](/screenshot.jpg?raw=true "Demo Screenshot")

# How to use the plugin in your website:
1- Include the plugin stylesheet file ```grt-youtube-popup.css``` inside your ```<head>``` tag

  ```html
  <link rel="stylesheet" href="grt-youtube-popup.css">
  ```

2- Include the plugin javascript file ```grt-youtube-popup.js``` inside the ```<body>``` tag and after ```jquery.min.js```

  ```html
  <script src="grt-youtube-popup.js"></script>
  ```

3- Add a custom class and insert the id of the Youtube video as a new attribute ```youtubeid="yPu6qV5byu4"``` like in the example below:

  ```html
  <span class="youtube-link" youtubeid="yPu6qV5byu4">Open Video</span>
  ```

4- Initialize the plugin at the end of all javascript files using your custom class (after ```jquery.min.js``` and ```grt-youtube-popup.js```)

```html
  <script> $(".youtube-link").grtyoutube(); </script>
  ```

# Advanced Options
- ```Autoplay``` (enabled by default) - accepted values: ```true``` or ```false```

```html
<script> $(".youtube-link").grtyoutube({ autoPlay:false }); </script>
```

- ```Theme``` (dark theme is set by default) - accepted values: ```"dark"``` or ```"light"```

```html
<script> $(".youtube-link").grtyoutube({ theme: "dark" }); </script>
```
