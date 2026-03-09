@include('errors.layout', [
    'status' => '403',
    'title' => __('ui.error_403_title'),
    'description' => __('ui.error_403_description'),
    'hint' => __('ui.error_403_hint'),
    'secondaryAction' => 'back',
])
