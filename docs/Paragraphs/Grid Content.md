# Grid Content
This is a paragraph type that will be used for the grid content stories.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Style | field\_prgf\_grid_style | Yes | Select list with following values: <ul><li>2\|2 items per row</li><li>3\|3 items per row</li><li>4\|4 items per row</li></ul> |
| **Content** | field\_grid_columns | Field collection | Grid columns |
| Description| field\_prgf\_column_description | No | Textarea for the description/body with WYSIWYG, without summary. |
| Headline | field\_prgf\_column_headline | No | Headline of the grid content. |
| Icon | field\_prgf\_column_icon | No | Entityreference to media asset. Should allow to upload svgs.|
| Icon class | field\_prgf\_column_class | No | Input field that allows to add the font-awesome icons needed. Description - "Provide a "Font Awesome" icon mane, e.g. flag, car, info. Overrides image Icon." |
| Link | field\_prgf\_column_link | No | Link field that supports internal and external URLs. |
