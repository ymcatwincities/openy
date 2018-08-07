<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\User;

// Contexts.
use Drupal\TqExtension\Context\RawTqContext;
// Utils.
use Drupal\TqExtension\Utils\BaseEntity;
use Drupal\TqExtension\Utils\EntityDrupalWrapper;
use Drupal\TqExtension\Utils\Database\FetchField;
use Drupal\TqExtension\Cores\DrupalKernelPlaceholder;

class RawUserContext extends RawTqContext
{
    use BaseEntity;

    /**
     * {@inheritdoc}
     */
    protected function entityType()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentId()
    {
        $currentUser = $this
          ->getUserManager()
          ->getCurrentUser();

        return empty($currentUser->uid) ? 0 : $currentUser->uid;
    }

    /**
     * @param string $column
     *   Column of a "users" table.
     * @param string $value
     *   Expected value in column.
     *
     * @return int
     */
    public function getIdByArguments($column, $value)
    {
        return (new FetchField('users', 'uid'))
            ->condition($column, $value)
            ->execute();
    }

    /**
     * @param string $roles
     *   Necessary user roles separated by comma.
     * @param array $fields
     *
     * @return \stdClass
     */
    public function createUserWithRoles($roles, array $fields = [])
    {
        $user = $this->createTestUser($fields);
        $driver = $this->getDriver();

        foreach (array_map('trim', explode(',', $roles)) as $role) {
            $driver->userAddRole($user, $role);
        }

        return $user;
    }

    /**
     * @throws \Exception
     */
    public function loginUser(\stdClass $user)
    {
        $this->logoutUser();

        $this->fillLoginForm([
            'username' => $user->name,
            'password' => $user->pass,
        ]);
    }

    /**
     * @param array $props
     *   An array with two keys: "username" and "password". Both of them are required.
     * @param string $message
     *   An error message, that will be thrown when user cannot be authenticated.
     *
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     *   When one of a fields cannot be not found.
     * @throws \Exception
     *   When login process failed.
     * @throws \WebDriver\Exception\NoSuchElement
     *   When log in button cannot be found.
     */
    public function fillLoginForm(array $props, $message = '')
    {
        $this->getRedirectContext()->visitPage('user/login');
        $formContext = $this->getFormContext();

        foreach (['username', 'password'] as $prop) {
            $formContext->fillField($this->getDrupalText($prop . '_field'), $props[$prop]);
        }

        $this->getWorkingElement()->pressButton($this->getDrupalText('log_in'));

        if (!$this->isLoggedIn()) {
            throw new \Exception($message ?: sprintf(
                'Failed to login as a user "%s" with password "%s".',
                $props['username'],
                $props['password']
            ));
        }

        $account = user_load_by_name($props['username']);
        $this
          ->getUserManager()
          ->setCurrentUser($account);

        DrupalKernelPlaceholder::setCurrentUser($account);
    }

    /**
     * Cookies are set when at least one page of the site has been visited. This
     * action done in "beforeScenario" hook of TqContext.
     *
     * @see TqContext::beforeScenario()
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        $cookieName = session_name();
        $baseUrl = parse_url($this->locatePath());

        // When "base_url" has "https" scheme, then secure cookie (SSESS) will be set to
        // mark authentication. "session_name()" will return unsecure name (SESS) since it
        // run programmatically. Correct a cookie name if Behat configured for using secure
        // URL and name is not "secure".
        // "scheme" may not exist in case if, for instance, "127.0.0.1:1234" used as base URL.
        if (strpos($cookieName, 'SS') !== 0 && isset($baseUrl['scheme']) && 'https' === $baseUrl['scheme']) {
            $cookieName = "S$cookieName";
        }

        $cookie = $this->getSession()->getCookie($cookieName);

        if (null !== $cookie) {
            // "goutte" session using for checking HTTP status codes.
            $this->getSession('goutte')->setCookie($cookieName, $cookie);

            return true;
        }

        return false;
    }

    public function logoutUser()
    {
        if ($this->isLoggedIn()) {
            $this->logout();
        }
    }

    /**
     * @param array $fields
     *   Additional data for user account.
     *
     * @throws \Exception
     *
     * @return \stdClass
     */
    public function createTestUser(array $fields = [])
    {
        $random = $this->getRandom();
        $username = $random->name(8);
        $user = [
            'name' => $username,
            'pass' => $random->name(16),
            'mail' => "$username@example.com",
            'roles' => [
                DRUPAL_AUTHENTICATED_RID => 'authenticated user',
            ],
        ];

        if (!empty($fields)) {
            $entity = new EntityDrupalWrapper('user');
            // Remove fields such as "name", "pass", "mail" if they are required.
            $required = array_diff_key($entity->getRequiredFields(), $user);

            // Fill fields. Field can be found by name or label.
            foreach ($fields as $fieldName => $value) {
                $fieldName = $entity->getFieldNameByLocator($fieldName);

                $user[$fieldName] = $value;
                // Remove field from $required if it was there and filled.
                unset($required[$fieldName]);
            }

            // Throw an exception when one of required fields was not filled.
            if (!empty($required)) {
                throw new \Exception(sprintf(
                    'The following fields "%s" are required and has not filled.',
                    implode('", "', $required)
                ));
            }
        }

        $user = (object) $user;
        $userId = DrupalKernelPlaceholder::getUidByName($user->name);
        $userManager = $this->getUserManager();
        $currentUser = $userManager->getCurrentUser();

        // User is already exists, remove it to create again.
        if ($userId > 0) {
            DrupalKernelPlaceholder::deleteUser($userId);
        }

        // $currentUser always exist but when no user created it has "false" as a value.
        // Variable stored to another because RawDrupalContext::userCreate() will modify
        // it and this will affect for future actions.
        if (!empty($currentUser)) {
            $tmp = $currentUser;
        }

        $this->userCreate($user);

        if (isset($tmp)) {
            $userManager->setCurrentUser($tmp);
        }

        return $user;
    }
}
