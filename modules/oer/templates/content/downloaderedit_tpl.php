<?php
ob_start();
$objFix = $this->getObject('cssfixlength', 'htmlelements');
$objFix->fixThree();
?>
<div id="twocolumn">
   
    <div id="Canvas_Content_Body_Region2">
        {
        "display" : "block",
        "module" : "oer",
        "block" : "downloaderedit",
        <?php
        echo '"configData":';
        echo '"' . $id . '|' . $productid . '|' .$producttype. '"';
        ?>
        }
        
        <div id="middledynamic_area" class="middledynamic_area_layer">&nbsp;</div>
        <div id="middlefeedback_area" class="middlefeedback_area_layer">&nbsp;</div>
    </div>
   
</div>

<?php
// Get the contents for the layout template
$this->setVar('errors', Null);
$pageContent = ob_get_contents();
ob_end_clean();
$this->setVar('pageContent', $pageContent);
?>