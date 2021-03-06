<?php

$this->objFileIcons = $this->getObject('fileicons', 'files');
$this->loadClass('form', 'htmlelements');
$this->loadClass('checkbox', 'htmlelements');
$this->loadClass('htmlheading', 'htmlelements');
$this->loadClass('label', 'htmlelements');
$this->loadClass('button', 'htmlelements');
$this->loadClass('hiddeninput', 'htmlelements');
$this->loadClass('textarea', 'htmlelements');

$heading = new htmlheading();
$heading->type = 1;
$heading->str = $this->objLanguage->languageText('mod_filemanager_editfiledetails', 'filemanager', 'Edit File Details').': '.$file['filename'];
echo $heading->show();

$form = new form ('updatefiledetails', $this->uri(array('action'=>'updatefiledetails')));

$table = $this->newObject('htmltable', 'htmlelements');
$table->startRow();
$label = new label ($this->objLanguage->languageText('word_description', 'system', 'Description').':', 'input_description');
$table->addCell($label->show());
$description = new textarea('description');
$description->value = $file['description'];
$table->addCell($description->show());
$table->endRow();

$table->startRow();
$label = new label ($this->objLanguage->languageText('mod_filemanager_keywordstags', 'filemanager', 'Keywords/Tags').':<br />'.$this->objLanguage->languageText('mod_filemanager_separatewithcommas', 'filemanager', 'Separate with commas'), 'input_keywords');
$table->addCell($label->show());
$keywords = new textarea('keywords');

$keywordsList = '';
if (count($tags) > 0) {
    $comma = '';
    foreach ($tags as $tag)
    {
        $keywordsList .= $comma.$tag;
        $comma = ', ';
    }
}

$keywords->value = $keywordsList;
$table->addCell($keywords->show());
$table->endRow();

$objModules = $this->getObject('modules', 'modulecatalogue');
        
if ($objModules->checkIfRegistered('creativecommons')) {
    $table->startRow();
    $table->addCell($this->objLanguage->languageText('mod_filemanager_filelicense', 'filemanager', 'File License').':');
    $licensechooser = $this->newObject('licensechooser', 'creativecommons');
    $licensechooser->defaultValue = $file['license'];
    $table->addCell($licensechooser->show());
    $table->endRow();
}

$form->addToForm($table->show());

$button = new button ('submitform', $this->objLanguage->languageText('mod_filemanager_updatefileinfo', 'filemanager', 'Update File Info'));
$button->setToSubmit();

$form->addToForm($button->show());

$hiddenInput = new hiddeninput('id', $file['id']);
$form->addToForm($hiddenInput->show());

echo $form->show();
?>