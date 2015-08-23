<?php
/* @var $this RanksController */
/* @var $model Ranks */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'ranks-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'url'); ?>
		<?php echo $form->textField($model,'url',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'url'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'title'); ?>
		<?php echo $form->textField($model,'title',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'title'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'rank_best_sell'); ?>
		<?php echo $form->textField($model,'rank_best_sell'); ?>
		<?php echo $form->error($model,'rank_best_sell'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'info_best_sell'); ?>
		<?php echo $form->textField($model,'info_best_sell',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'info_best_sell'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'info_ranks'); ?>
		<?php echo $form->textField($model,'info_ranks',array('size'=>60,'maxlength'=>4096)); ?>
		<?php echo $form->error($model,'info_ranks'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'isbn_10'); ?>
		<?php echo $form->textField($model,'isbn_10',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'isbn_10'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'isbn_13'); ?>
		<?php echo $form->textField($model,'isbn_13',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'isbn_13'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->