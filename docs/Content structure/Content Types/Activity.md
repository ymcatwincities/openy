# Activity
Activity content type is used for adding Activities on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the activity item. |
| Program Subcategory  | field\_activity_category  | Yes | A reference field for selecting the program subcategory. |
| **Content Area** | Field group|||
| Description | field\_activity_description | No | Textarea for the description/body with WYSIWYG, without summary. |

### URL pattern
Content type is using following pattern:
`/programs/[node:field_activity_category:entity:field_category_program:entity:title]/[node:field_activity_category:entity:title]/[node:title]`
