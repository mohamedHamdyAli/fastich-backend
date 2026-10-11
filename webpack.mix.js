const mix = require('laravel-mix');

// Vendor 1: pace, jQuery UI, Bootstrap
mix.combine([
    'resources/plugins/AdminLTE/plugins/pace/pace.min.js',
    'resources/plugins/jquery-ui/jquery-ui.min.js',
    'resources/plugins/bootstrap/js/bootstrap.min.js'
], 'public/js/vendor1.js');

// Vendor 2: select2, datepicker, DataTables core
mix.combine([
    'resources/plugins/AdminLTE/plugins/select2/select2.full.min.js',
    'resources/plugins/AdminLTE/plugins/datepicker/bootstrap-datepicker.min.js',
    'resources/plugins/AdminLTE/plugins/DataTables/datatables.min.js'
], 'public/js/vendor2.js');

// Vendor 3: DataTables PDF and validation
mix.combine([
    'resources/plugins/AdminLTE/plugins/DataTables/pdfmake-0.1.32/pdfmake.min.js',
    'resources/plugins/AdminLTE/plugins/DataTables/pdfmake-0.1.32/vfs_fonts.js',
    'resources/plugins/jquery-validation-1.16.0/dist/jquery.validate.min.js',
    'resources/plugins/jquery-validation-1.16.0/dist/additional-methods.min.js'
], 'public/js/vendor3.js');

// Vendor 4: toastr, fileinput, accounting
mix.combine([
    'resources/plugins/toastr/toastr.min.js',
    'resources/plugins/bootstrap-fileinput/fileinput.min.js',
    'resources/plugins/accounting.min.js'
], 'public/js/vendor4.js');

// Vendor 5: datetimepicker, daterangepicker
mix.combine([
    'resources/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js',
    'resources/plugins/AdminLTE/plugins/daterangepicker/daterangepicker.js'
], 'public/js/vendor5.js');

// Vendor 6: mousetrap, sweetalert, bootstrap-tour
mix.combine([
    'resources/plugins/mousetrap/mousetrap.min.js',
    'resources/plugins/sweetalert/sweetalert.min.js',
    'resources/plugins/bootstrap-tour/bootstrap-tour.min.js'
], 'public/js/vendor6.js');

// Vendor 7: printThis, AdminLTE app, calculator
mix.combine([
    'resources/plugins/printThis.js',
    'resources/plugins/AdminLTE/js/AdminLTE-app.js',
    'resources/plugins/calculator/calculator.js'
], 'public/js/vendor7.js');

// Vendor 8: dropzone, jQuery steps, fullcalendar
mix.combine([
    'resources/plugins/dropzone/dropzone.js',
    'resources/plugins/jquery.steps/jquery.steps.min.js',
    'resources/plugins/fullcalendar/fullcalendar.min.js',
    'resources/plugins/fullcalendar/locale-all.js'
], 'public/js/vendor8.js');

// Vendor 9: colorpicker, decimal, jKanban
mix.combine([
    'resources/plugins/bootstrap-colorpicker/bootstrap-colorpicker.min.js',
    'resources/plugins/decimal.min.js',
    'resources/plugins/jKanban/jKanbanBoard.js'
], 'public/js/vendor9.js');

// Vendor 10: onscan, top scrollbar
mix.combine([
    'node_modules/onscan.js/onscan.min.js',
    'resources/plugins/jquery.top_scrollbar.js'
], 'public/js/vendor10.js');

// Default JS (app.js)
mix.js('resources/js/app.js', 'public/js/init.js');

// Set the resource root to ensure proper paths
mix.setResourceRoot('../');

// Production optimizations

    // Minify all vendor files for production
    mix.minify('public/js/vendor1.js');
    mix.minify('public/js/vendor2.js');
    mix.minify('public/js/vendor3.js');
    mix.minify('public/js/vendor4.js');
    mix.minify('public/js/vendor5.js');
    mix.minify('public/js/vendor6.js');
    mix.minify('public/js/vendor7.js');
    mix.minify('public/js/vendor8.js');
    mix.minify('public/js/vendor9.js');
    mix.minify('public/js/vendor10.js');




// const mix = require('laravel-mix');

