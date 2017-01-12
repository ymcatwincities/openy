# Program Subcategory
Program Subcategory content type is used for adding program subcategories on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the blog item. |
| Program  | field\_category_program  | Yes | A reference field for selecting the program. |
| **Header Area** ||||
| Image | field\_category_image | No | A image field, for uploading the category image. |
| Color | field\_category_color | No | Reference field for choosing the term from "Color" vocabulary. |
| **Content Area** ||||
| Description | field\_category_description | No | Textarea for the description/body with WYSIWYG, without summary. |
| Content | field_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Sidebar Area** ||||
| Content | field\_sidebar_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |

### URL pattern

Content type is using following pattern:
`/programs/[node:field_category_program:title]/[node:title]`
