<?php
/* @var $this RanksController */
/* @var $data Ranks */
?>

<div class="view">

	<b><?php echo CHtml::encode($data->getAttributeLabel('id')); ?>:</b>
	<?php echo CHtml::link(CHtml::encode($data->id), array('view', 'id'=>$data->id)); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('url')); ?>:</b>
	<?php echo CHtml::encode($data->url); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('title')); ?>:</b>
	<?php echo CHtml::encode($data->title); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('rank_best_sell')); ?>:</b>
	<?php echo CHtml::encode($data->rank_best_sell); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('info_best_sell')); ?>:</b>
	<?php echo CHtml::encode($data->info_best_sell); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('info_ranks')); ?>:</b>
	<?php echo CHtml::encode($data->info_ranks); ?>
	<br />

	<b><?php echo CHtml::encode($data->getAttributeLabel('isbn_10')); ?>:</b>
	<?php echo CHtml::encode($data->isbn_10); ?>
	<br />

	<?php /*
	<b><?php echo CHtml::encode($data->getAttributeLabel('isbn_13')); ?>:</b>
	<?php echo CHtml::encode($data->isbn_13); ?>
	<br />

	*/ ?>

</div>