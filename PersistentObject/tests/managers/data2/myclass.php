<?php
require_once 'PHPUnit/Util/Filter.php';
PHPUnit_Util_Filter::addFileToFilter(__FILE__);

$def = new ezcPersistentObjectDefinition();
$def->class = 'MyClass';
return $def;
?>
