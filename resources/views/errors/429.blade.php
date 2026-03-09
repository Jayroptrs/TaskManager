@include('errors.layout', [
    'status' => '429',
    'title' => __('ui.error_429_title'),
    'description' => __('ui.error_429_description'),
    'hint' => __('ui.error_429_hint'),
    'secondaryAction' => 'reload',
])
