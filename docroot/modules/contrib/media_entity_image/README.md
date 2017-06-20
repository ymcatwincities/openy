## About Media entity

Media entity provides a 'base' entity for a media element. This is a
very basic entity which can reference to all kinds of media-objects
(local files, YouTube videos, tweets, CDN-files, ...). This entity only
provides a relation between Drupal (because it is an entity) and the
resource. You can reference to this entity within any other Drupal
entity.

## About Media entity image

This module provides local image integration for Media entity (i.e.
media type provider plugin). The user can map fields from image's Exif
data to media bundle fields.

### Mapping meta-data to drupal fields

If you want to map some of the image meta-data (for example some of the
EXIF information), follow these steps:
1- Create a media bundle of type image
2- Create some normal fields that will be used to store this information
3- Go back to the bundle edit form, select "Yes" on the "Whether to
gather exif data" drop down
4- Map the desired EXIF fields to the fields you created in step 2.
These will get automatically populated each time the entity is saved and
the drupal fields on the entity are empty.
NOTE that this behavior (only mapping the meta-data when the drupal
field is empty) may change in the future, once this issue is solved:
https://www.drupal.org/node/2772045


Project page: http://drupal.org/project/media_entity_image.

Maintainers:
 - Janez Urevc (@slashrsm) drupal.org/user/744628
 - Primož Hmeljak (@primsi) drupal.org/user/282629

IRC channel: #drupal-media
