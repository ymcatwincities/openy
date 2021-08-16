# Welcome to Open Y smoke tests documentation

In order for Open Y dependencies being tested in a short timeframe, please follow steps below

# Webforms

## Check webform module

### User

Administrator

### Steps

*Note: Webform is a very big contributed module that providess a lot of features. We need to check at least basic functions. *

1. Login as admin
2. Go to Structure -> Webforms (/admin/structure/webform)
3. Verify you see a st of pages provided via module (Forms, Submissions, Options, Configuration, Add-ons, etc)
4. Verify by defalt Open Y has one webform called ""Contact"" with a status Open
5. Verify you can click on the webform title and see the form as independent page (ignore styles absence)
6. Verify you can open the following pages for a specific webform (Results, Settings). 
7. Verify there are no errors while visiting webform related pages. 

### Expected Results

1. Module webform is installed 
2. Pages for creating webforms works find and there are no errors
3. There is a default webform called ""Contact""
4. There are no errors while visiting specific webform pages

## Creating webform

### User

Administrator

### Steps

1. Login as admin
2. Go to Structure -> Webforms (/admin/structure/webform)
3. Click on the button ""Add webform""
4. Enter title, description and choose status Open
5. Verify you were redirected to the Build page
6. TO CONTINUE WEBFORM UI MODULE SHOULD BE INSTALLED
7. Make sure you are on the Elements tab
7. Verify there are buttons such as ""Add element / Add Page / Add layout""
8. Try adding 2-3 new elements 
10. Save the form 
11. Go to View tab 
12. Verify added elements rendered correctly. 

### Expected results

1. Administrator can create webforms 
2. Webform module provideds needed functionality to create webform and add different elements 

## Check Open Y Webform paragraph

### User

Administrator

### Steps

*Note: Open Y does not use webform pages as is insted it provides a paragraph that gives the possiblity insert webform as ablock to any page. *

1. Login as admin
2. Go to the Landing page creating form (Content -> Add Content -> Landing page)
3. Open Content Area
4. In the list of paragraphs find Webform component. 
5. Add it
6. Verify paragraph form inserted
7. Verify there is a dropdown with the possiblity select webform (default one -> Contact) 
8. Verify there are no errors while saving landing page 
9. Verify you can see webform rendered on the page  

### Expected results

1. There is a paragraph that gives the possiblity add webform to any page 
2. Paragraph is working without any errors
3. Webform renders on the page after saving

## Check webforms availability

### User

Administrator / Anonymous

### Steps

1. Login as admin
2. Create a new Landing page 
3. Add webform via paragraph 
4. Save the page. 
5. Log out 
6. Open just created page 
7. Verify you can see the webform
8. Verify you can use the webform and it works properly 

### Expected results

1. Anonymous users can access the webform added to any page (landing page in the example)
2. Anonymous users can use the webform without any issues

## Check form submissions 

### User

Administrator / Anonymous

### Steps

1. Login as admin
2. Create a new Landing page 
3. Add webform via paragraph 
4. Save the page. 
5. Log out 

6. Open just created page 
7. Submit the form 2-3 times 

8. Login as admin
9. Go to Structure -> Webforms (/admin/structure/webform)
10. Verify the webform that was used has 2-3 results 
11. Click on the results 
12. Verify you can see submissions and the number of them is correct
13. Check submissions and verify they contain correct data (data that was used previously)


### Expected results

1. Anonymous user can submit the form 
2. All results submitted via the form are present in the admin interface and contains correct data (submitted previously)

## Email notifications  

### User

Administrator

### Steps

1. Login as admin
2. Go to Structure -> Webforms (/admin/structure/webform)
3. Choose Contact webform 
4. Go to Settings -> Emails / Handlers
5. Add a new Email
6. Enter your email in the Send To -> To email -> Custom To email address
7. Save the form 
8. Go to View tab
9. Submit the form 
10. Verify your inbox there should be aconfirmation email


### Expected results

1. There are settings to configure who should receive email confirmation 
2. Forms for adding settings work correctnly without any issues
3. Emails are sending  after form submission
