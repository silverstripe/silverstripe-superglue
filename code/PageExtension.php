<?php

namespace SilverStripe\SuperGlue;

use ArrayData;
use ClassInfo;
use Controller;
use DataExtension;
use DropdownField;
use FieldList;
use GridField;
use GridFieldDataColumns;
use HTMLText;
use ManyManyList;
use PaginatedList;
use Requirements;
use SiteTree;
use Tab;
use TextField;

class PageExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = array(
        "SuperGlueConnector" => "Varchar(255)",
        "SuperGluePinLimit" => "Int",
        "SuperGlueFirstPageLimit" => "Int",
        "SuperGluePageLimit" => "Int",
    );

    /**
     * @var array
     */
    private static $defaults = array(
        "SuperGluePinLimit" => 5,
        "SuperGlueFirstPageLimit" => 9,
        "SuperGluePageLimit" => 9,
    );

    /**
     * @var array
     */
    private static $many_many = array(
        "SuperGlueSubPages" => "SiteTree",
    );

    /**
     * @var array
     */
    private static $many_many_extraFields = array(
        "SuperGlueSubPages" => array(
            "SuperGlueSort" => "Int",
            "SuperGluePinned" => "Boolean",
        ),
    );

    /**
     * @inheritdoc
     *
     * @param FieldList $fields
     */
    public function updateSettingsFields(FieldList $fields)
    {
        $connectors = array("" => "(none)");
        $implementors = ClassInfo::implementorsOf("SilverStripe\\SuperGlue\\Connector");

        foreach ($implementors as $implementor) {
            /** @var Connector $instance */
            $instance = new $implementor();

            $connectors[$implementor] = $instance->getTitle();
        }

        /** @var DropdownField $connector */
        $connector = DropdownField::create(
            "SuperGlueConnector",
            _t("SuperGlue.PageExtension.SettingsOptions.Connector.Title", "Connected items type"),
            $connectors
        );

        $connector->setDescription(
            _t("SuperGlue.PageExtension.SettingsOptions.Connector.Description", trim("
                By selecting a page type, you allow pages of that type to be linked to this one, so
                that they can be displayed in summary.<br />When a page type is selected, a new tab
                will appear in the CMS, allowing you to link pages of that type, sort and pin them.
            "))
        );

        /** @var TextField $pinLimit */
        $pinLimit = TextField::create(
            "SuperGluePinLimit",
            _t("SuperGlue.PageExtension.SettingsOptions.PinLimit.Title", "Connected items pin limit")
        );

        $pinLimit->setDescription(
            _t("SuperGlue.PageExtension.SettingsOptions.PinLimit.Description", trim("
                This is the number of connected pages you will allow to be pinned.<br />New,
                unpinned, connected pages will be given a sorting value just below pinned pages,
                <br />causing them to be displayed after the pinned items but before older items
                with a lower sorting value.
            "))
        );

        $pinLimit->setAttribute("type", "number");

        /** @var TextField $firstPageLimit */
        $firstPageLimit = TextField::create(
            "SuperGlueFirstPageLimit",
            _t("SuperGlue.PageExtension.SettingsOptions.SuperGlueFirstPageLimit.Title", "Connected items on first page")
        );

        $firstPageLimit->setDescription(
            _t("SuperGlue.PageExtension.SettingsOptions.SuperGlueFirstPageLimit.Description", trim("
                This is the number of connected pages that will display on the first page.
            "))
        );

        $firstPageLimit->setAttribute("type", "number");

        /** @var TextField $pageLimit */
        $pageLimit = TextField::create(
            "SuperGluePageLimit",
            _t("SuperGlue.PageExtension.SettingsOptions.PageLimit.Title", "Connected items per page")
        );

        $pageLimit->setDescription(
            _t("SuperGlue.PageExtension.SettingsOptions.PageLimit.Description", trim("
                This is the number of connected pages that will display per paginated page.
            "))
        );

        $pageLimit->setAttribute("type", "number");

        $fields->addFieldToTab("Root.Settings", $connector);
        $fields->addFieldToTab("Root.Settings", $pinLimit);
        $fields->addFieldToTab("Root.Settings", $firstPageLimit);
        $fields->addFieldToTab("Root.Settings", $pageLimit);
    }

    /**
     * @inheritdoc
     *
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);

        Requirements::customScript("
            var index = 0;
            var modified = [];
            var path = 'tabs-admin/pages/edit/show/" . $this->owner->ID . "';
            var items = JSON.parse(sessionStorage.getItem(path));

            var interval  = setInterval(function() {
                var tabs = jQuery('.ui-tabs-nav li:has(#tab-Root_ConnectedPages)');

                if (tabs.length) {
                    index = tabs.index();

                    if (items && items.length) {
                        for (var i = 0; i < items.length; i++) {
                            if (items[i].id == 'Root') {
                                modified.push({
                                    'id': 'Root',
                                    'selected': index
                                });
                            } else {
                                modified.push(item);
                            }
                        }
                    } else {
                        modified.push({
                            'id': 'Root',
                            'selected': index
                        });
                    }

                    sessionStorage.setItem(path, JSON.stringify(modified));

                    clearTimeout(interval);
                }
            }, 50);
        ");

        $this->setRelations();

        if ($connector = $this->owner->SuperGlueConnector) {
            /** @var Connector $connector */
            $connector = new $connector();

            $tab = new Tab("ConnectedPages", "Connected Pages");

            $pinnedGrid = new GridField(
                "PinnedConnectedPagesGridField",
                "Pinned Pages",
                $this->owner->SuperGlueSubPages()->filter("SuperGluePinned", true)->sort("SuperGlueSort", "DESC")
            );

            $pinnedGrid->getConfig()->addComponents(
                (new GridFieldOrderableRows())->setSortField("SuperGlueSort"),
                new UnpinGridFieldActionProvider()
            );

            if (method_exists($connector, "getGridFieldDisplayFields")) {
                $displayFields = $connector->getGridFieldDisplayFields($this->owner);

                /** @var GridFieldDataColumns $dataColumns */
                $dataColumns = $pinnedGrid->getConfig()->getComponentByType("GridFieldDataColumns");

                $dataColumns->setDisplayFields($displayFields);
            }

            $tab->Fields()->add($pinnedGrid);

            $normalGrid = new GridField(
                "NormalConnectedPagesGridField",
                "Normal Pages",
                $this->owner->SuperGlueSubPages()->filter("SuperGluePinned", false)->sort("SuperGlueSort", "DESC")
            );

            $normalGrid->getConfig()->addComponents(
                (new GridFieldOrderableRows())->setSortField("SuperGlueSort"),
                new PinGridFieldActionProvider($this->owner->SuperGluePinLimit, $this->owner->SuperGlueSubPages()->filter("SuperGluePinned", true)->count())
            );

            if (method_exists($connector, "getGridFieldDisplayFields")) {
                $displayFields = $connector->getGridFieldDisplayFields($this->owner);

                /** @var GridFieldDataColumns $dataColumns */
                $dataColumns = $normalGrid->getConfig()->getComponentByType("GridFieldDataColumns");

                $dataColumns->setDisplayFields($displayFields);
            }

            $tab->Fields()->add($normalGrid);

            $fields->addFieldToTab("Root", $tab);
        }
    }

    /**
     * @inheritdoc
     */
    private function setRelations()
    {
        if ($class = $this->owner->SuperGlueConnector) {
            /** @var SiteTree $owner */
            $owner = $this->owner;

            /** @var Connector $connector */
            $connector = new $class();

            /** @var ManyManyList $relatedList */
            $relatedList = $owner->SuperGlueSubPages();

            $potentialList = $connector->getDataList($owner);
            $potentialIds = $potentialList->column("ID");
            $relatedIds = $relatedList->column("ID");

            // add unlinked, potential objects

            foreach ($potentialIds as $potentialId) {
                if (in_array($potentialId, $relatedIds)) {
                    continue;
                }

                $relatedList->add($potentialList->byID($potentialId), array("SuperGlueSort" => $relatedList->min("SuperGlueSort") - 1));
            }

            // remove linked, non-potential objects

            foreach ($relatedIds as $relatedId) {
                if (in_array($relatedId, $potentialIds)) {
                    continue;
                }

                $relatedList->remove($relatedList->byID($relatedId));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function onAfterWrite()
    {
        $this->setRelations();
    }

    /**
     * @return PaginatedList
     */
    public function SuperGlueViewSubPages()
    {
        $list = $this->owner->SuperGlueSubPages()
            ->Sort(array(
                "SuperGluePinned" => "DESC",
                "SuperGlueSort" => "ASC",
            ));

        $controller = Controller::curr();

        $paginated = new PaginatedList($list, $controller->getRequest());
        $paginated->setLimitItems(true);

        if ((int) $paginated->getRequest()->getVar("start") < $this->owner->SuperGlueFirstPageLimit) {
            $paginated->setPageLength($this->owner->SuperGlueFirstPageLimit);
        } else {
            $paginated->setPageLength($this->owner->SuperGluePageLimit);
        }

        return $paginated;
    }

    /**
     * @return HTMLText
     */
    public function SuperGlueView()
    {
        $connector = $this->owner->SuperGlueConnector;

        /** @var Connector $connector */
        $connector = new $connector();

        /** @var SiteTree $owner */
        $owner = $this->owner;

        $data = new ArrayData(array(
            "Parent" => $this->owner
        ));

        return $data->renderWith($connector->getTemplate($owner));
    }

    /**
     * @return string
     */
    public function LoadMoreLink()
    {
        $controller = Controller::curr();

        return Controller::join_links($controller->Link(), "LoadMore");
    }
}
