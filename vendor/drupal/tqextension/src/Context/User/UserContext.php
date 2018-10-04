<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\User;

// Helpers.
use Behat\Gherkin\Node\TableNode;

class UserContext extends RawUserContext
{
    /**
     * @param string $operation
     *   Allowable values: "edit", "view", "visit".
     * @param string $value
     *   The name or email of a user.
     *
     * @When /^(?:|I )(visit|view|edit) (?:the "([^"]+)"|current) user$/
     */
    public function visitPage($operation, $value = '')
    {
        $column = $value;

        // If we have an empty value then try to use current user.
        if ('' !== $value) {
            $column = strpos($value, '@') === false ? 'name' : 'mail';
        }

        // This helps us restrict an access for editing for users without correct permissions.
        // Will check for 403 HTTP status code.
        $this->getRedirectContext()
            // Check user by email if value contains a "dog" symbol.
            ->visitPage($this->entityUrl($operation, $column, $value));
    }

    /**
     * @example
     * Then I am logged in as a user with "CRM Client" role and filled fields
     *   | Full name                | Sergii Bondarenko |
     *   | Position                 | Developer         |
     *   | field_crm_user_company   | FFW               |
     *
     * @param string $roles
     *   User roles, separated by comma.
     * @param TableNode $fields
     *   | Field machine name or label | Value |
     *
     * @throws \EntityMetadataWrapperException
     *   When user object cannot be saved.
     * @throws \Exception
     *   When required fields are not filled.
     *
     * @Given /^(?:|I am )logged in as a user with "([^"]*)" role(?:|s)(?:| and filled fields:)$/
     */
    public function loginCreatedUser($roles, TableNode $fields = null)
    {
        $this->loginUser($this->createDrupalUser($roles, $fields));
    }

    /**
     * @see loginCreatedUser()
     *
     * @Then /^(?:|I )create a user with "([^"]*)" role(?:|s)(?:| and filled fields:)$/
     */
    public function createDrupalUser($roles, TableNode $fields = null)
    {
        return $this->createUserWithRoles($roles, null !== $fields ? $fields->getRowsHash() : []);
    }

    /**
     * @param TableNode $credentials
     *   | username | BR0kEN |
     *   | password | p4sswd |
     *
     * @throws \Exception
     *   When user cannot be authorized.
     *
     * @Given /^(?:|I )am logged in with credentials:/
     */
    public function loginWithCredentials(TableNode $credentials)
    {
        $this->fillLoginForm($credentials->getRowsHash());
    }

    /**
     * This step must be used instead of "I am an anonymous user" because it
     * has more strict checking for authenticated user.
     *
     * @Given /^I am unauthorized user$/
     * @Given /^I am log out$/
     */
    public function logoutDrupalUser()
    {
        $this->logoutUser();
    }

    /**
     * @AfterScenario
     */
    public function afterUserScenario()
    {
        // Logout, when scenario finished an execution, is required for "Scenario Outline" because an
        // object will not be instantiated for every iteration and user data, from previous one, will
        // be kept.
        $this->logoutUser();
    }
}
