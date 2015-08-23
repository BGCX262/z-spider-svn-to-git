<?php
/* @var $this RanksController */
/* @var $model Ranks */

$this->breadcrumbs=array(
	'Ranks'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Ranks', 'url'=>array('index')),
	array('label'=>'Manage Ranks', 'url'=>array('admin')),
);
?>

<h1>Create Ranks</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>