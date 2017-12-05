# Schedule search list

This is dynamic paragraph that renders the session instances, based on url parameters, and or filters from [Schedule search form](Schedule search form.md).

Relates to [Branches Popup (All)](Branches Popup (All).md).

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Block | field\_prgf_block | Yes | Block reference to the view/block. Should have default value and should be hidden in form display. |

### Use cases
*Use case 1: Schedule search list paragraph on a page WITHOUT location popup paragraph*
1.1 Preferred branch is selected and no location in URL
- When I open Schedule search list page WITHOUT location popup on page
- And I have a preferred branch
- And I don't have location=% in the URL
- Filter by location should be predefined based on cookie
- Results should be filtered

1.2 Preferred branch is empty and no location in URL
- When I open Schedule search list page WITHOUT location popup on page
- And I don't have a preferred branch
- And I don't have location=% in the URL
- Filter by location should show "All"
- Results should be shown for all branches

1.3 Location in specified URL
- When I open Schedule search list page WITHOUT location popup on page
- And I have location=% in the URL
- We skip cookie whether is empty or exist
- Filter by location should show branch from URL
- Results should be filtered

*Use case 2: Schedule search list paragraph on a page WITH location popup*
2.1 Preferred branch is selected and no location in URL
- When I open Schedule search list page WITH location popup on page
- And I have a preferred branch
- And I don't have location=% in the URL
- Location popup shouldn't be shown
- Filter by location should be predefined based on cookie
- Results should be filtered

2.2 Preferred branch is empty and no location in URL
- When I open Schedule search list page WITH location popup on page
- And I don't have a preferred branch
- And I don't have location=% in the URL
- Filter by location should show "All"
- Results should be shown for all branches
- Location popup should be shown

2.3 Location in specified URL
- When I open Schedule search list page WITH location popup on page
- And I have location=% in the URL
- We skip cookie whether is empty or exist
- Location popup shouldn't be shown
- Filter by location should show branch from URL
- Results should be filtered
