<?php

class WCOA_Options
{

	private ?array $options;

	public function __construct()
	{
		add_action( 'admin_init', [$this, 'load_fields'] );

		$general = get_option( 'wcoa_general');
		$this->options = is_array($general) ? $general : null;
	}

	public function load_fields(): void
    {
		register_setting( 'wcoa_general', 'wcoa_general', [$this, 'validate'] );

        add_settings_section( 'general', __('General','woocommerce'), [], 'wcoa_general_page' );
		add_settings_section( 'email_template', __('Email options','woocommerce'), [$this, 'section_email_template_description'], 'wcoa_general_page' );
		add_settings_section( 'my_account', __('My account page','woocommerce'), [], 'wcoa_general_page' );

        add_settings_field( 'option_attachment_prefix', __('Attachment prefix','sld-wcoa'), [$this, 'option_input_text'], 'wcoa_general_page', 'general', self::params_attachment_prefix());

		add_settings_field('option_email_header', __('Subject','woocommerce'), [$this, 'option_input_text'], 'wcoa_general_page', 'email_template', self::params_email_header());
		add_settings_field( 'option_email_content', __('Message','woocommerce'), [$this, 'option_input_text'], 'wcoa_general_page', 'email_template', self::params_email_content());
		add_settings_field( 'option_email_button', __('Button text','woocommerce'), [$this, 'option_input_text'], 'wcoa_general_page', 'email_template', self::params_email_button());
		add_settings_field( 'option_email_enabled', __('Automatic email notifications','sld-wcoa'), [$this, 'option_email_enabled'], 'wcoa_general_page', 'email_template' );
		add_settings_field( 'option_completed_email_enabled', __('Completed order email','sld-wcoa'), [$this, 'option_completed_email_enabled'], 'wcoa_general_page', 'email_template' );

        add_settings_field( 'option_attachments_endpoint', __('Endpoint URL','sld-wcoa'), [$this, 'option_input_text'], 'wcoa_general_page', 'my_account', self::params_attachments_endpoint());

	}

	public function section_email_template_description(): void
    {
		_e('Customizing attachments email notifications.','sld-wcoa');
	}

	private static function params_email_header(): array
	{
		return [
			'tag' => 'input',
			'id' => 'option_email_header',
			'name' => 'message_header',
			'style' => [ 'wcoa-input-400' ],
			'value' => 'message_header',
			'placeholder' => 'New attachment to the order {order_number}',
			'label' => esc_html__('Text to appear the email subject.', 'sld-wcoa')
			           . " " . sprintf( esc_html__('Available placeholders: %s', 'woocommerce'), '<code>{site_title}</code>, <code>{order_number}</code>')
		];
	}

	private static function params_email_content(): array
	{
		return [
			'tag' => 'textarea',
			'id' => 'option_email_content',
			'name' => 'message_content',
			'style' => [ 'wcoa-input-400' ],
			'value' => 'message_content',
			'placeholder' => 'An attachment has been added to your order {order_number}. Click the button below to view it.',
			'label' => esc_html__('Text to appear the main email content.', 'sld-wcoa')
			           . " " . sprintf( esc_html__('Available placeholders: %s', 'woocommerce'), '<code>{site_title}</code>, <code>{order_number}</code>')
		];
	}

	private static function params_email_button(): array
	{
		return [
			'tag' => 'input',
			'id' => 'option_email_button',
			'name' => 'button_text',
			'style' => [ 'wcoa-input-200' ],
			'value' => 'button_text',
			'placeholder' => esc_html__('Preview', 'woocommerce'),
			'label' => esc_html__('Attachment preview button text.', 'sld-wcoa')
		];
	}

    /**
     * @since 2.2.0
     * @return array
     */
	private static function params_attachment_prefix(): array
	{
		return [
			'tag' => 'input',
			'id' => 'option_attachment_prefix',
			'name' => 'attachment_prefix',
			'style' => [ 'wcoa-input-200' ],
			'value' => 'attachment_prefix',
            'placeholder' => '',
            'label' => esc_html__('The prefix will be added to the attachment file name.', 'sld-wcoa')
                       // translators: %s is the mapping type or description.
                . " " . sprintf( esc_html__('Available mapping: %s', 'sld-wcoa'), '<code>{order_number}</code>')
		];
	}

    /**
     * @since 2.2.0
     * @return array
     */
	private static function params_attachments_endpoint(): array
	{
		return [
			'tag' => 'input',
			'id' => 'option_attachments_endpoint',
			'name' => 'attachments_endpoint',
			'style' => [ 'wcoa-input-200' ],
			'value' => 'attachments_endpoint',
			'placeholder' => 'wcoa-attachments',
			'label' => esc_html__('Endpoint for the "My account &rarr; Attachments" page.', 'sld-wcoa')
		];
	}

	public function option_input_text(array $args): void
    {
		$style = implode(' ', $args['style']);

		$content = null;

		if (isset($this->options[$args['value']]))
        {
            $content = esc_attr( $this->options[$args['value']] );
        }

		print "<{$args['tag']} id='{$args['id']}' name='wcoa_general[{$args['name']}]' class='$style' type='text' value='$content' placeholder='{$args['placeholder']}' >";

		if ($args['tag'] === 'textarea')
        {
            print "$content</{$args['tag']}>";
        }

		print "<p for='{$args['id']}'>" . $args['label'] ."</p>";
	}

	public function option_email_enabled(): void
    {
		$content = null;

			if (isset($this->options['email_enabled']) && $this->options['email_enabled'] === true)
            {
                $content = 'checked';
            }

		print "<input id='option_email_enabled' name='wcoa_general[email_enabled]' type='checkbox' value='1' $content/>";
		print "<label for='option_email_enabled'>" . esc_html__("Enable auto-send messages after addition attachment.", 'sld-wcoa') ."</label>";
	}

	public function option_completed_email_enabled(): void
    {
		$content = null;

			if (isset($this->options['completed_email_enabled']) && $this->options['completed_email_enabled'] === true)
            {
                $content = 'checked';
            }

		print "<input id='option_completed_email_enabled' name='wcoa_general[completed_email_enabled]' type='checkbox' value='1' $content/>";
		print "<label for='option_completed_email_enabled'>" . esc_html__("Add attachment information to completed order email.", 'sld-wcoa') ."</label>";
	}

	public function validate(array $input): array
	{
		$new = [];

		if (isset($input['message_header']))
        {
            $new['message_header'] = trim( $input['message_header']);
        }

		if (isset($input['message_content']))
        {
            $new['message_content'] = trim( $input['message_content']);
        }

		if (isset($input['button_text']))
        {
            $new['button_text'] = trim( $input['button_text']);
        }

		if (isset($input['attachment_prefix']))
        {
            $new['attachment_prefix'] = trim( $input['attachment_prefix']);
        }

		if (isset($input['attachments_endpoint']))
        {
            $new['attachments_endpoint'] = trim( $input['attachments_endpoint']);
        }

        $new['email_enabled'] = isset($input['email_enabled']) && trim($input['email_enabled']) === '1';

        $new['completed_email_enabled'] = isset($input['completed_email_enabled']) && trim($input['completed_email_enabled']) === '1';

		return $new;
	}

	public static function get($option)
	{
        return get_option('wcoa_general')[$option] ?? false;
	}
}

new WCOA_Options();

