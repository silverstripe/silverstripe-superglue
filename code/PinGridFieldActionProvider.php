<?php

namespace SilverStripe\SuperGlue;

use Controller;
use DataObject;
use Exception;
use GridField;
use GridField_ActionProvider;
use GridField_ColumnProvider;
use GridField_FormAction;
use SiteTree;
use SS_HTTPResponse;

class PinGridFieldActionProvider implements GridField_ColumnProvider, GridField_ActionProvider
{
    /**
     * @var int
     */
    private $limit = 5;

    /**
     * @var int
     */
    private $pinned = 0;

    /**
     * @param int $limit
     * @param int $pinned
     */
    public function __construct($limit, $pinned)
    {
        $this->limit = $limit;
        $this->pinned = $pinned;
    }

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
        return array("class" => "col-buttons");
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
            return array("title" => "");
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
        return array("Actions");
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
        if ($this->pinned < $this->limit) {
            $field = GridField_FormAction::create(
                $gridField,
                "CustomAction" . $record->ID,
                "pin",
                "pin",
                array("ID" => $record->ID)
            );

            return $field->Field();
        }

        return "";
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
        return array("pin");
    }

    /**
     * @inheritdoc
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param array $arguments
     * @param array $data
     *
     * @return SS_HTTPResponse
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == "pin") {
            $pageId = $data["ID"];
            $subPageId = $arguments["ID"];

            if ($pageId && $subPageId) {
                $page = SiteTree::get()->byID($pageId);
                $subPage = SiteTree::get()->byID($subPageId);

                if ($page && $subPage) {
                    $components = $page->getManyManyComponents("SuperGlueSubPages");
                    $components->add($subPage, array("SuperGluePinned" => 1));
                }
            }
        }

        return Controller::curr()->redirectBack();
    }
}
