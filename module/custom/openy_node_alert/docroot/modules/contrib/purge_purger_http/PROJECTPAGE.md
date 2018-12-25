[//]: # ( clear&&curl -s -F input_files[]=@PROJECTPAGE.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2 )
[//]: # ( curl -s -F input_files[]=@PROJECTPAGE.md -F from=markdown -F to=pdf http://c.docverter.com/convert>PROJECTPAGE.pdf )

This project provides a generic HTTP-based purger to the [Purge](https://www.drupal.org/project/purge)
project and allows site builders to support caching platforms and CDNs that
aren't supported by any other modules. It aims to provide the same technical
configuration options that older versions of Purge provided.
