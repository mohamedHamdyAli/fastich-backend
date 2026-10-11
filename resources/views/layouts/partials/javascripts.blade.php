<script type="text/javascript">
    base_path = "{{url('/')}}";
    //used for push notification
    APP = {};
    APP.PUSHER_APP_KEY = '{{config('broadcasting.connections.pusher.key')}}';
    APP.PUSHER_APP_CLUSTER = '{{config('broadcasting.connections.pusher.options.cluster')}}';
    //variable from app service provider
    APP.PUSHER_ENABLED = '{{$__is_pusher_enabled}}';
    @auth
        @php
            $user = Auth::user();
        @endphp
        APP.USER_ID = "{{$user->id}}";
    @else
        APP.USER_ID = '';
    @endauth
</script>

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js?v=$asset_v"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js?v=$asset_v"></script>
<![endif]-->

<!--<script src="{{ asset('js/vendor.js?v=' . $asset_v) }}"></script>-->
<!--<script src="{{ asset('js/vendor.js?v=' . $asset_v) }}"></script>-->

<script src="{{ asset('js/vendor/vendor1.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/vendor/vendor2.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/vendor/vendor3.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/vendor/vendor4.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/vendor/vendor5.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/vendor/vendor6.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/vendor/vendor7.min.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/vendor/vendor8.min.js?v=' . $asset_v) }}"></script>
<script>
    //     var financial_year = {
    //         start: moment('{{ Session::get("financial_year.start") }}'),
    //         end: moment('{{ Session::get("financial_year.end") }}'),
    //     }
</script>
<!--<script src="{{ asset('js/vendor/vendor10.min.js?v=' . $asset_v) }}"></script>-->


<!--<script src="{{asset('js/plugins/jquery-3.4.1.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/jquery-ui.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/jquery.inputmask.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/jquery.slimscroll.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/jquery-jvectormap-1.2.2.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/moment.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/moment-timezone-with-data.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/tiny_mce.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/daterangepicker.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/fullcalendar.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/bootstrap-datepicker.min.js')}}"></script>-->
    <!--<script src="{{asset('js/plugins/bootstrap-slider.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/datatables.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/pace.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/select2.full.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/fileinput.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/toastr.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/sweetalert.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/jquery.validate.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/bootstrap-tour.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/icheck.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/bootstrap-datetimepicker.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/bootstrap-colorpicker.min.js')}}"></script>-->
<!--    <script src="{{asset('js/plugins/dropzone.js')}}"></script>-->


@if(file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale') ) . '.js?v=' . $asset_v) }}"></script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif
@php
    $business_date_format = session('business.date_format', config('constants.default_date_format'));
    $datepicker_date_format = str_replace('d', 'dd', $business_date_format);
    $datepicker_date_format = str_replace('m', 'mm', $datepicker_date_format);
    $datepicker_date_format = str_replace('Y', 'yyyy', $datepicker_date_format);
    $moment_date_format = str_replace('d', 'DD', $business_date_format);
    $moment_date_format = str_replace('m', 'MM', $moment_date_format);
    $moment_date_format = str_replace('Y', 'YYYY', $moment_date_format);
    $business_time_format = session('business.time_format');
    $moment_time_format = 'HH:mm';
    if($business_time_format == 12){
        $moment_time_format = 'hh:mm A';
    }
   
    $user_language = request()->session()->get('user.language', config('app.locale'));
    $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];
    $default_datatable_page_entries = !empty($common_settings['default_datatable_page_entries']) ? $common_settings['default_datatable_page_entries'] : 25;
@endphp

<script>
    moment.tz.setDefault('{{ Session::get("business.time_zone") }}');
    $(document).ready(function(){
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        @if(config('app.debug') == false)
            $.fn.dataTable.ext.errMode = 'throw';
        @endif
    });
    
    var financial_year = {
        start: moment('{{ Session::get("financial_year.start") }}'),
        end: moment('{{ Session::get("financial_year.end") }}'),
    }
    
    @if(file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    //Default setting for select2
    $.fn.select2.defaults.set("language", "{{session()->get('user.language', config('app.locale'))}}");
    @endif

    var datepicker_date_format = "{{$datepicker_date_format}}";
    var moment_date_format = "{{$moment_date_format}}";
    var moment_time_format = "{{$moment_time_format}}";

   
    
    var app_locale = "{{session()->get('user.language', config('app.locale'))}}";
    // if(app_locale =='en'){
    //     var moment_time_format = "{{$moment_time_format}}";
    // }
    // if(app_locale =='ar'){
    //     var moment_time_format = 'mm:HH';
    // } 


    var non_utf8_languages = [
        @foreach(config('constants.non_utf8_languages') as $const)
        "{{$const}}",
        @endforeach
    ];

    var __default_datatable_page_entries = "{{$default_datatable_page_entries}}";

    var __new_notification_count_interval = "60000";
</script>

@if(file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale') ) . '.js?v=' . $asset_v) }}"></script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif

<script src="{{ asset('js/functions.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/common.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/app.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/help-tour.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/documents_and_note.js?v=' . $asset_v) }}"></script>

<!-- TODO -->
@if(file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale') ) . '.js?v=' . $asset_v) }}"></script>
@endif
@php
    $validation_lang_file = 'messages_' . session()->get('user.language', config('app.locale') ) . '.js';
@endphp
@if(file_exists(public_path() . '/js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file))
    <script src="{{ asset('js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file . '?v=' . $asset_v) }}"></script>
@endif

@if(!empty($__system_settings['additional_js']))
    {!! $__system_settings['additional_js'] !!}
@endif
@yield('javascript')

@if(Module::has('Essentials'))
  @includeIf('essentials::layouts.partials.footer_part')
@endif

<script type="text/javascript">
    $(document).ready( function(){
        var locale = "{{session()->get('user.language', config('app.locale'))}}";
        var isRTL = @if(in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl'))) true; @else false; @endif

        $('#calendar').fullCalendar('option', {
            locale: locale,
            isRTL: isRTL
        });
    });
</script>
