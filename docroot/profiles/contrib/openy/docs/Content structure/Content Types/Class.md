# Class
Class content type is used for adding Classes on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the class item. |
| Activity  | field\_class_activity  | No | A reference field for selecting the class. |
| **Header Area** | Field group|||
| Content | field\_header_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Content Area** | Field group|||
| Description | field\_class_description | No | Textarea for the description/body with WYSIWYG, without summary. |
| Content | field_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Sidebar Area** | Field group |||
| Content | field\_sidebar_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Bottom Area** | Field group|||
| Content | field\_bottom_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |

### URL pattern
Content type is using following pattern:
`/programs/[node:field_class_activity:entity:field_activity_category:entity:field_category_program:entity:title]/[node:field_class_activity:entity:field_activity_category:entity:title]/[node:field_class_activity:entity:title]/[node:title]`
