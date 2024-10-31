<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

class WCOA_Attachment
{
	private array $file;

	private int $order_id;

	private WCOA_Admin $adminInstance;

	public const META_KEY = "_wcoa_attachment_id";

	public function __construct(array $file, int $order_id)
	{
		$this->file = $file;
		$this->order_id = $order_id;
		$this->adminInstance = WCOA_Admin::getInstance();
	}

	public function save(): bool|array
    {
		$attachment = $this->upload();

		if ($attachment !== false)
		{
			$notification = new WCOA_Notification($this->order_id, $attachment['id']);
			$notification->create_note();

			if ( WCOA_Options::get('email_enabled') === true )
            {
                $notification->send_email();
            }

			return $attachment;
		}

		return false;
	}

	private function upload(): bool|array
    {
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');

		// check if the file exists
		if (!isset($this->file['name']))
		{
			return false;
		}

        $this->file['name'] = $this->get_attachment_prefix() . $this->file['name'];

		// get the file type from the file name
		$file_type = wp_check_filetype(basename($this->file['name']));

		// create file in the upload folder
		$attachment = wp_upload_bits($this->file['name'], null, file_get_contents($this->file['tmp_name']));

		$filename = $attachment['file'];

		$title = preg_replace('/\.[^.]+$/', '', basename($filename));

		$metadata = [
			'post_mime_type' => $file_type['type'],
			'post_title' => $title,
			'post_content' => '',
			'post_status' => 'inherit',
			'guid' => $attachment['url']
		];

		// insert attachment to database and return attachment id
		$attachment_id = wp_insert_attachment($metadata, $attachment['url'] );

		$inserted_metadata = wp_generate_attachment_metadata($attachment_id, $filename);

		wp_update_attachment_metadata($attachment_id, $inserted_metadata);

	    if ($this->adminInstance->hpos_is_enabled() && OrderUtil::get_order_type($this->order_id) === 'shop_order')
	    {
			$order = wc_get_order($this->order_id);
			if ($order)
			{
				$order->add_meta_data(self::META_KEY, $attachment_id);
				$order->save();
			}
	    }
		else
		{
			add_post_meta($this->order_id, self::META_KEY, $attachment_id);
		}


		return [
			'id' => $attachment_id,
			'title' => $title,
			'url' => $attachment['url']
		];
	}

    /**
     * @since 2.2.2
     * @param int $order_id
     * @return array
     */
    public static function get_all_by_order(int $order_id): array
    {
        global $wpdb;

        if (WCOA_Admin::getInstance()->hpos_is_enabled())
        {
	        $command = sprintf("SELECT attachment.post_title, attachment.guid 
                                   FROM %swc_orders_meta AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment' 
                                   AND attachment_meta.order_id = %d;",
		        $wpdb->prefix, $wpdb->posts, $order_id);
        }
		else
		{
			$command = sprintf("SELECT attachment.post_title, attachment.guid 
                                   FROM %s AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment' 
                                   AND attachment_meta.post_id = %d;",
				$wpdb->postmeta, $wpdb->posts, $order_id);
		}

        $result = $wpdb->get_results($command, ARRAY_A);

        return empty($result) ? [] : $result;
    }

    /**
     * @since 2.2.2
     * @param int $user_id
     * @return array
     */
    public static function get_all_by_user(int $user_id): array
    {
        global $wpdb;

		if (WCOA_Admin::getInstance()->hpos_is_enabled())
		{
			$command = sprintf("SELECT 
                                   attachment.ID AS attachment_id,
                                   attachment.post_date,
                                   attachment.post_title,
                                   attachment_meta.order_id AS order_id,
                                   attachment.guid,
                                   user_order.customer_id
                                   FROM %swc_orders_meta AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   JOIN %swc_orders AS user_order ON attachment_meta.order_id = user_order.id
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment' 
                                   AND user_order.customer_id = %d;",
				$wpdb->prefix, $wpdb->posts, $wpdb->prefix, $user_id);
		}
		else
		{
			$command = sprintf("SELECT 
                                   attachment.ID AS attachment_id,
                                   attachment.post_date,
                                   attachment.post_title,
                                   attachment_meta.post_id AS order_id,
                                   attachment.guid,
                                   user_order.post_author
                                   FROM %s AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   JOIN %s AS user_order ON attachment_meta.post_id = user_order.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment' 
                                   AND user_order.post_author = %d;",
				$wpdb->postmeta, $wpdb->posts, $wpdb->posts, $user_id);
		}

        $result = $wpdb->get_results($command, ARRAY_A);

        return empty($result) ? [] : $result;
    }

    /**
     * @since 2.2.2
     * @return array
     */
    public static function get_all(): array
    {
        global $wpdb;

        if (WCOA_Admin::getInstance()->hpos_is_enabled())
        {
	        $command = sprintf("SELECT
                                   attachment.ID AS attachment_id,
                                   attachment.post_author,
                                   attachment.post_date,
                                   attachment.post_title,
                                   attachment_meta.order_id AS order_id,
                                   attachment.guid FROM %swc_orders_meta AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment';",
		        $wpdb->prefix, $wpdb->posts);
        }
		else
		{
			$command = sprintf("SELECT
                                   attachment.ID AS attachment_id,
                                   attachment.post_author,
                                   attachment.post_date,
                                   attachment.post_title,
                                   attachment_meta.post_id AS order_id,
                                   attachment.guid FROM %s AS attachment_meta
                                   JOIN %s AS attachment ON attachment_meta.meta_value = attachment.ID
                                   WHERE attachment_meta.meta_key = '_wcoa_attachment_id' 
                                   AND attachment.post_type = 'attachment';",
				$wpdb->postmeta, $wpdb->posts);
		}

        $result = $wpdb->get_results($command, ARRAY_A);

        return empty($result) ? [] : $result;
    }

