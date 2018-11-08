This module creates API endpoints for ActiveNet. After enabling the module, you should be able to input the config into the admin and use the ActiveNetClient class.

1. Enter ActiveNet creds at admin/openy/integrations/activenet/settings
2. In your theme.theme file or a custom module:

```
use Drupal\activenet\ActiveNetClient;
$client = new $ActiveNetClient();
$centers =  $ActiveNetClient->getCenters();
print_r($centers); //returns json response of all centers

```
All available methods:
```
getSites()
getCenters()
getActivities()
getActivityTypes()
getActivityCategories()
getActivityOtherCategories()
getFlexRegPrograms()
getFlexRegProgramTypes()
getMembershipPackages()
getMembershipCategories()
getActivityDetail($id)
```
