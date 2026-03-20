import flatpickr from "flatpickr";
import tinymce from 'tinymce';
import Axios from "axios";
import rangePlugin from 'flatpickr/dist/plugins/rangePlugin'

flatpickr('#startDate', {
	enableTime: true,
	altInput: true,
	dateFormat: "Y-m-d H:i",
	altFormat: "d/m/Y H:i",
	disableMobile: true,
	"plugins": [new rangePlugin({ input: "#endDate" })]
});

flatpickr('#inputDate,#inputEndDate', {
	enableTime: true,
	altInput: true,
	dateFormat: "Y-m-d H:i",
	altFormat: "d/m/Y H:i",
	time_24hr: true,
	disableMobile: true
});

tinymce.init({
	selector: 'textarea#full_textarea',
	language: 'pt_BR',
	plugins: 'advlist lists',
	toolbar: 'undo redo bold italic strikethrough | numlist bullist | alignleft aligncenter alignright alignjustify',
	height: "400",
	language_url: '/langs/pt_BR.js',
});

tinymce.init({
	selector: "textarea#full_textarea_banner",
	language: 'pt_BR',
	convert_urls: true,
	relative_urls: false,
	remove_script_host: false,
	paste_data_images: true,
	image_title: true,
	automatic_uploads: true,
	images_upload_url: "/api/upload",
	language_url: '/js/langs/pt_BR.js',
	file_picker_types: "image",
	height: "420",
	plugins: [
		"advlist autolink lists link image charmap print preview hr anchor pagebreak",
		"searchreplace wordcount visualblocks visualchars code fullscreen",
		"insertdatetime media nonbreaking save table contextmenu directionality",
		"template paste textcolor colorpicker textpattern"
	],
	toolbar1:
		"insertfile undo redo | styleselect | link image |bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent",
	toolbar2: "print preview media | forecolor backcolor",
	// override default upload handler to simulate successful upload
	file_picker_callback: function (cb, value, meta) {
		var input = document.createElement("input");
		input.setAttribute("type", "file");
		input.setAttribute("accept", "image/*");
		input.onchange = function () {
			var file = this.files[0];

			var reader = new FileReader();
			reader.readAsDataURL(file);
			reader.onload = function () {
				var id = "blobid" + new Date().getTime();
				var blobCache = tinymce.activeEditor.editorUpload.blobCache;
				var base64 = reader.result.split(",")[1];
				var blobInfo = blobCache.create(id, file, base64);
				blobCache.add(blobInfo);
				cb(blobInfo.blobUri(), { title: file.name });
			};
		};
		input.click();
	},
	setup(editor) {
		editor.on("keydown", function (e) {
			if ((e.keyCode == 8 || e.keyCode == 46) && tinymce.activeEditor.selection) {
				var selectedNode = tinymce.activeEditor.selection.getNode();
				if (selectedNode && selectedNode.nodeName == 'IMG') {
					var imageSrc = selectedNode.src;
					Axios.post("/api/remove_media", { image: imageSrc })
						.then(res => {
							console.log(res)
						})
						.catch(err => {
							console.error(err);
						})
					//here you can call your server to delete the image
				}
			}
		});
	}
});
