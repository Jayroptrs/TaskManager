@include('errors.layout', [
    'status' => '419',
    'title' => __('ui.error_419_title'),
    'description' => __('ui.error_419_description'),
    'hint' => __('ui.error_419_hint'),
    'secondaryAction' => 'reload',
])
