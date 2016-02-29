# Generic HTTP Purger

This project provides a generic HTTP-based purger to the [Purge](https://www.drupal.org/project/purge)
project and allows site builders to support caching platforms and CDNs that
aren't supported by any other modules. It aims to provide the same technical
configuration options that older versions of Purge provided.

##### Roadmap

* To provide a minimum viable product for Drupal 8.0.
* To port most of the HTTP configuration options legacy purge provides.
* The ability to create one or more 'success conditions' + AND/OR selection. The
  API core provides certainly looks promising for this.
* The ability to add/define POST fields.
* Robust testing coverage.
