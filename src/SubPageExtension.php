<?php

namespace SilverStripe\SuperGlue;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Model\List\ManyManyList;

class SubPageExtension extends Extension
{
    /**
     * @inheritdoc
     */
    public function onAfterWrite()
    {
        $decorated = $this->getDecoratedBy(PageExtension::class);

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
                        $relationList->add($this->owner, ["SuperGlueSort" => $relationList->min("SuperGlueSort") - 1]);
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
        $classes = [];

        foreach (ClassInfo::subClassesFor(DataObject::class) as $className) {
            if (DataObject::has_extension($className, $extension)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }
}
