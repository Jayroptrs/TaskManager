@include('errors.layout', [
    'status' => '401',
    'title' => __('ui.error_401_title'),
    'description' => __('ui.error_401_description'),
    'hint' => __('ui.error_401_hint'),
    'secondaryAction' => 'back',
])
