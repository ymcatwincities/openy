# Session
Session content type is used for adding Sessions on the site.

### Fields
| Name  | Machine name | Required | Description |
| ------------- | ------------- | ------------- | ------------- |
| Title  | drupal's default  | Yes | Title of the session item. |
| Class  | field\_session_class  | Yes | A reference field for selecting the program subcategory. |
| **Session Info** | Field group |-|-|
| Description | field\_session_description | No | Textarea for the description/body with WYSIWYG, without summary. |
| Gender | field\_session_gender  | No | Select List with Gender options: Coed, Male, Female. |
| Online registration | field\_session\_online  | No | Boolean field that determines if the Register Now button/link gets displayed. |
| Ticket required | field\_session\_ticket  | No | Checkbox field to indicate that there is a ticket required. |
| Min Age | field\_session\_min_age  | No | Input field for adding the min age. |
| Max Age | field\_session\_max_age  | No | Input field for adding the max age. |
| Registration link | field\_session\_reg_link  | No | A link field with the Registration link Value. |
| **Membership** | Field group |-|-|
| In membership | field\_session\_in_mbrsh  | No | Boolean field that helps determine if the session is included into membership package. |
| Member price | field\_session\_mbr_price  | No | Input with with the price information for members. |
| Non Member Price | field\_session\_nmbr_price  | No | Input with with the price information for members. |
| **Location** | Field group |-|-|
| Location  | field\_session\_location  | Yes | A reference field for selecting the branch or camp. |
| Physical Location | field\_session\_plocation  | No | A reference field for selecting the facility. |
| **Time** | Field group |-|-|
| Exclusions | field\_session_exclusions | No | A date field that identifies dates that would normally have an instance of the session but won’t. Needs to be able to have multiple exclusions. Supports multiple values. Should be handled by a single date field with 'end date' option enabled. Its widget should be adjust to not to show period end date, but show period end time (to keep period start/end date equal). |
| **Time** | field\_session_time | Paragraph | Session schedule. |
| Date & Time  | field\_session\_time_date  | No | This will use Drupal date/time fields & should be a single date field with 'end date' and 'end time' option enabled. |
| Days  | field\_session\_time_days  | No | Checkboxes with following values: <ul><li>sunday\|Sunday</li><li>monday\|Monday</li><li>tuesday\|Tuesday</li><li>wednesday\|Wednesday</li><li>thursday\|Thursday</li><li>friday\|Friday</li><li>saturday\|Saturday</li></ul> Should support multiple values. |

### URL pattern
No URL pattern. Eventually this content type shouldn't be available for end users.
