# Landing Page
Landing Page content type is used for adding landing pages on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the landing page item. |
| Layout | field\_lp_layout  | Yes | Select list with the options: <ul><li>one\_column\_clean\|One Column - Full width</li><li>one\_column\|One Column</li><li>two\_column\|Two Columns</li><li>two\_column\_fixed\|Two Columns with fixed sidebar (sticky at the top)</li></ul> |
| Meta Tags  | field\_meta_tags  | No | A meta tags field allows us to provide structured metadata and Graph meta tags for Facebook, Pinterest, LinkedIn and other social networking sites. |
| **Header Area** | Field group |||
| Content | field\_header_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Content Area** | Field group |||
| Content | field_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Sidebar Area** | Field group |||
| Content | field\_sidebar_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Bottom Area** | Field group|||
| Content | field\_bottom_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |

### URL pattern

Content type is using following pattern:
`[node:title]`
