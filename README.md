# October CMS Chunk Import with progress bar
Experimental implementation of Chunk Importer for October CMS. Based on October's original ImportExport behavior.
![Preview of chunk importer with progress bar](http://i.imgur.com/2EZrmnf.png)
# Usage
This plugin comes with test models and controllers, so it's up and ready for testing. Take **Test CSV file** from /assets folder and try to import some data. 

Now import process is splitted into chunks. Each chunk processes 50 records at a time, each chunk is a new AJAX request, and that's how progress bar is being updated.

There's also a Import Log side menu, where You can find user, who imported particular file, imported file name and date it was imported at.





