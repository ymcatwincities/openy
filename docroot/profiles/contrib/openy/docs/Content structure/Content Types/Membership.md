# Membership
Membership content type is used for adding membership on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the membership item. |
| Description | field\_mbrshp_description | Yes | Textarea for the description/body with WYSIWYG, without summary. |
| Image | field\_mbrshp_image | Yes | Media field to upload the image. |
| **Membership info**  | field\_mbrshp_info | Paragraph | Paragraph to indicate the location where the membership is available and the URL.|
| Location | field\_mbrshp_location | No | Select list with locations (branches). Single value. |
| Link | field\_mbrshp_link | No | Link field to provide the membership redirect URL. |
| Join Fee | field\_mbrshp\_join_fee | No | Dollar value for how much someone has to pay to join. |
| Monthly Rate | field\_mbrshp\_monthly_rate | No | Dollar value for the monthly fee of the membership. |

### URL pattern

Content type is using following pattern:
`/membership/[node:title]`
