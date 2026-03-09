@include('errors.layout', [
    'status' => '502',
    'title' => __('ui.error_502_title'),
    'description' => __('ui.error_502_description'),
    'hint' => __('ui.error_502_hint'),
    'secondaryAction' => 'reload',
])
