<?php
/* @var $this RanksController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Ranks',
);

$this->menu=array(
	array('label'=>'Create Ranks', 'url'=>array('create')),
	array('label'=>'Manage Ranks', 'url'=>array('admin')),
);
?>

<h1>Ranks</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
