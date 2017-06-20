# Class Sessions

This is dynamic paragraph that renders the class session instances, based on url query parameter location with a valid id.

Relates to [Branches Popup (Class)](Branches Popup (Class).md).

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Block | field\_prgf_block | Yes | Block reference to the view/block. Should have default value and should be hidden in form display. |

### Displayed table
* Location(should be displayed in case if &location=% not in the URL. Otherwise should be hiddedn.
* Time + date
* Registration(link)
* Details
  * Online registration
  * Ticket required
  * In membership
* Age Min - Max

### Use cases
*Use case 3: Class page WITHOUT location popup*
3.1 Location in specified URL
- When I open Class page WITHOUT location popup on page
- And I have location=% in the URL
- We skip cookie whether is empty or exist
- Results should be filtered based on location from URL
- In sidebar we should see location teaser

3.2 Preferred branch is empty and no location in URL or Preferred branch is selected and no location in URL
- When I open Class page WITHOUT location popup on page
- And I don't have a preferred branch
- Or I have a preferred branch
- And I don't have location=% in the URL
- Results should contain all branches
- In sidebar we should see "All locations...."

*Use case 4: Class page WITH location popup*
4.1 Location in specified URL
- When I open Class page WITH location popup on page
- And I have location=% in the URL
- We skip cookie whether is empty or exist
- Results should be filtered based on location from URL
- In sidebar we should see location teaser
- Location is sidebar should have "Edit" link that will open location popup

4.2 Preferred branch is empty and no location in URL or Preferred branch is selected and no location in URL
- When I open Class page WITH location popup on page
- And I don't have a preferred branch
- Or I have a preferred branch
- And I don't have location=% in the URL
- Results should contain all branches
- In sidebar we should see "All locations...."
- Location popup should be shown (Unless only one location is associated with the class)
