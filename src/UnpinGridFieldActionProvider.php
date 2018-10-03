<?php

namespace SilverStripe\SuperGlue;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\HTTPResponse;

class UnpinGridFieldActionProvider implements GridField_ColumnProvider, GridField_ActionProvider
{
    /**
     * @inheritdoc
     *
     * @param GridField $gridField
     * @param array $columns
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array("Actions", $columns)) {
            $columns[] = "Actions";
        }
    }

    /**
     * @inheritdoc
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     *
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return ["class" => "col-buttons"];
    }

    /**
     * @inheritdoc
     *
     * @param GridField $gridField
     * @param string $columnName
     *
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == "Actions") {
            return ["title" => ""];
        }
    }

    /**
     * @inheritdoc
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return ["Actions"];
    }

    /**
     * @inheritdoc
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     *
     * @return mixed
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        $field = GridField_FormAction::create(
            $gridField,
            "CustomAction" . $record->ID,
            "unpin",
            "unpin",
            ["ID" => $record->ID]
        );

        return $field->Field();
    }

    /**
     * @inheritdoc
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getActions($gridField)
    {
        return ["unpin"];
    }

    /**
     * @inheritdoc
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param array $arguments
     * @param array $data
     *
     * @return HTTPResponse
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == "unpin") {
            $pageId = $data["ID"];
            $subPageId = $arguments["ID"];

            if ($pageId && $subPageId) {
                $page = SiteTree::get()->byID($pageId);
                $subPage = SiteTree::get()->byID($subPageId);

                if ($page && $subPage) {
                    $components = $page->getManyManyComponents("SuperGlueSubPages");
                    $components->add($subPage, ["SuperGluePinned" => 0]);
                }
            }
        }
    }
}
