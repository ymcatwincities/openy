# About this submodule

This submodule provides an Entity browser widget that can be used to upload
multiple files using the DropzoneJS library.

### Test the widget:

1. Enable entity_browser_example module
2. Reimport the configuration for entity_browser.browser.test_files.yml replacing the upload widget with the following wigdet configuration:


		735d146c-a4b2-4327-a057-d109e0905e05:
	  	  settings:
	        upload_location: 'public://'
	        dropzone_description: 'This is a dropzone description'
	        extensions: 'png jpg jpeg gif'
	        max_filesize: '2 M'
	      uuid: 735d146c-a4b2-4327-a057-d109e0905e05
	      weight: 0
	      label: 'Upload files'
	      id: dropzonejs
	      
3. The sample content type should be upldated with the new widget.


