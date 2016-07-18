Dropbox Integration
-------------------

The directory format in dropbox must be 
/website_dir/category/sort_name/
    info.rtf/txt - The details of the entry, in either RTF or plain text.
    primary_image/img.jpg - The primary image for the entry
    synopsis/file.rtf/txt - Must be rtf or txt file and contents will be used as synopsis.
    description/file.rtf/txt - Must be rtf or txt file and contents will be used as description.
    images/img01.jpg - The images in this directory will be added to the entry.
    audio/img01.mp3 - The audio in this directory will be added to the entry.
    video/img01.mp4 - The video in this directory will be added to the entry.
    links/

Notes
-----

The sort_name is also used as the permalink in the url, and as the way to lookup entries. If the sort name changes,
Ciniki will create a new entry and it will be duplicated.


