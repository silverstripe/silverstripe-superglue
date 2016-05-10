<?php

namespace SilverStripe\SuperGlue;

use ClassInfo;
use DataExtension;
use ManyManyList;
use Object;

class SubPageExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $belongs_many_many = array(
        "SuperGluePages" => "SiteTree",
    );

    /**
     * @inheritdoc
     */
    public function onAfterWrite()
    {
        $decorated = $this->getDecoratedBy("SilverStripe\\SuperGlue\\PageExtension");

        foreach ($decorated as $class) {
            $objects = call_user_func([$class, "get"]);

            foreach ($objects as $object) {
                if ($connector = $object->SuperGlueConnector) {
                    /** @var Connector $connector */
                    $connector = new $connector();

                    $list = $connector->getDataList($object);
                    $listIds = $list->column("ID");

                    if (in_array($this->owner->ID, $listIds)) {
                        /** @var ManyManyList $relationList */
                        $relationList = $object->SuperGlueSubPages();
                        $relationList->add($this->owner, array("SuperGlueSort" => $relationList->min("SuperGlueSort") - 1));
                    }
                }
            }
        }
    }

    /**
     * @param string $extension
     *
     * @return array
     */
    private function getDecoratedBy($extension)
    {
        $classes = array();

        foreach (ClassInfo::subClassesFor("Object") as $className) {
            if (Object::has_extension($className, $extension)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }
}
