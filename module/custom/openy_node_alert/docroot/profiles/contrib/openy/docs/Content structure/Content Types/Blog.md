# Blog
Blog Post content type is used for adding blog posts on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the blog item. |
| Locations | field\_blog_location | Yes | Reference field to `branch` and `camp` nodes. Multiple Values. |
| Category | field\_blog_category | No | Reference field for choosing the term from "Blog Category" vocabulary. Multiple Values. |
| Meta Tags  | field\_meta_tags  | No | A meta tags field allows us to provide structured metadata and Graph meta tags for Facebook, Pinterest, LinkedIn and other social networking sites. |
| **Styles** | Field group |||
| Style | field\_blog_style  | Yes | Select list field with multiple options for choosing style: <ul><li>Story Card</li><li>Photo Card</li><li>News Card (default)</li><li>Color Card</li></ul> |
| Background color | field\_blog\_color  | No | teaser background color  (used when Color Card style is selected.) |
| Text color | field\_blog\_text\_color | No | teaser text color  (used when Color Card style is selected.) |
| **Content Area** | Field group |||
| Image | field\_blog_image | No | Image field for the Blog item. Entity reference to Media bundle. |
| Description | field_blog_description | No | Textarea for the description/body with WYSIWYG, without summary. |
| Content | field_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Sidebar Area** | Field group |||
| Related content | field\_blog_related | No | Reference field for choosing related Blog nodes. Multiple Values. |
| Content | field\_sidebar_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |

### URL pattern

Content type is using following pattern:
`/blog/[node:title]`
