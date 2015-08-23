<?php
/* @var $this RanksController */
/* @var $model Ranks */

$this->breadcrumbs=array(
	'Ranks'=>array('index'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List Ranks', 'url'=>array('index')),
	array('label'=>'Create Ranks', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#ranks-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1>Manage Ranks</h1>

<p>
You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php echo CHtml::link('Advanced Search','#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<?php $this->renderPartial('_search',array(
	'model'=>$model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'ranks-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'url',
		'title',
		'rank_best_sell',
		'info_best_sell',
		'info_ranks',
		/*
		'isbn_10',
		'isbn_13',
		*/
		array(
			'class'=>'CButtonColumn',
		),
	),
)); ?>
