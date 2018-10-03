<?php

namespace SilverStripe\SuperGlue;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Core\Extension;

class PageControllerExtension extends Extension
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        "LoadMore",
    ];

    /**
     * @return SS_HTTPResponse
     */
    public function LoadMore()
    {
        /** @var PaginatedList|DataObject[] $pages */
        $pages = $this->owner->SuperGlueViewSubPages();

        $connector = $this->owner->SuperGlueConnector;

        /** @var Connector $connector */
        $connector = new $connector();

        $items = [];

        foreach ($pages as $page) {
            if (method_exists($connector, "getPageArray")) {
                $item = $connector->getPageArray($page);
            } else {
                $item = $page->toMap();
            }

            $items[] = $item;
        }

        $data = [
            "total" => (int)$pages->TotalItems(),
            "limit" => (int)$pages->getPageLength(),
            "start" => (int)$pages->getPageStart(),
            "next"  => $pages->NextLink(),
            "items" => $items,
        ];

        if ((int)$pages->getPageStart() >= (int)$pages->TotalItems() - (int)$pages->getPageLength()) {
            unset($data["next"]);
        }

        $response = new \HttpRequest();
        $response->setBody(json_encode($data));
        $response->addHeader("Content-type", "application/json");

        return $response;
    }
}
