<?php

namespace dutchheight\navie\craftql\repositories;

use dutchheight\navie\Navie;
use yii\base\Component;

class ListRepository extends Component
{
    private $lists = [];

    public function load()
    {
        foreach (Navie::$plugin->getLists()->getAllLists() as $list) {
            if (!isset($this->lists[$list->id])) {
                $this->lists[$list->id] = $list;

                if (!empty($list->uid)) {
                    $this->lists[$list->uid] = $list;
                }
            }
        }
    }

    public function get($id)
    {
        if (!isset($this->lists[$id])) {
            return false;
        }

        return $this->lists[$id];
    }

    public function all()
    {
        return $this->lists;
    }
}