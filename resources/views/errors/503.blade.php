@include('errors.layout', [
    'status' => '503',
    'title' => __('ui.error_503_title'),
    'description' => __('ui.error_503_description'),
    'hint' => __('ui.error_503_hint'),
    'secondaryAction' => 'reload',
])
