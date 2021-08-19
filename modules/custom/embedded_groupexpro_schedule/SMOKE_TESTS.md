# Welcome to Group Ex Pro integration smoke tests documentation

In order for Open Y Group Ex Pro integration being tested in a short timeframe, please follow steps below

# Component: Embedded GroupEx Pro Schedule						

## Adding embeded GroupEx Pro schedule to a page

### User

Administrator

### Steps

1. Login as Admin 
2. Go to Content page -> Add Content
3. Create a new landing page
4. In the Content area verify you can add a paragraph called ""Embedded GroupEx Pro Schedule""
5. Verify paragraph has no settings while adding it to the page
6. Verfiy there are no errors and page created successfully using paragraph
8. Verify you can edit the page, edit paragraph, without any issues

### Expected Results

1. There is paragraph called ""Embedded GroupEx Pro Schedule""
2. Paragrap has no settings
3. There are no errors while using paragraph

## Check DEMO page with embeded GroupEx Pro schedule

### User

Administrator

### Steps

1. Login as Admin 
2. Go to Content page
3. Check the following pages, they should contain ""Embedded GroupEx Pro Schedule"" paragraph in the Content area:
- Group Schedules (Embedded) /schedules/group-schedules-embedded
4. Verify embeded schedule renders wihtout any errors and it can be used (all filters and links work)

### Expected Results

1. The following demo pages contain ""Embedded GroupEx Pro Schedule"" paragraph in the Content area:
- Group Schedules (Embedded) /schedules/group-schedules-embedded
2. Embeded schedule renders wihtout any errors

## Integration configuration 

### User

Administrator

### Steps

1. Login as Admin 
2. Go to Extend (/admin/modules)
3. Install modules ""OpenY PEF GXP Sync"" and ""Open Y Mappings Feature""  and all related (asked on the next step)
4. Go to Open Y -> Integrations -> GroupEx Pro -> GroupEx Pro settings (/admin/openy/integrations/groupex-pro/gxp)
5. Open URL https://www.groupexpro.com/gxp/api/openy/view/36/204 and verify there is a json response
5. Enter Client Id = 36 
6. Enter Activity = Group Exercise Classes (choose node of type Activity) Most likely ID will be 94. 
7. Enter Locations Mapping: 
202,West YMCA
204,Downtown YMCA
203,East YMCA
3718,South YMCA
8. Save configuration 
9. Verify settings saved correctly.
10. Go to the page Configuration -> System -> YMCA Sync settings (/admin/config/system/ymca-sync)
11. Enable checkbox with the label ""openy_pef_gxp_sync"" and Save configuration
12. Go to the page Open Y -> Settings -> Mappings -> Mapping list (/admin/openy/settings/mappings/mapping)
13. Add mappings for every branch you would like to synchronize: 
- Enter the name of the mapping to easily identify it in the future, for instance, ""West YMCA GXP sync mapping""
- Authored by keep as is
- Locations - choose Branch
- Groupex ID - Enter GroupEx ID
- Save
14. Verify there are no issues while adding mappings

### Expected Results

1. There is a page with a form to configure GroupEx Pro integration 
2. Test GroupEx Pro account with ID 36 exist and accessible
3. Mapping can be configured without any issues

## Check Integration syncer command

### User

Administrator

### Steps

Precondition: Execute test case above. 
1. Verify GroupEx Pro itegration settings are saved on the page Open Y -> Integrations -> GroupEx Pro -> GroupEx Pro settings (/admin/openy/integrations/groupex-pro/gxp)
2. Run Drush command ""drush openy-pef-gxp-sync"" from your project docroot
3. Verify command executed successfully

### Expected Results

1. Drush command sync classes from GroupEx Pro into PEF

## Check results of syncronization 

### User

Administrator / Anonymous

### Steps

Precondition: Execute test case above.
1. Go to the page Group Excercise Classes (/group-exercise-classes)
2. Verify ""Repeat Schedule"" paragraph added on the page 
3. Edit ""Repeat Schedule"" paragraph and add new categories like Yoga, Cardio/Strength, etc. 
4. Save and publish 
5. Verify there are new classes in the list of results synced from the GroupEx Pro on the Schedules page
- Yoga between August 17, 2020 and March 31, 2021 in the Downtown YMCA branch
- Body Pump between August 26, 2020 and August 26, 2037 in the Downtown YMCA branch
- Tabata Express between December 29, 2020 and January 26, 2021 in the Downtown YMCA branch
- Indoor Cycling between June 17, 2020 and June 17, 2037 in the West YMCA
- Hot Yoga between July 27, 2020 and July 27, 2037 in the West YMCA

### Expected Results

1. On the Schedules page there are classes from the GroupEx Pro
