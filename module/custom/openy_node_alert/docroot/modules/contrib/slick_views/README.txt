ABOUT

This adds a new display style to views called "Slick carousel". Similar to how
you select "HTML List" or "Unformatted List" as display styles.

This module doesn't require Views UI to be enabled but it is required if you
want to configure your Views display using Slick carousel through the web
interface. This ensures you can leave Views UI off once everything is setup.

DEPENDENCIES
o Views (in core)
o Slick 2.x above. [1]

Be sure to install the Slick example[2] to avoid adventures in the first place.

[1] http://dgo.to/slick
[2] http://dgo.to/slick_extras

OPTIONSET
Arm yourself with proper optionsets. To create one, go to:

"admin/config/media/slick"

Be sure to install the Slick UI module first, included in the main Slick module,
otherwise no such URL, and regular access denied error.

USAGE

Go to Views UI "admin/structure/views", add a new view, and a block.

Usage #1
--------
Displaying multiple (rendered) entity instances for the slides by View modes.
- Choose "Slick carousel" under the Format.
- Choose "Rendered entity" or "Content" under "Show" under "Format", and its
  View mode.

Themeing is related to their own entity display outside the Views UI.
Example use case: Blogs, teams, testimonials, case studies sliders.

Usage #2
--------
Displaying multiple entity instances using selective fields for the slides.
- Choose "Slick carousel" under the Format.
- Choose available optionsets you have created at "admin/config/media/slick"
- Choose "Fields" under "Show" under "Format".
- Add fields, and do custom works or markups. If having a multi-value Image
  field, recommended to only display 1.

Themeing is all yours inside the Views UI.

Example use case: similar as above.

Usage #3
--------
Displaying a multiple-value field in a single entity display for the slides.
Use it either with contextual filter by NID, or filter criteria by NID.
- Under Pager", choose "Display a specified number of items" with "1 item".
- Choose "Unformatted list" under the Format.
- Add a multi-value Image, Media or Field collection field.
- Click the field under the Fields, choose "Slick carousel" under Formatter.
- Adjust the settings.
- Make sure to Display "all" or any number > 1 under "Multiple Field settings".
- Check "Use field template" under "Style settings", otherwise no field visible.

Themeing is mostly taken care of by slick in terms of layout, with the goodness
of Views to provide better markups manually.

Example use case: individual front or inner slideshow based on the entity ID.

GOTCHAS

If you are choosing a single multi-value field (such as images, media files, or
field collection fields) rather displaying various fields from multiple nodes,
make sure to:
- Choose a "Unformatted list" Format, not "Slick carousel".
- Choose "Slick carousel" for that field when configuring the field instead.
- Check "Use field template" under "Style Settings"so that the Slick field
  themeing is picked-up.
