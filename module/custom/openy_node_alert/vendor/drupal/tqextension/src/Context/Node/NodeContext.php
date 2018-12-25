<?php
/**
 * @author Sergii Bondarenko, <sb@firstvector.org>
 */
namespace Drupal\TqExtension\Context\Node;

class NodeContext extends RawNodeContext
{
    /**
     * @param string $operation
     *   Allowable values: "edit", "view", "visit".
     * @param string $nid
     *   Node ID or inaccurate title.
     * @param string $contentType
     *   Content type, for additional filter of a search query.
     *
     * @When /^(?:|I )(visit|view|edit) (?:the "([^"]+)"|current) node(?:| of type "([^"]+)")$/
     */
    public function visitPage($operation, $nid = '', $contentType = '')
    {
        // This helps us restrict an access for editing for users without correct permissions.
        // Will check for 403 HTTP status code.
        $this->getRedirectContext()
            ->visitPage($this->entityUrl($operation, $nid, $contentType));
    }
}
