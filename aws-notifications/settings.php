<?php
add_action( 'admin_menu', 'awsn_add_admin_menu' );
add_action( 'admin_init', 'awsn_settings_init' );

function awsn_add_admin_menu(  ) { 

	add_submenu_page( 'options-general.php', 
						'AWS Notifications', 
						'AWS Notifications', 
						'manage_options', 
						'aws-notifications', 
						'awsn_options_page' );
}


function awsn_settings_init(  ) { 

	register_setting( 'pluginPage', 'awsn_settings' );

	add_settings_section(
		'awsn_pluginPage_section_0', 
		__( 'Amazon Web Services Credentials', 'aws-file-upload' ), 
		'awsn_settings_section_callback_0', 
		'pluginPage'
	);

	add_settings_field( 
		'awsn_text_field_0_aws_key', 
		__( 'AWS Public Key', 'aws-file-upload' ), 
		'awsn_text_field_0_render', 
		'pluginPage', 
		'awsn_pluginPage_section_0' 
	);

	add_settings_field( 
		'awsn_text_field_1_aws_secret', 
		__( 'AWS Secret', 'aws-file-upload' ), 
		'awsn_text_field_1_render', 
		'pluginPage', 
		'awsn_pluginPage_section_0' 
	);

}


function awsn_text_field_0_render(  ) { 

	$options = get_option( 'awsn_settings' );
	?>
	<input type='text' name='awsn_settings[awsn_text_field_0_aws_key]' 
		value='<?php echo $options['awsn_text_field_0_aws_key']; ?>'>
	<?php

}


function awsn_text_field_1_render(  ) { 

	$options = get_option( 'awsn_settings' );
	?>
	<input type='password' name='awsn_settings[awsn_text_field_1_aws_secret]' 
		value='<?php echo $options['awsn_text_field_1_aws_secret']; ?>'>
	<?php

}


function awsn_settings_section_callback_0(  ) { 

	echo __( 'Please include your credentials for the AWS Simple Email Service.', 'aws-notifications' );

}


function awsn_options_page(  ) { 

		?>
		<form action='options.php' method='post'>

			<h2>AWS Notifications</h2>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

		</form>
		<?php
}

