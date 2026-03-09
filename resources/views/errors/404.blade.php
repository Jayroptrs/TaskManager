@include('errors.layout', [
    'status' => '404',
    'title' => __('ui.error_404_title'),
    'description' => __('ui.error_404_description'),
    'hint' => __('ui.error_404_hint'),
    'secondaryAction' => 'back',
])
