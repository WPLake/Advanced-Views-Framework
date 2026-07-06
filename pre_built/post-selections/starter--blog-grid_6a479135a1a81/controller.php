<?php

declare(strict_types=1);

use Org\Wplake\Advanced_Views\Bridge\Controllers\Selection\Selection_Controller_Base;

return new class extends Selection_Controller_Base {
    /**
     * @return array<string,mixed>
     */
    public function get_variables(): array
    {
        return [
            // "another_var" => $this->get_custom_arguments()["another"] ?? "",
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function get_variables_for_validation(): array
    {
        // it's better to return dummy data here [ "another_var" => "dummy string", ]
        return $this->get_variables();
    }

    public function get_query_arguments(): array
    {
        // https://developer.wordpress.org/reference/classes/wp_query/#parameters
        return [
            // "author" => get_current_user_id(),
            // "post_parent" => $this->get_custom_arguments()["post_parent"] ?? 0,
        ];
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
