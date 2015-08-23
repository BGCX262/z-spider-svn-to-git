<?php
/* @var $this RanksController */
/* @var $model Ranks */

$this->breadcrumbs=array(
	'Ranks'=>array('index'),
	$model->title=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Ranks', 'url'=>array('index')),
	array('label'=>'Create Ranks', 'url'=>array('create')),
	array('label'=>'View Ranks', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage Ranks', 'url'=>array('admin')),
);
?>

<h1>Update Ranks <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>