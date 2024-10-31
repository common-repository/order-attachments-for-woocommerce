<?php

class WCOA_Ajax
{

	public function __construct()
	{
		add_action( 'wp_ajax_wcoa_add_attachment', [$this, 'add_attachment']);
		add_action( 'wp_ajax_wcoa_send_email_to_customer', [$this, 'send_email_to_customer']);
	}

	public static function add_attachment(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$response = [
			'status' => 'error',
			'code' => -1,
			'message' => __('Error saving media file.')
		];

		if (isset($_FILES['attachment']['name']))
		{
			$file = $_FILES['attachment'];
			$order_id = $_POST['order_id'];

			$attachment = new WCOA_Attachment($file, $order_id);
			$result = $attachment->save();

			if ($result !== false)
			{
				$response = [
					'status' => 'success',
					'code' => 0,
					'message' => sprintf(__('%s media file attached.'), 1),
					'data' => $result
				];
			}
		} else {
			$response['message'] = __('No file was uploaded.');
		}

		try
		{
			print json_encode($response, JSON_THROW_ON_ERROR);
		}
		catch (JsonException $e)
		{
			print sprintf("{\"status\": \"success\", \"code\": 0, \"message\": \"%s\"}", $e->getMessage());
		}

		wp_die();
	}

	public static function send_email_to_customer(): void
	{
		header('Content-Type: application/json; charset=utf-8');

		$response = [
			'status' => 'error',
			'code' => -1
		];

		if (isset($_POST['order_id']))
		{
			$attachment_id = 0;

			if (isset($_POST['attachment_id']))
			{
				$res = $_POST['attachment_id'];
				if (is_numeric($res))
				{
					$attachment_id = (int)$res;
				}
			}

			$notification = new WCOA_Notification($_POST['order_id'], $attachment_id);
			$notification->send_email();

			$response = [
				'status' => 'success',
				'code' => 0
			];
		}

		try
		{
			print json_encode($response, JSON_THROW_ON_ERROR);
		}
		catch (JsonException)
		{
			print "{\"status\": \"success\", \"code\": 0}";
		}

		wp_die();
	}
}

new WCOA_Ajax();
