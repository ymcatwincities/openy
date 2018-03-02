### OpenY Acceptance testing best practices

Due to the fact OpenY is a big distribution with a large amount of modules, components, subsystems, and business 
processes we have to ensure we are not braking major functionality during development.
For the automated tests we have created [General Checks template](https://github.com/ymcatwincities/openy/blob/8.x-1.x/.github/PULL_REQUEST_TEMPLATE.md) 
on GitHub every developer should follow to get review approval from OpenY core team.
But General Checks are for testing functionality for the current proposed change, not for Regression Testing. 
Only [Behat tests](https://github.com/ymcatwincities/openy/blob/8.x-1.x/docs/Development/Tests.md#behat) in this flow 
are about regression testing, provided automatically on each build by OpenY community.
For the release acceptance testing we need to ensure we aren't braking syste, somewhere on more global level. 
Usually this could happen during Drupal core upgrade or/and contrib modules upgrades if included into release of OpenY.
To increase productivity and decrease effort for manual Acceptance Testing of upcoming Release it is highly 
important to build a plan of testing prior releasing OpenY. This plan should include functionalities that possible to have 
regressions becuase of planned changes.
For example. If the Drupal core is updated it is important to gather all Drupal core Release Notes since last release 
core upgrade for OpenY and analyze important issues fixed. 
Example - in case if you are doing upgrade from latest 8.4.0 to 8.4.4:

 - https://www.drupal.org/project/drupal/releases/8.4.4 (translations)
 - https://www.drupal.org/project/drupal/releases/8.4.3 (postgreSQL and migration)
 - https://www.drupal.org/project/drupal/releases/8.4.2 (migrations, taxonomy, ckeditor)
 - https://www.drupal.org/project/drupal/releases/8.4.1 (composer)

This means the list of systems should be tested are
 - multilingual
 - postgreSQL support
 - migration
 - taxonomy
 - ckeditor
 - composer

This list could be extended by analyzing some highly important parts of OpenY distributions that depends from the above 
subsystems. No need to spend the time on every module that has in dependency taxonomy, but at least one needs to be tested 
if still working. In case if there is a Behat test, already created for the subsystem in a list, it could be skipped
if test is not failing on a build. 
How to choose one - could be random selection or one of the oldest modules in a system, because 
there is a higher chance minor change could case regression for the module that was not updated recently. 
It also makes sence to update oldest modules(contrib modules) that have dependencies from the above list, but 
to move faster update should be initiated only if there is a security issue or module stopped working because of 
the subsystems getting updated within an upcoming release. In case if respective module update creates more issues 
that its old version - it is better to keep old one with fixing a regression bug instead of fighting windmills 
with issues, introduced by new module version. Trick: usually new version of the module already contains a bug fix, 
so adding a patch from the drupal.org to composer.json of the OpenY distribution is preffered to get distribution 
released. And of cause, you need to create a follow-up task for the module to be updated after release.

After creating list of modules, that probably could introduce regressions it is highly recommended to follow 
Quickstart section of the module's readme files, that usually shipped with modules. [Example for the location_finder](https://github.com/ymcatwincities/openy/blob/8.x-1.x/modules/custom/location_finder/README.md#quickstart).
In case if module has no Quickstart of Acceptance testing section in readme - important to test at least one place 
where functionality of the module should be working. It is highly recommended to add this manual test steps as 
a follow-up task, [new issue](https://github.com/ymcatwincities/openy/issues/new) or even better - create Pull Request 
with changes to readme into OpenY repository. For the sake of performance adding step by step how-to to the respective 
module's README.md file is highly recommended. Usually it takes only 1-5 minutes to write a couple of line which 
will do help in the future a lot.

If there is a time to go deeper - adding Drupal tour for the how-to, created in README should help a lot in the future.
Having a tour for the business functionality is highly recommended to ship with the component, becuase it is win-win 
technology - it creates in-site visual guided documentation and helps to decrease time for the Acceptance testing.

And last, but not the lease - adding Behat tests to the system will do ensure functionality is tested on every pull 
request, on every CI build in the future.
