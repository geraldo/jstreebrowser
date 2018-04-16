<?php
ini_set('open_basedir', dirname(__FILE__) . DIRECTORY_SEPARATOR);

class fs
{
	protected $base = null;

	protected function real($path) {
		$temp = realpath($path);
		if(!$temp) { throw new Exception('Path does not exist: ' . $path); }
		if($this->base && strlen($this->base)) {
			if(strpos($temp, $this->base) !== 0) { throw new Exception('Path is not inside base ('.$this->base.'): ' . $temp); }
		}
		return $temp;
	}
	protected function path($id) {
		$id = str_replace('/', DIRECTORY_SEPARATOR, $id);
		$id = trim($id, DIRECTORY_SEPARATOR);
		$id = $this->real($this->base . DIRECTORY_SEPARATOR . $id);
		return $id;
	}
	protected function id($path) {
		$path = $this->real($path);
		$path = substr($path, strlen($this->base));
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		$path = trim($path, '/');
		return strlen($path) ? $path : '/';
	}
	public function __construct($base) {
		$this->base = $this->real($base);
		if(!$this->base) { throw new Exception('Base directory does not exist'); }
	}
	public function lst($id, $with_root = false) {
		$dir = $this->path($id);
		//error_log($dir);
		$lst = @scandir($dir);
		//error_log(json_encode($lst));
		if(!$lst) { throw new Exception('Could not list path: ' . $dir); }
		$res = array();
		foreach($lst as $item) {
			if($item == '.' || $item == '..' || $item === null) { continue; }
			//$tmp = preg_match('([^ a-zа-я-_0-9.]+)ui', $item);
			$tmp = preg_match('/^[\p{L}]+$/ui', $item);
			if($tmp === false || $tmp === 1) { continue; }
			if(is_dir($dir . DIRECTORY_SEPARATOR . $item)) {
				$res[] = array('text' => $item, 'children' => true,  'id' => $this->id($dir . DIRECTORY_SEPARATOR . $item), 'icon' => 'folder');
			}
			else {
				$res[] = array('text' => $item, 'children' => false, 'id' => $this->id($dir . DIRECTORY_SEPARATOR . $item), 'type' => 'file', 'icon' => 'file file-'.substr($item, strrpos($item,'.') + 1));
			}
		}
		if($with_root && $this->id($dir) === '/') {
			$res = array(array('text' => basename($this->base), 'children' => $res, 'id' => '/', 'icon'=>'folder', 'state' => array('opened' => true, 'disabled' => true)));
		}
		return $res;
	}
	public function data($id) {
		if(strpos($id, ":")) {
			$id = array_map(array($this, 'id'), explode(':', $id));
			return array('type'=>'multiple', 'content'=> 'Multiple selected: ' . implode(' ', $id));
		}
		$dir = $this->path($id);
		if(is_dir($dir)) {
			return array('type'=>'folder', 'content'=> $id);
		}
		if(is_file($dir)) {
			$ext = strpos($dir, '.') !== FALSE ? substr($dir, strrpos($dir, '.') + 1) : '';
			$dat = array('type' => $ext, 'content' => '');
			switch($ext) {
				case 'pdf':
					$dat['content'] = $dir;
					break;
				case 'txt':
					$dat['content'] = file_get_contents($dir);
					break;
				case 'map6':
				case 'map28':
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'png':
					$dat['content'] = 'data:'.finfo_file(finfo_open(FILEINFO_MIME_TYPE), $dir).';base64,'.base64_encode(file_get_contents($dir));
					break;
				default:
					$dat['content'] = 'File not recognized: '.$this->id($dir);
					break;
			}
			return $dat;
		}
		throw new Exception('Not a valid selection: ' . $dir);
	}
}

// load jstree configuration file
$config = json_decode(file_get_contents("./config.json"), true);

