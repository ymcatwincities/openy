# Check redirects in testing

Scenarios, which will use steps for checking the redirect, should be tagged with `@redirect` tag.

```gherkin
Scenario: Form submission
  And I fill "First name" with "Testfirstname"
  When I press on "Submit"
  Then I should not see the heading "Error"
  And should not see text matching "The website encountered an unexpected error"
  And should see no errors
  Then I am on the "https://login.salesforce.com/" page
  And fill the following:
    | username | salesforce@email.com |
    | password | salesforcepassword   |
  When I press on "Login"
  Then I should be redirected on "https://emea.salesforce.com/home/home.jsp"
  When I click on "Web Integrations"
  Then I should see text matching "Testfirstname"
```
