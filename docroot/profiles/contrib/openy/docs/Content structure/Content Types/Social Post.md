# Social Post
Social Post content type is used for adding Social Posts on the site. Social Posts are grabbed from social networks.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the program item. |
| ID| field_id|Yes|Post Id in social network. This is system field. Is used by post fetcher.|
| Image| field_image|No|Image field for saving post image. Can save jpg and png formats.|
|Link|field_link|no|Contains link to original post in social network.|
|Platform|field_platform|no|The name of platform where post was imported from.|
|Post|field_post|yes|Text of post.|
|Posted|field_posted|no|Date when post was posted in social network
### URL pattern

Content type is using following pattern:
`/social_post/[node:title]`
