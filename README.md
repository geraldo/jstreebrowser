# jstreebrowser

jstreebrowser is a document browser based on [jsTree](http://www.jstree.com/) and it's [filebrowser demo](https://github.com/vakata/jstree-php-demos/tree/master/filebrowser).

jstreebrowser builds a interactive file tree reading a locale file system.

## Supported file types

Actually the following file types are handled, all others are ignored:
 * `.pdf`
 * `.txt` recognizes 3 types of content:
   * mails: content using `mailto:`
   * urls: content starting with `http://` or `https://`
   * texto: all other type of content
 * `.map` (map6 and map28): image map to connect .pdfs to areas of an image
 * `.png`, `.jpg`, `.gif`

File types are easily extentible to adapt the visualization to your needs.

## Demo

Here you find a basic demo: 

Compare it to the plain file structure of this documents: 

## License & Contributing

Copyright (c) 2014 Ivan Bozhanov (http://vakata.com)

Licensed under the [MIT license](http://www.opensource.org/licenses/mit-license.php).
