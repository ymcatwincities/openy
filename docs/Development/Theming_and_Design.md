Welcome to Open Y Theming and Design documentation.

### How to change styles on content type level

**Given:** 
As an Open Y site developer, I want to be able to easily change the CSS for a Camp page 
independently from a Location page, so I can better customize the site to meet the needs of my customers. 

**How to:**
- If you need to change CSS on some pages independently, you should enable Custom CSS functionality on 
the theme configuration page - Custom CSS - check "Enable or disable custom CSS".
- Input CSS code into the textarea.

In order to change CSS on each particular page you should use the following selectors:
- .page-node-type-{node type};
- .node-id-{node ID};
- .path-frontpage.
  
The existing node types are: _activity_, _alert_, _blog_, _branch_, _camp_, _class_, _facility_, _landing-page_, _membership_, _news_, _program_, _program-subcategory_, _session_.

