@include('errors.layout', [
    'status' => '500',
    'title' => __('ui.error_500_title'),
    'description' => __('ui.error_500_description'),
    'hint' => __('ui.error_500_hint'),
    'secondaryAction' => 'reload',
])
