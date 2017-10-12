# News
News Post content type is used for adding news posts on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the news item. |
| Locations | field\_news_location | Yes | Reference field to `branch` and `camp` nodes. Multiple Values. |
| Category | field\_news_category | No | Reference field for choosing the term from "News Category" vocabulary. Multiple Values. |
| Meta Tags  | field\_meta_tags  | No | A meta tags field allows us to provide structured metadata and Graph meta tags for Facebook, Pinterest, LinkedIn and other social networking sites. |
| **Content Area** | Field group |||
| Image | field\_news_image | No | Image field for the News item. Entity reference to Media bundle. |
| Description | field_news_description | No | Textarea for the description/body with WYSIWYG, without summary. |
| Content | field_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |
| **Sidebar Area** | Field group |||
| Related content | field\_news_related | No | Reference field for choosing related News nodes. Multiple Values. |
| Content | field\_sidebar_content | No | A paragraph embed field that will allow us to add various flexible content modules, from the predefined list of paragraph types. |

### URL pattern

Content type is using following pattern:
`/news/[node:title]`