// /*
//  |--------------------------------------------------------------------------
//  | Mix Asset Management
//  |--------------------------------------------------------------------------
//  |
//  | Mix provides a clean, fluent API for defining some Webpack build steps
//  | for your Laravel application. By default, we are compiling the Sass
//  | file for the application as well as bundling up all the JS files.
//  |
//  */
// //
// // mix.js('resources/js/app.js', 'public/js/init.js')
// //     // .sass('resources/sass/app.scss', 'public/css/init.css')
// //     .combine([
// //       'public/js/init.js',
// //         'resources/plugins/AdminLTE/plugins/pace/pace.min.js',
// //     	'resources/plugins/jquery-ui/jquery-ui.min.js',
// //     	'resources/plugins/bootstrap/js/bootstrap.min.js',
// //     	'resources/plugins/AdminLTE/plugins/select2/select2.full.min.js',
// //     	'resources/plugins/AdminLTE/plugins/datepicker/bootstrap-datepicker.min.js',
// //     	'resources/plugins/AdminLTE/plugins/DataTables/datatables.min.js',
// //     	'resources/plugins/AdminLTE/plugins/DataTables/pdfmake-0.1.32/pdfmake.min.js',
// //     	'resources/plugins/AdminLTE/plugins/DataTables/pdfmake-0.1.32/vfs_fonts.js',
// //     	'resources/plugins/jquery-validation-1.16.0/dist/jquery.validate.min.js',
// //     	'resources/plugins/jquery-validation-1.16.0/dist/additional-methods.min.js',
// //     	'resources/plugins/toastr/toastr.min.js',
// //     	'resources/plugins/bootstrap-fileinput/fileinput.min.js',
// //     	'resources/plugins/accounting.min.js',
// //     	'resources/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js',
// //     	'resources/plugins/AdminLTE/plugins/daterangepicker/daterangepicker.js',
// //     	'resources/plugins/mousetrap/mousetrap.min.js',
// //     	'resources/plugins/sweetalert/sweetalert.min.js',
// //     	'resources/plugins/bootstrap-tour/bootstrap-tour.min.js',
// //     	'resources/plugins/printThis.js',
// //     	'resources/plugins/AdminLTE/js/AdminLTE-app.js',
// //     	'resources/plugins/calculator/calculator.js',
// //     	'resources/plugins/dropzone/dropzone.js',
// //     	'resources/plugins/jquery.steps/jquery.steps.min.js',
// //         'resources/plugins/fullcalendar/fullcalendar.min.js',
// //         'resources/plugins/fullcalendar/locale-all.js',
// //         'resources/plugins/bootstrap-colorpicker/bootstrap-colorpicker.min.js',
// //         'resources/plugins/decimal.min.js',
// //         'resources/plugins/jKanban/jKanbanBoard.js',
// //         'node_modules/onscan.js/onscan.min.js',
// //         'resources/plugins/jquery.top_scrollbar.js'
// // 	], 'public/js/vendor.js')
// //   .combine([
// //     'public/css/init.css',
// // 		'resources/plugins/AdminLTE/plugins/pace/pace.css',
// // 		'resources/plugins/jquery-ui/jquery-ui.min.css',
// // 		'resources/plugins/bootstrap/css/bootstrap.min.css',
// // 		'resources/plugins/ionicons/css/ionicons.min.css',
// // 		'resources/plugins/AdminLTE/plugins/select2/select2.min.css',
// // 		'resources/plugins/AdminLTE/css/AdminLTE.min.css',
// // 		'resources/plugins/AdminLTE/plugins/datepicker/bootstrap-datepicker.min.css',
// // 		'resources/plugins/AdminLTE/plugins/DataTables/datatables.min.css',
// // 		'resources/plugins/toastr/toastr.min.css',
// // 		'resources/plugins/bootstrap-fileinput/fileinput.min.css',
// // 		'resources/plugins/AdminLTE/css/skins/_all-skins.min.css',
// // 		'resources/plugins/AdminLTE/plugins/daterangepicker/daterangepicker.css',
// // 		'resources/plugins/bootstrap-tour/bootstrap-tour.min.css',
// // 		'resources/plugins/calculator/calculator.css',
// // 		'resources/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css',
// // 		'resources/plugins/dropzone/dropzone.min.css',
// // 		'resources/plugins/jquery.steps/jquery.steps.css',
// //         'resources/plugins/custom.css',
// //         'resources/plugins/fullcalendar/fullcalendar.min.css',
// //         'resources/plugins/bootstrap-colorpicker/bootstrap-colorpicker.min.css',
// //         'resources/plugins/jKanban/jKanbanBoard.css',
// //         'resources/plugins/css-toggle-switch/toggle-switch.css'
// // 	], 'public/css/vendor.css')
// //     .combine([
// //         'resources/plugins/AdminLTE/css/AdminLTE.rtl.min.css',
// //         'resources/plugins/bootstrap/css/bootstrap.rtl.min.css'
// //     ], 'public/css/rtl.css')
// //     .copy('resources/plugins/bootstrap/fonts/glyphicons-halflings-regular.woff2', 'public/fonts/')
// //     .copy('resources/plugins/bootstrap/fonts/glyphicons-halflings-regular.woff', 'public/fonts/')
// //     .copy('resources/plugins/bootstrap/fonts/glyphicons-halflings-regular.ttf', 'public/fonts/')
// //     .copy('resources/plugins/ionicons/fonts/ionicons.ttf', 'public/fonts/ionicons.ttf')
// //     .copyDirectory('node_modules/tinymce/skins/', 'public/js/skins/')
// //     .setResourceRoot('../');
// mix.options({
// 	postCss: [
// 		require('autoprefixer'),
// 	],
// });

// mix.setPublicPath('public');

// module.exports = {
// 	//...
// 	optimization: {
// 		concatenateModules: true
// 	}
// };

// mix.webpackConfig({
// 	resolve: {
// 		extensions: ['.js', '.vue'],
// 		alias: {
// 			'@': __dirname + 'resources'
// 		}
// 	},
// 	output: {
// 		chunkFilename: 'js/chunks/[name].js',
// 	},
// });

// // used to run app using reactjs
// mix.react('resources/pos/src/index.js', 'public/pos-js/pos.js').version();