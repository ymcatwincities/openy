<?php

namespace Drupal\ymca_page_manager\Entity;

use Drupal\page_manager\PageInterface;
use Drupal\page_manager\Entity\Page as PageDefault;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

class Page extends PageDefault implements PageInterface, ThirdPartySettingsInterface {

}
