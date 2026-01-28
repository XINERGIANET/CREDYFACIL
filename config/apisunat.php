<?php

return [
	'url' => env('APISUNAT_URL'),
	'id' => env('APISUNAT_ID'),
	'token' => [
		'dev' => env('APISUNAT_TOKEN_DEV'),
		'prod' => env('APISUNAT_TOKEN_PROD')
	]
];