if(isset($_GET['operation'])) {
	$fs = new fs(dirname(__FILE__) . DIRECTORY_SEPARATOR. 'data' . DIRECTORY_SEPARATOR . $config['data'] . DIRECTORY_SEPARATOR);
	try {
		$rslt = null;
		switch($_GET['operation']) {
			case 'get_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
				$rslt = $fs->lst($node, (isset($_GET['id']) && $_GET['id'] === '#'));
				break;
			case "get_content":
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
				$rslt = $fs->data($node);
				break;
			default:
				throw new Exception('Unsupported operation: ' . $_GET['operation']);
				break;
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($rslt);
	}
	catch (Exception $e) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 500 Server Error');
		header('Status:  500 Server Error');
		echo $e->getMessage();
	}
	die();
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title><?php echo $config['title']; ?></title>
		<meta name="viewport" content="width=device-width" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
		<style>
		html, body { background:#ebebeb; font-size:10px; font-family:Verdana; margin:0; padding:0; }
		#container { min-width:320px; margin:0px auto 0 auto; background:white; border-radius:0px; padding:0px; overflow:hidden; }
		#tree { float:left; width:357px; border-right:1px solid silver; overflow:auto; padding:0px 0; }
		#data { margin-left:320px; }
		#data textarea { margin:0; padding:0; height:100%; border:0; background:white; display:block; line-height:18px; resize:none; }
		#data, #code { font: normal normal normal 12px/18px 'Consolas', monospace !important; }

		#tree .folder { background:url('./file_sprite.png') right bottom no-repeat; }
		#tree .file { background:url('./file_sprite.png') 0 0 no-repeat; }
		#tree .file-pdf { background-position: -18px 0 }
		#tree .file-as { background-position: -36px 0 }
		#tree .file-c { background-position: -72px -0px }
		#tree .file-iso { background-position: -108px -0px }
		#tree .file-htm, #tree .file-html, #tree .file-xml, #tree .file-xsl { background-position: -126px -0px }
		#tree .file-cf { background-position: -162px -0px }
		#tree .file-cpp { background-position: -216px -0px }
		#tree .file-cs { background-position: -236px -0px }
		#tree .file-sql { background-position: -272px -0px }
		#tree .file-xls, #tree .file-xlsx { background-position: -362px -0px }
		#tree .file-h { background-position: -488px -0px }
		#tree .file-crt, #tree .file-pem, #tree .file-cer { background-position: -452px -18px }
		#tree .file-php { background-position: -108px -18px }
		#tree .file-jpg, #tree .file-jpeg, #tree .file-png, #tree .file-gif { background-position: -126px -18px }
		#tree .file-ppt, #tree .file-pptx { background-position: -144px -18px }
		#tree .file-rb { background-position: -180px -18px }
		#tree .file-text, #tree .file-txt, #tree .file-md, #tree .file-log, #tree .file-htaccess { background-position: -254px -18px }
		#tree .file-txt { background-position: -380px -18px }
		#tree .file-map { background-position: -36px -18px }
		#tree .file-doc, #tree .file-docx { background-position: -362px -18px }
		#tree .file-zip, #tree .file-gz, #tree .file-tar, #tree .file-rar { background-position: -416px -18px }
		#tree .file-js { background-position: -434px -18px }
		#tree .file-css { background-position: -144px -0px }
		#tree .file-fla { background-position: -398px -0px }
		map area { cursor: pointer; }
		#tree li[id*='/.'] { display: none; }
		</style>
	</head>
	<body>
		<div id="container" role="main">
			<div id="tree"></div>
			<div id="data">
				<div class="content code" style="display:none;"><textarea id="code"></textarea></div>
				<div class="content pdf">
					<iframe name="docframe" id="docframe" scrolling="auto" frameborder="0" style="width:800px;height:575px;position:absolute"></iframe>
				</div>
				<div class="content folder" style="display:none;"></div>
				<div class="content image" style="display:none; position:relative;"><img src="" alt="" style="display:block; position:absolute; left:50%; top:50%; padding:0; max-height:90%; max-width:90%;" /></div>
				<div class="content map map6" style="display:none; position:relative;">
					<img id="img_6_fulls" src="6_fulls.jpg" border="0" width="935" height="530" orgWidth="935" orgHeight="530" usemap="#6_fulls" alt="" style="display:block; position:absolute; left:510px; top:50%; padding:0; " />
					<map name="6_fulls" id="map_6_fulls">
						<area alt="1" title="1" shape="rect" coords="278,13,468,181" target="_self"/>
						<area alt="2" title="2" shape="rect" coords="468,13,657,181" target="_self"/>
						<area alt="3" title="3" shape="rect" coords="278,181,468,349" target="_self"/>
						<area alt="4" title="4" shape="rect" coords="468,181,657,349" target="_self"/>
						<area alt="5" title="5" shape="rect" coords="278,349,468,517" target="_self"/>
						<area alt="6" title="6" shape="rect" coords="468,349,657,517" target="_self"/>
					</map>
				</div>
				<div class="content map map28" style="display:none; position:relative;">
					<img id="img_28_fulls" src="28_fulls.jpg" border="0" width="935" height="530" orgWidth="935" orgHeight="530" usemap="#28_fulls" alt="" style="display:block; position:absolute; left:510px; top:50%; padding:0;" />
					<map name="28_fulls" id="map_28_fulls">
						<area alt="1" title="1" shape="rect" coords="512,37,575,79" target="_self"/>
						<area id="2" alt="2" title="2" shape="rect" coords="387,162,450,204" target="_self"/>
						<area alt="3" title="3" shape="rect" coords="449,162,512,204" target="_self"/>
						<area alt="4" title="4" shape="rect" coords="387,203,450,245" target="_self"/>
						<area alt="5" title="5" shape="rect" coords="449,203,512,245" target="_self"/>
						<area alt="6" title="6" shape="rect" coords="511,203,574,245" target="_self"/>
						<area alt="7" title="7" shape="rect" coords="387,245,450,287" target="_self"/>
						<area alt="8" title="8" shape="rect" coords="449,245,512,287" target="_self"/>
						<area alt="9" title="9" shape="rect" coords="511,245,574,287" target="_self"/>
						<area alt="10" title="10" shape="rect" coords="638,246,701,288" target="_self"/>
						<area alt="11" title="11" shape="rect" coords="387,287,450,329" target="_self"/>
						<area alt="12" title="12" shape="rect" coords="449,287,512,329" target="_self"/>
						<area alt="13" title="13" shape="rect" coords="511,287,574,329" target="_self"/>
						<area alt="14" title="14" shape="rect" coords="574,287,637,329" target="_self"/>
						<area alt="15" title="15" shape="rect" coords="324,328,387,370" target="_self"/>
						<area alt="16" title="16" shape="rect" coords="387,328,450,370" target="_self"/>
						<area alt="17" title="17" shape="rect" coords="449,328,512,370" target="_self"/>
						<area alt="18" title="18" shape="rect" coords="511,328,574,370" target="_self"/>
						<area alt="19" title="19" shape="rect" coords="574,328,637,370" target="_self"/>
						<area alt="20" title="20" shape="rect" coords="324,370,387,412" target="_self"/>
						<area alt="21" title="21" shape="rect" coords="387,370,450,412" target="_self"/>
						<area alt="22" title="22" shape="rect" coords="449,370,512,412" target="_self"/>
						<area alt="23" title="23" shape="rect" coords="511,370,574,412" target="_self"/>
						<area alt="24" title="24" shape="rect" coords="387,411,450,453" target="_self"/>
						<area alt="25" title="25" shape="rect" coords="449,411,512,453" target="_self"/>
						<area alt="26" title="26" shape="rect" coords="511,411,574,453" target="_self"/>
						<area alt="27" title="27" shape="rect" coords="449,453,512,495" target="_self"/>
						<area alt="28" title="28" shape="rect" coords="511,453,574,495" target="_self"/>
					</map>
				</div>
				<div class="content default" style="text-align:center;">Select a file from the tree.</div>
			</div>
		</div>

		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>
		<script>
		$(function () {
			$(window).resize(function () {
				var h = Math.max($(window).height() - 0, 420);
				$('#container, #data, #tree, #data .content').height(h).filter('.default').css('lineHeight', h + 'px');
			}).resize();

			$('#tree')
			.jstree({
				'core' : {
					'data' : {
						'url' : '?operation=get_node',
						'data' : function (node) {
							return { 'id' : node.id };
						}
					},
					'force_text' : true,
					'themes' : {
						'responsive' : false,
						'variant' : 'small',
						'stripes' : true
					}
				},
				'sort' : function(a, b) {
					return this.get_type(a) === this.get_type(b) ? (this.get_text(a) > this.get_text(b) ? 1 : -1) : (this.get_type(a) >= this.get_type(b) ? 1 : -1);
				},
				'types' : {
					'default' : { 'icon' : 'folder' },
					'file' : { 'valid_children' : [], 'icon' : 'file' }
				},
				'plugins' : ['dnd','sort','types']
			})
			.on('changed.jstree', function (e, data) {
				if (data.node.id.indexOf(".txt") != -1) {
					//txt link
					var url = $($('li[id="'+data.node.id+'"] a')[0]).attr("href");

					// mailto
					if (url.indexOf("mailto:") != -1)
						window.location.href = url;

					// url link
					else if (url.indexOf("http://") != null || url.indexOf("https://") != null)
						window.open(url, "_blank");
				} else {
					//pdf
					if(data && data.selected && data.selected.length) {
						$.get('?operation=get_content&id=' + data.selected.join(':'), function (d) {
							if(d && typeof d.type !== 'undefined') {
								$('#data .content').hide();
								$('#data .pdf').hide();

								switch(d.type) {
									case 'pdf':
										$('#data .pdf').show();
										var basedir = "<?=getcwd()?>/";
										$('#docframe').prop('src', d.content.substring(basedir.length));
										break;
									case 'map6':
									case 'map28':
										$('#data .'+d.type+' img').one('load', function () { $(this).css({'marginTop':'-' + $(this).height()/2 + 'px','marginLeft':'-' + $(this).width()/2 + 'px'}); }).attr('src',d.content);
										$('#data .'+d.type).show();
										$('#docframe').prop('src', '');
										break;
									case 'png':
									case 'jpg':
									case 'jpeg':
									case 'gif':
										$('#data .image img').one('load', function () { $(this).css({'marginTop':'-' + $(this).height()/2 + 'px','marginLeft':'-' + $(this).width()/2 + 'px'}); }).attr('src',d.content);
										$('#data .image').show();
										break;
									default:
										$('#data .default').html(d.content).show();
										break;
								}
							}

							if (data.node.id.indexOf("."+d.type) != -1) {
								var areas = $('#data .'+d.type+' map area');
								var path = data.node.id.split("/");
								if (path.length == 2) {
									var f0 = "data/<?=$config['data']?>/";
									var f1 = path[0];
									var fserie = path[1].split(".")[0];
									var f2 = "/."+fserie+"/";
									var letra = fserie.substring(fserie.length-1,fserie.length).toLowerCase();
									$('.'+d.type+' area').click(function(){
										$('#docframe').prop('src', f0+f1+f2+letra+this.title+'.pdf');
										$('#data .content').hide();
										$('#data .pdf').show();
									});
								}
							}
						});

					}
					else {
						$('#data .content').hide();
						$('#data .default').html('Select a file from the tree.').show();
					}
				}
			})
			.on('init.jstree', function (e, data) {
				console.log("loading");
			})
			.on('loading.jstree', function (e, data) {
				console.log("loading");
			})
			.on('loaded.jstree', function (e, data) {
			    // hide hidden folders starting with .
				/*$("#tree li[id*='/.']").each(function (e, data) {
					console.log(data.id);
					$(this).hide();
				});*/
				//if txt -> add link to a href
				$("#tree li[id*='.txt']").each(function (e, data) {
			    	if(data.id) {
			    		var ele = $(this).find("a");
			    		$.get('?operation=get_content&id=' + data.id, function (d) {
							if(d && typeof d.type !== 'undefined') {
								ele.attr("href", d.content.trim());
								ele.attr("target", "_blank");
							}
						});
					}
			    });
			})
			.on('after_open.jstree', function (e, data) {
			    // hide hidden folders starting with .
				/*$("#tree li[id*='/.']").each(function (e, data) {
					console.log(data.id);
					$(this).hide();
				});*/
				//if txt -> add link to a href
				$("#tree li[id*='.txt']").each(function (e, data) {
			    	if(data.id) {
			    		var ele = $(this).find("a");
			    		$.get('?operation=get_content&id=' + data.id, function (d) {
							if(d && typeof d.type !== 'undefined') {
								ele.attr("href", d.content.trim());
								ele.attr("target", "_blank");
							}
						});
					}
			    });
			})
			.on('open_all.jstree', function (e, data) {
			})
		});
		</script>
	</body>
</html>
