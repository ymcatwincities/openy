# Views Rest Feed
--------------------------------------------------------------------------------

This module adds a new "Rest Export Feed" view display type, allowing 
"REST Export" displays to be attached to other Views displays. This behaves 
in the same way as the core "Feed" display type, which permits the attachment 
of RSS feeds to other displays (among other things).

## Setup

1. Enable views_rest_feed module.
1. Create a new view.
1. Add a "Page" display.
1. Add a "REST Export Feed" display to the view.
1. Under Format -> Serializer -> Settings, choose exactly one accepted request
   format.
1. Under Feed Settings -> Attach to, select "Page".
1. Under Feed Settings -> Path, set a path. Ideally you should make the path
   extension match the accepted request format. E.g., if you accepting JSON, 
   create a path ending in .json.
1. Save the view
1. View the "Page" display.
1. Observe the "JSON" feed icon at the footer of the page.
   
