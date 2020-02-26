<?php

/**
* @file
* Hooks provided by the Paragraph Skins module.
*/

/**
* @addtogroup hooks
* @{
*/

/**
* Alter skin.
*
* @param array $skins
*   Skins definitions, keyed by plugin ID.
*/
function hook_paragraph_skins_alter(array &$skins) {
  $skins['default']['label'] = 'Altered label';
}

/**
* Alter skins groups.
*
* @param array $skin_groups
*   Skin group definitions, keyed by plugin ID.
*/
function hook_paragraph_skin_groups_alter(array &$skin_groups) {
  $skin_groups['default']['label'] = 'Altered label';
}

/**
* @} End of "addtogroup hooks".
*/
