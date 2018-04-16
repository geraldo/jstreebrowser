# jstreebrowser

jstreebrowser is a document browser based on [jsTree](http://www.jstree.com/) and it's [filebrowser demo](https://github.com/vakata/jstree-php-demos/tree/master/filebrowser).

jstreebrowser builds a interactive file tree reading a locale file system.

## Supported file types

Actually the following file types are handled, all others are ignored:
 * `.pdf`
 * `.png`, `.jpg`, `.gif`
 * `.txt` recognizes 2 types of content:
   * mails: content starting with `mailto:`
   * urls: content starting with `http://` or `https://`
 * `.map` (map6 and map28): image map to connect .pdfs to areas of an image

File types are easily extensible to adapt the visualization to your needs.

## Installation

- Clone repository onto your web server html directory
- Make file tree inside `data` directory
- Edit parameters in `config.json`:
  * `title`: Title shown as HTML title
  * `data`: Name of file system root directory, has to be in /data directory.

## Demo

Here you find a basic demo: 

Compare it to the plain file structure of this documents: 

## License & Contributing

Copyright (c) 2014 Ivan Bozhanov (http://vakata.com)
Modified work Copyright (c) 2018 Gerald Kogler (http://go.yuri.at)

Licensed under the [MIT license](http://www.opensource.org/licenses/mit-license.php).
