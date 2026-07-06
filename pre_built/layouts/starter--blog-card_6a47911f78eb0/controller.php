<?php

declare(strict_types=1);

use Org\Wplake\Advanced_Views\Bridge\Controllers\Layout\Layout_Controller_Base;

return new class extends Layout_Controller_Base {
    /**
     * @return array<string,mixed>
     */
    public function get_variables(): array
    {
        return [
            // "custom_variable" => get_post_meta($this->get_object_id(), "your_field", true),
            // "another_var" => $this->get_custom_arguments()["another"] ?? "",
        ];
    }
    /**
     * @return array<string,mixed>
     */
    public function get_variables_for_validation(): array
    {
        // it's better to return dummy data here [ "custom_variable" => "dummy string", ]
        return $this->get_variables();
    }
    /**
     * @return array<string,mixed>
     */
    public function get_ajax_response(): array
	{
	    // $message = $this->get_container()->get(MyClass::class)->myMethod();
		return [
			// "message" => $message,
		];
	}
	/**
     * @return array<string,mixed>
     */
    public function get_rest_api_response(WP_REST_Request $request): array
	{
	    // $input = $request->get_json_params();
	    // $message = $this->get_container()->get(MyClass::class)->myMethod();
		return [
			// "message" => $message,
		];
	}
};
