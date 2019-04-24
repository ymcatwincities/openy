### Open Y Acceptance testing best practices

Open Y is a big distribution with a large amount of modules, components, subsystems, and business 
processes, therefore we have to take appropriate steps to ensure the stability of major functionality during development.

For the automated tests we have created [General Checks template](https://github.com/ymcatwincities/openy/blob/8.x-1.x/.github/PULL_REQUEST_TEMPLATE.md) 
on GitHub every developer should follow to get review approval from Open Y core team.
However, General Checks are for testing functionality for the current proposed change only, not for Regression Testing. 

For regression testing,  [Behat tests](https://github.com/ymcatwincities/openy/blob/8.x-1.x/docs/Development/Tests.md#behat) in this flow are provided automatically on each build by Open Y community.

Every pull request should include a testing plan prior to release into Open Y. This plan should cover the testing of all workflows and functionality to ensure that they continue to work with any new code or change implemented. This is because it is possible for conflicts to occur between elements of Open Y, Drupal Modules, and Drupal Core. These pull request testing plans will increase productivity and decrease effort for manual Acceptance Testing of upcoming Releases. This testing plan should cover specific features and functionality that is likely to cause regression issues post-release or post-upgrade to the latest version of Open Y once this new code is implemented.

Example of testing plan: If the Drupal core is updated it is important to gather all Drupal core Release Notes since last release 
core upgrade for Open Y and analyze important issues fixed. 
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

This list could be extended by analyzing some highly important parts of Open Y distributions that depends from the above 
subsystems. It is not required to spend time on every module that has a dependency taxonomy, but it is important to test at least one impacted module to ensure it is still working post-implementation. In case if there is a Behat test already created for the subsystem in a list then a manual test could be skipped as long as the build is not failing due to the module or element covered in the associatied Behat test. 

How to choose which modules to test: These can be a random selection from the list of systems impacted, or one of the oldest modules in a system impacted. This is because  there is a higher chance that a minor change could cause a regression issue for the modules that have not received recent or regular updates. 

The oldest modules(contrib modules) that have dependencies from the above list should also be updated, but 
to improve productivity, these updates should only be initiated  if there is a security issue or a module stopped working because of 
the subsystems getting updated within an upcoming release. In case if a respective module update creates more issues 
that the older version of said module - then it is better to keep the old module and fix an associated regression bug.
Tip: usually a new version of the module already contains a bug fix, so adding a patch from the drupal.org to composer.json of the Open Y distribution is preffered to get distribution 
released. Keep in mind, you will need to create a follow-up task for the module to be updated after release.

After creating list of modules that could introduce regression issues it is highly recommended to follow 
Quickstart section of the module's readme files, that usually is shipped with modules. [Example for the location_finder](https://github.com/ymcatwincities/openy/blob/8.x-1.x/modules/custom/location_finder/README.md#quickstart).
In case if a module has no Quickstart of Acceptance testing section in readme - it is important to test at least one place 
where functionality of the module should be working. It is highly recommended to add this manual test steps as 
a follow-up task, [new issue](https://github.com/ymcatwincities/openy/issues/new) or even better - create Pull Request 
with changes to readme into Open Y repository. For the sake of performance, adding step by step how-to to the respective 
module's README.md file is highly recommended. It takes only a few minutes to write a couple of lines of documentation which will greatly help others with future contributions and changes.

Optional, but grately appreciated: Add a Drupal tour for the how-to, created in README will benefit future Open Y users and developers.
Having a tour for the business functionality is highly recommended to ship with the component - it creates an in-site visual guided documentation and helps to decrease time for the Acceptance testing.

And last, but not the lease - adding Behat tests to the system will ensure functionality is tested on every pull 
request, on every CI build in the future.

### Rule
Every release of Open Y since 8.1.9 should include list of subsystems, changed in release for the community to be aware of the possible regressions on their end.
