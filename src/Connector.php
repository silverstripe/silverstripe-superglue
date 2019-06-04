<?php

namespace SilverStripe\SuperGlue;

use SilverStripe\ORM\DataList;
use SilverStripe\CMS\Model\SiteTree;

interface Connector
{
    /**
     * Title to display, when toggling Super Glue for this Page.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Template this Page should include for each glued DataObject.
     *
     * @param SiteTree $page
     *
     * @return string
     */
    public function getTemplate(SiteTree $page);

    /**
     * Filtered DataList for DataObjects to link to this Page.
     *
     * @param SiteTree $page
     *
     * @return DataList
     */
    public function getDataList(SiteTree $page);
}