    public static function get_list(int $order_id = 0, int $user_id = 0): array
    {
        if ($user_id !== 0)
        {
            return self::prepare_customer_list($user_id);
        }

        return self::prepare_list($order_id);
    }

	private static function prepare_list(int $order_id = 0): array
	{
		global $wpdb;

		$sql_check = "SELECT COUNT(*) FROM $wpdb->postmeta AS order_meta
				JOIN $wpdb->postmeta AS attachment_meta ON order_meta.meta_value = attachment_meta.post_id
				JOIN $wpdb->posts AS attachments ON order_meta.meta_value = attachments.ID
				WHERE order_meta.meta_key = '_wcoa_attachment_id' and attachment_meta.meta_key = '_wp_attached_file'";

		if ($order_id !== 0)
		{
			$sql_check .= " AND order_meta.post_id = $order_id;";
		}

		$counter = $wpdb->get_var($sql_check);

		if ($counter <= 0)
		{
			return [];
		}

		$sql = "SELECT order_meta.post_id AS order_id, order_meta.meta_value AS post_id, attachments.post_author,
       			attachments.post_date, attachments.post_title, attachments.guid FROM $wpdb->postmeta AS order_meta
				JOIN $wpdb->postmeta AS attachment_meta ON order_meta.meta_value = attachment_meta.post_id
				JOIN $wpdb->posts AS attachments ON order_meta.meta_value = attachments.ID
				WHERE order_meta.meta_key = '_wcoa_attachment_id' AND attachment_meta.meta_key = '_wp_attached_file'";

		if ($order_id !== 0)
		{
			$sql .= " AND order_meta.post_id = $order_id";
		}

		return $wpdb->get_results($sql, ARRAY_A);

	}

	public static function prepare_customer_list(int $user_id): array
	{
		global $wpdb;

		$sql_check = sprintf("SELECT COUNT(*) FROM %s AS attachment
                      JOIN %s AS attachment_meta ON (attachment_meta.meta_value = attachment.ID)
                      JOIN %s AS orders ON attachment_meta.post_id = orders.post_id
                      WHERE attachment.post_type = 'attachment' AND attachment_meta.meta_key = '_wcoa_attachment_id'
                      AND orders.meta_key = '_customer_user' AND attachment.post_mime_type != '' AND orders.meta_value = %d;",
			$wpdb->posts, $wpdb->postmeta, $wpdb->postmeta, $user_id);

		$counter = $wpdb->get_var($sql_check);

		if (is_numeric($counter) && (int)$counter <= 0)
		{
			return [];
		}

		$sql = sprintf("SELECT orders.post_id AS 'order_id', attachment.post_date, attachment.guid 
				FROM %s AS attachment
                JOIN %s AS attachment_meta ON (attachment_meta.meta_value = attachment.ID)
                JOIN %s AS orders ON attachment_meta.post_id = orders.post_id
                WHERE attachment.post_type = 'attachment' AND attachment_meta.meta_key = '_wcoa_attachment_id'
                AND orders.meta_key = '_customer_user' AND attachment.post_mime_type != '' AND orders.meta_value = %d;",
			$wpdb->posts, $wpdb->postmeta, $wpdb->postmeta, $user_id);

		return $wpdb->get_results($sql, ARRAY_A);

	}

	public static function get_url(int $order_id, string $order_key, int $attachment_id = 0): bool|string
    {
		global $wpdb;

		$counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id = $order_id AND meta_value = '$order_key';");

		if ($counter < 1)
        {
            return false;
        }

		if ($attachment_id === 0)
        {
            $result = $wpdb->get_var("SELECT MIN(meta_value) AS qty FROM $wpdb->postmeta where post_id = $order_id and meta_key = '_wcoa_attachment_id'");
			if (!is_numeric($result) )
			{
				return false;
			}
	        $attachment_id = (int)$result;
        }

		$url = get_the_guid($attachment_id);

		if ($url === '' || str_contains($url, '?p=1'))
        {
            return false;
        }

		return $url;
	}

	public static function get_public_url(int $order_id, int $attachment_id): string
	{
		global $wpdb;

		$key = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $order_id AND meta_key = '_order_key'");

		if (empty($key))
        {
            return false;
        }

		$path = "?wcoa_attachment_for_order=$order_id&key=$key&id=$attachment_id";

		return get_home_url(null, $path);

	}

    /**
     * @since 2.2.0
     * @return string
     */
    private function get_attachment_prefix(): string
    {
        $default = '';

        if (get_option('wcoa_general'))
        {
            $prefix = get_option( 'wcoa_general')['attachment_prefix'] ?? '';

            if (str_contains($prefix, "{order_number}"))
            {
                return str_replace("{order_number}", $this->order_id, $prefix);
            }

            return empty($prefix) ? $default : trim($prefix);
        }

        return $default;
    }
}


