Contributing to Open Y
=====

# Issues
If you have a support request, you've found a bug or you have a feature request:
- Search through known issues/requests [https://github.com/ymcatwincities/openy/issues](https://github.com/ymcatwincities/openy/issues)
- Create new issue [https://github.com/ymcatwincities/openy/issues/new](https://github.com/ymcatwincities/openy/issues/new)

# Pull Requests
If you have some time to make a contribution to the project, here are the steps that will help you:
- Create fork of [main project](https://github.com/ymcatwincities/openy). [How to create fork](https://help.github.com/articles/fork-a-repo/).
- Commit & push changes into your fork
- Create new Pull Request. [How to create Pull Request](https://help.github.com/articles/creating-a-pull-request/).
- Write steps for review. In this way maintainers can go through steps on build to verify your fix/feature.
- Ensure steps for review added to README.md file in a module's/project's directory if it makes sence to check them on regular basis. Often this is needed for crucial parts of the system which is main business functionality of the component. Example of super simple steps for review [see in Quickstart section of location_finder module](https://github.com/ymcatwincities/openy/blob/8.x-1.x/modules/custom/location_finder/README.md#quickstart), plese.
- Create Drupal tour module, based on steps for review and ship it with the module which provides a functionality.
- Wait for a CI build and ask maintainers for review.


**Important:** make sure your git email is associated with account on drupal.org, otherwise you won't get commits there.

# Drupal.org credits
If you would like to get drupal.org credits for your contribution:
- Create issue on [drupal.org](https://www.drupal.org/project/issues/openy?categories=All)
- Link drupal.org issue to GitHub Pull Request
- Specify in GitHub Pull Request link to drupal.org issue
- Once PR has been merged, reviewer will close drupal.org issue with appropriate credits.
