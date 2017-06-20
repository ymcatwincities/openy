# Media WYSIWYG View Modes
Listed view modes are available for embedding in WYSIWYG editor.

### View Modes
| Name  | Machine name | Description |
| ------------- | ------------- | ------------- |
| Full | embedded_full | This view mode displays media asset with full width. |
| Half | embedded_half | This view mode displays media asset with half width and uses alingment. |
| Link | embedded_link | This view mode displays link to media asset. |

### Bundles details

#### Image
In "Full" and "Half" view modes image should be display in `<img>` tag with appropriate classes.
Link - should lead to the original image with `target=blank`.

#### Video
In "Full" and "Half" view modes should be displayed embedded video with appropriate classes.
Link - should lead to the original video with `target=blank`.

#### Document
In "Full" and "Half" view modes document should be displayed as iframe, where `URL` is URL to the document. Also it should have appripriate classes.
```html
<iframe src="//docs.google.com/gview?url=URL&embedded=true" frameborder="0"></iframe>
```
Link - should lead to the original document with `target=blank`.
