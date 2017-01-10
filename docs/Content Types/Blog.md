# Blog
Blog Post content type is used for adding blog posts on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the blog item. |
| Style | field\_blog_style  | Yes | Select list field with multiple options for choosing style: <ul><li>Story Card</li><li>Photo Card</li><li>News Card</li><li>Fuchsia Card (default)</li><li>Green Card</li></ul> |
| Location | field\_blog_location | Yes | Reference field to branch nodes. Multiple Values. |
| Category | field\_blog_category | No | Reference field for choosing the term from "Blog Category" vocabulary. Multiple Values. |
| **Content Area** ||||
| Image | field\_blog_image | No | Image field for the Blog item. Entity reference to Media bundle. |
| Description | field_body | No | Textarea for the description/body with WYSIWYG, without summary. |
| Content | field_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Sidebar Area** ||||
| Related Blog posts | field\_blog\_posts_related | No | Reference field for choosing related Blog nodes. Multiple Values. |
| Content | field\_sidebar_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |

### URL pattern

Content type is using following pattern:
`/blog/[node:title]`
