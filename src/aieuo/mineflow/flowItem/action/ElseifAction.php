<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\recipe\Recipe;

class ElseifAction extends IFAction {

    protected $id = self::ACTION_ELSEIF;

    protected $name = "action.elseif.name";
    protected $detail = "action.elseif.description";

    public function getDetail(): string {
        $details = ["=============elseif============="];
        foreach ($this->getConditions() as $condition) {
            $details[] = $condition->getDetail();
        }
        $details[] = "~~~~~~~~~~~~~~~~~~~~~~~~~~~";
        foreach ($this->getActions() as $action) {
            $details[] = $action->getDetail();
        }
        $details[] = "================================";
        return implode("\n", $details);
    }

    public function execute(Recipe $origin): bool {
        $lastResult = $this->getParent()->getLastActionResult();
        if ($lastResult === null) throw new \UnexpectedValueException();
        if ($lastResult) return true;

        foreach ($this->getConditions() as $condition) {
            if (!$condition->execute($origin)) return false;
        }

        $this->executeActions($origin, $this->getParent());
        return true;
    }
}