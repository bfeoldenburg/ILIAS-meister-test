<?php

require_once 'Services/Repository/classes/class.ilObjectPluginListGUI.php';

class ilObjBaseReportListGUI extends ilObjBaseReportListGUI {
	
	abstract public function initType();
	abstract public function getGuiClass();
	abstract public function initCommands();

}