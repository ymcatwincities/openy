# Alert
Alert content type is used for adding alerts on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the activity item. |
| Background color | field\_alert_color | Yes | Reference field for choosing the term from "Color" vocabulary. |
| Text color | field\_alert\_text_color | Yes | Reference field for choosing the term from "Color" vocabulary. |
| Icon color | field\_alert\_icon_color | No | Reference field for choosing the term from "Color" vocabulary. Description for field: "Leave empty to hide icon." |
| Placement | field\_alert_place | Yes | Select list field (singular) for choosing place: <ul><li>Header</li><li>Footer</li></ul> |
| Description | field\_alert_description | Yes | Textarea for the description/body with WYSIWYG, without summary. |
| Link | field\_alert_link | No | Internal or external link. |
| Reference | field\_alert_belongs | No | Entityreference with autocomplete to any node. Description for field: "Reference to node (branch, camp, landing page and etc.), where local alert will be displayed." |

### URL pattern
Content type is using following pattern:
`/alert/[node:title]`.
