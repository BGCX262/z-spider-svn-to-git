<?php
/* @var $this RanksController */
/* @var $model Ranks */
/* @var $form CActiveForm */
?>

<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<div class="row">
		<?php echo $form->label($model,'id'); ?>
		<?php echo $form->textField($model,'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'url'); ?>
		<?php echo $form->textField($model,'url',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'title'); ?>
		<?php echo $form->textField($model,'title',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'rank_best_sell'); ?>
		<?php echo $form->textField($model,'rank_best_sell'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'info_best_sell'); ?>
		<?php echo $form->textField($model,'info_best_sell',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'info_ranks'); ?>
		<?php echo $form->textField($model,'info_ranks',array('size'=>60,'maxlength'=>4096)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'isbn_10'); ?>
		<?php echo $form->textField($model,'isbn_10',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'isbn_13'); ?>
		<?php echo $form->textField($model,'isbn_13',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->