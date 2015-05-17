<?=form_open($base_form)?>

<p><?= lang('client_information'); ?></p>

<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(lang('preference'), lang('setting'));

foreach ($settings as $setting => $value)
{
	$this->table->add_row(
		lang($setting, $setting),
		form_input(array(
			'name'  => $setting,
			'id'    => $setting,
			'value' => $value
		))
	);
}

echo $this->table->generate();
?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?=form_close()?>
