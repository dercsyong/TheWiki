<?php
/**
 * namumark.php - Namu Mark Renderer
 * Copyright (C) 2015 koreapyj koreapyj0@gmail.com
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */

class PlainWikiPage {
	public $title, $text, $lastchanged;
	function __construct($text) {
		$this->title = '(inline wikitext)';
		$this->text = $text;
		$this->lastchanged = time();
	}

	function getPage($name) {
		return new PlainWikiPage('');
	}
}

class MySQLWikiPage {
	public $title, $text, $lastchanged;
	private $sql;
	function __construct($name, $_mysql) {
		if(!($result = $_mysql->query('SELECT `text`, `lastchanged` FROM `documents` WHERE `document` = "'.$_mysql->real_escape_string($name).'"'))) {
			return false;
		}

		if(!($row = $result->fetch_array(MYSQLI_NUM))) {
			return false;
		}
		$this->title = $name;
		$this->text = $row[0];
		$this->lastchanged = $row[1]?strtotime($row[1]):false;
		$this->sql = $_mysql;
	}

	function getPage($name) {
		return new MySQLWikiPage($name, $this->sql);
	}
}

class NamuMark {
	public $prefix, $lastchange;

	function __construct($wtext) {

		$this->list_tag = array(
			array('*', 'ul'),
			array('1.', 'ol class="decimal"'),
			array('A.', 'ol class="upper-alpha"'),
			array('a.', 'ol class="lower-alpha"'),
			array('I.', 'ol class="upper-roman"'),
			array('i.', 'ol class="lower-roman"')
			);

		$this->h_tag = array(
			array('/^====== (.*) ======/', 6),
			array('/^===== (.*) =====/', 5),
			array('/^==== (.*) ====/', 4),
			array('/^=== (.*) ===/', 3),
			array('/^== (.*) ==/', 2),
			array('/^= (.*) =/', 1),

			null
			);

		$this->single_bracket = array(
			array(
				'open'	=> '{{{',
				'close' => '}}}',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '{{|',
				'close' => '|}}',
				'multiline' => false,
				'processor' => array($this,'closureProcessor')),
			array(
				'open'	=> '[[',
				'close' => ']]',
				'multiline' => false,
				'processor' => array($this,'linkProcessor')),
			array(
				'open'	=> '[',
				'close' => ']',
				'multiline' => false,
				'processor' => array($this,'macroProcessor')),

			array(
				'open'	=> '\'\'\'',
				'close' => '\'\'\'',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '\'\'',
				'close' => '\'\'',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '~~',
				'close' => '~~',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '--',
				'close' => '--',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '__',
				'close' => '__',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> '^^',
				'close' => '^^',
				'multiline' => false,
				'processor' => array($this,'textProcessor')),
			array(
				'open'	=> ',,',
				'close' => ',,',
				'multiline' => false,
				'processor' => array($this,'textProcessor'))
			);

		$this->multi_bracket = array(
			array(
				'open'	=> '{{{',
				'close' => '}}}',
				'multiline' => true,
				'processor' => array($this,'renderProcessor'))
			);
			
		$this->macro_processors = array();
		
		$this->WikiPage = $wtext;
		$this->imageAsLink = false;
		$this->wapRender = false;

		$this->toc = array();
		$this->fn = array();
		$this->category = array();
		$this->links = array();
		$this->fn_cnt = 0;
		$this->prefix = '';
		$this->prefix = '';
		$this->included = false;
	}

	public function getLinks() {
		if(empty($this->WikiPage->title))
			return [];

		if(empty($this->links)) {
			$this->whtml = htmlspecialchars(@$this->WikiPage->text);
			$this->whtml = $this->htmlScan($this->whtml);
		}
		return $this->links;
	}

	public function toHtml() {
		if(empty($this->WikiPage->title))
			return '';
		$this->whtml = htmlspecialchars(@$this->WikiPage->text);
		$this->whtml = $this->htmlScan($this->whtml);
		return $this->whtml;
	}

	private function htmlScan($text) {
		$result = '';
		$len = strlen($text);
		$now = '';
		$line = '';

		if(self::startsWith($text, '#') && preg_match('/^#(?:redirect|넘겨주기) (.+)$/im', $text, $target)) {
			array_push($this->links, array('target'=>$target[1], 'type'=>'redirect'));
			//@header('Location: '.$this->prefix.'/'.self::encodeURI($target[1]));
			if(defined('noredirect')){
				return '#redirect '.$target[1];
			}
			
			if(str_replace("http://thewiki.ga/w/", "", $_SERVER['HTTP_REFERER'])==str_replace("+", "%20", urlencode($target[1]))||str_replace("https://thewiki.ga/w/", "", $_SERVER['HTTP_REFERER'])==str_replace("+", "%20", urlencode($target[1]))){
				return '흐음, 잠시만요. <b>같은 문서끼리 리다이렉트 되고 있는 것 같습니다!</b><br>다음 문서중 하나를 수정하여 문제를 해결할 수 있습니다.<hr><a href="/history/'.self::encodeURI($target[1]).'" target="_blank">'.$target[1].'</a><br><a href="/history/'.str_replace("+", "%20", urlencode($_GET['w'])).'" target="_blank">'.$_GET['w'].'</a><hr>문서를 수정했는데 같은 문제가 계속 발생하나요? <a href="'.self::encodeURI($target[1]).'"><b>여기</b></a>를 확인해보세요!';
			} else {
				return 'Redirection... <script> top.location.href = "/w/'.self::encodeURI($target[1]).'"; </script>';
			}
		}

		for($i=0;$i<$len && $i>=0;self::nextChar($text,$i)) {
			$now = self::getChar($text,$i);
			if($line == '' && $now == ' ' && $list = $this->listParser($text, $i)) {
				$result .= ''
					.$list
					.'';
				$line = '';
				$now = '';
				continue;
			}

			if($line == '' && self::startsWith($text, '|', $i) && $table = $this->tableParser($text, $i)) {
				$result .= ''
					.$table
					.'';
				$line = '';
				$now = '';
				continue;
			}

			if($line == '' && self::startsWith($text, '&gt;', $i) && $blockquote = $this->bqParser($text, $i)) {
				$result .= ''
					.$blockquote
					.'';
				$line = '';
				$now = '';
				continue;
			}

			foreach($this->multi_bracket as $bracket) {
				if(self::startsWith($text, $bracket['open'], $i) && $innerstr = $this->bracketParser($text, $i, $bracket)) {
					$result .= ''
						.$this->lineParser($line)
						.$innerstr
						.'';
					$line = '';
					$now = '';
					break;
				}
			}

			if($now == "\n") { // line parse
				$result .= $this->lineParser($line);
				$line = '';
			}
			else
				$line.=$now;
		}
		if($line != '')
			$result .= $this->lineParser($line);

		$result .= $this->printFootnote();

		if(!empty($this->category)) {
			$result .= '<div class="clearfix"></div><div class="wiki-category"><h2>분류</h2><ul>';
			foreach($this->category as $category) {
				$result .= '<li>'.$this->linkProcessor(':분류:'.$category.'|'.$category, '[[').'</li> ';
			}
			$result .= '</div>';
		}
		return $result;
	}

	private function bqParser($text, &$offset) {
		$len = strlen($text);		
		$innerhtml = '';
		for($i=$offset;$i<$len;$i=self::seekEndOfLine($text, $i)+1) {
			$eol = self::seekEndOfLine($text, $i);
			if(!self::startsWith($text, '&gt;', $i)) {
				// table end
				break;
			}
			$i+=4;
			$line = $this->formatParser(substr($text, $i, $eol-$i));
			$line = preg_replace('/^(&gt;)+/', '', $line);
			if($this->wapRender)
				$innerhtml .= $line.'<br/>';
			else
				$innerhtml .= '<p>'.$line.'</p>';
		}
		if(empty($innerhtml))
			return false;

		$offset = $i-1;
		return '<blockquote class="wiki-quote">'.$innerhtml.'</blockquote>';
	}

	private function tableParser($text, &$offset) {
		$len = strlen($text);
		$table = new HTMLElement('table');
		$table->attributes['class'] = 'wiki-table';

		if(!self::startsWith($text, '||', $offset)) {
			// caption
			$caption = new HTMLElement('caption');
			$dummy=0;
			$caption->innerHTML = $this->bracketParser($text, $offset, array('open' => '|','close' => '|','multiline' => true, 'strict' => false,'processor' => function($str) { return $this->formatParser($str); }));
			$table->innerHTML .= $caption->toString();
			$offset++;
		}

		for($i=$offset;$i<$len && ((!empty($caption) && $i === $offset) || (substr($text, $i, 2) === '||' && $i+=2));) {
			if(!preg_match('/\|\|( *?(?:\n|$))/', $text, $match, PREG_OFFSET_CAPTURE, $i) || !isset($match[0]) || !isset($match[0][1]))
				$rowend = -1;
			else {
				$rowend = $match[0][1];
				$endlen = strlen($match[0][0]);
			}
			if($rowend === -1 || !$row = substr($text, $i, $rowend-$i))
				break;
			$i = $rowend+$endlen;
			$row = explode('||', $row);

			$tr = new HTMLElement('tr');
			$simpleColspan = 0;
			foreach($row as $cell) {
				$td = new HTMLElement('td');

				$cell = htmlspecialchars_decode($cell);
				$cell = preg_replace_callback('/<(.+?)>/', function($match) use ($table, $tr, $td) {
					$prop = $match[1];
					switch($prop) {
						case '(':
							break;
						case ':':
							$td->style['text-align'] = 'center';
							break;
						case ')':
							$td->style['text-align'] = 'right';
							break;
						case 'white':
							$td->style['background-color'] = "white";
							break;
						case 'black':
							$td->style['background-color'] = "black";
							break;
						case 'gray':
							$td->style['background-color'] = "gray";
							break;
						case 'red':
							$td->style['background-color'] = "red";
							break;
						case 'pink':
							$td->style['background-color'] = "pink";
							break;
						case 'green':
							$td->style['background-color'] = "green";
							break;
						case 'yellow':
							$td->style['background-color'] = "yellow";
							break;
						case 'dimgray':
							$td->style['background-color'] = "dimgray";
							break;
						case 'midnightblue':
							$td->style['background-color'] = "midnightblue";
							break;
						case 'lightskyblue':
							$td->style['background-color'] = "lightskyblue";
							break;
						case 'orange':
							$td->style['background-color'] = "orange";
							break;
						case 'firebrick':
							$td->style['background-color'] = "firebrick";
							break;
						case 'gold':
							$td->style['background-color'] = "gold";
							break;
						case 'forestgreen':
							$td->style['background-color'] = "forestgreen";
							break;
						case 'orangered':
							$td->style['background-color'] = "orangered";
							break;
						case 'darkslategray':
							$td->style['background-color'] = "darkslategray";
							break;
						default:
							if(self::startsWith($prop, 'table')) {
								$tbprops = explode(' ', $prop);
								foreach($tbprops as $tbprop) {
									if(!preg_match('/^([^=]+)=(?|"(.*)"|\'(.*)\'|(.*))$/', $tbprop, $tbprop))
										continue;
									switch($tbprop[1]) {
										case 'align':
										case 'tablepadding':
											$padding = explode(",", $tbprop[2]); 
											$paddingx = is_numeric($padding[0])?$padding[0].'px':$padding[0];
											$paddingy = is_numeric($padding[1])?$padding[1].'px':$padding[1];
											$paddinga = is_numeric($padding[2])?$padding[2].'px':$padding[2];
											$paddingb = is_numeric($padding[3])?$padding[3].'px':$padding[3];
											$td->style['padding'] = $paddingx." ".$paddingy." ".$paddinga." ".$paddingb;
											break;
										case 'tablealign':
											switch($tbprop[2]) {
												case 'left':
#													$table->style['float'] = 'left';
#													$table->attributes['class'].=' float';
													break;
												case 'center':
													$table->style['margin-left'] = 'auto';
													$table->style['margin-right'] = 'auto';
													break;
												case 'right':
													$table->style['float'] = 'right';
													$table->attributes['class'].=' float';
													break;
											}
											break;
										case 'bgcolor':
											$table->style['background-color'] = $tbprop[2];
											break;
										case 'bordercolor':
											$table->style['border-color'] = $tbprop[2];
											$table->style['border-style'] = 'solid';
											break;
										case 'width':
										case 'tablewidth':
											$table->style['width'] = $tbprop[2];
											break;
									}
								}
							}
							elseif(preg_match('/^(\||\-)([0-9]+)$/', $prop, $span)) {
								if($span[1] == '-') {
									$td->attributes['colspan'] = $span[2];
									break;
								}
								elseif($span[1] == '|') {
									$td->attributes['rowspan'] = $span[2];
									break;
								}
							}
							elseif(preg_match('/^#(?:([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})|([A-Za-z]+))$/', $prop, $span)) {
								$td->style['background-color'] = $span[1]?'#'.$span[1]:$span[2];
								break;
							}
							elseif(preg_match('/^([^=]+)=(?|"(.*)"|\'(.*)\'|(.*))$/', $prop, $htmlprop)) {
								switch($htmlprop[1]) {
									case 'rowbgcolor':
										$tr->style['background-color'] = $htmlprop[2];
										break;
									case 'bgcolor':
										$td->style['background-color'] = $htmlprop[2];
										break;
									case 'width':
										$td->style['width'] = is_numeric($htmlprop[2])?$htmlprop[2].'px':$htmlprop[2];
										break;
									case 'height':
										$td->style['height'] = is_numeric($htmlprop[2])?$htmlprop[2].'px':$htmlprop[2];
										break;
									default:
										return $match[0];
								}
							}
							else {
								return $match[0];
							}
					}
					return '';
				}, $cell);
				$cell = htmlspecialchars($cell);

				$cell = preg_replace('/^ ?(.+) ?$/', '$1', $cell);
				if($cell=='') {
					$simpleColspan += 1;
					continue;
				}

				if($simpleColspan != 0) {
					$td->attributes['colspan'] = $simpleColspan+1;
					$simpleColspan = 0;
				}

				$lines = explode("\n", $cell);
				foreach($lines as $line) {
					$td->innerHTML .= $this->lineParser($line);
				}

				$tr->innerHTML .= $td->toString();
			}
			$table->innerHTML .= $tr->toString();
		}
		$offset = $i-1;
		return $table->toString();
	}

	private function listParser($text, &$offset) {
		$listTable = array();
		$len = strlen($text);
		$lineStart = $offset;

		$quit = false;
		for($i=$offset;$i<$len;$before=self::nextChar($text,$i)) {
			$now = self::getChar($text,$i);
			if($now == "\n" && empty($listTable[0])) {
					return false;
			}
			if($now != ' ') {
				if($lineStart == $i) {
					// list end
					break;
				}

				$match = false;

				foreach($this->list_tag as $list_tag) {
					if(self::startsWith($text, $list_tag[0], $i)) {

						if(!empty($listTable[0]) && $listTable[0]['tag']=='indent') {
							$i = $lineStart;
							$quit = true;
							break;
						}

						$eol = self::seekEndOfLine($text, $lineStart);
						$tlen = strlen($list_tag[0]);
						$innerstr = substr($text, $i+$tlen, $eol-($i+$tlen));
						$this->listInsert($listTable, $innerstr, ($i-$lineStart), $list_tag[1]);
						$i = $eol;
						$now = "\n";
						$match = true;
						break;
					}
				}
				if($quit)
					break;

				if(!$match) {
					// indent
					if(!empty($listTable[0]) && $listTable[0]['tag']!='indent') {
						$i = $lineStart;
						break;
					}

					$eol = self::seekEndOfLine($text, $lineStart);
					$innerstr = substr($text, $i, $eol-$i);
					$this->listInsert($listTable, $innerstr, ($i-$lineStart), 'indent');
					$i = $eol;
					$now = "\n";
				}
			}
			if($now == "\n") {
				$lineStart = $i+1;
			}
		}
		if(!empty($listTable[0])) {
			$offset = $i-1;
			return $this->listDraw($listTable);
		}
		return false;
	}

	private function listInsert(&$arr, $text, $level, $tag) {
		if(preg_match('/^#([1-9][0-9]*) /', $text, $start))
			$start = $start[1];
		else
			$start = 1;
		if(empty($arr[0])) {
			$arr[0] = array('text' => $text, 'start' => $start, 'level' => $level, 'tag' => $tag, 'childNodes' => array());
			return true;
		}

		$last = count($arr)-1;
		$readableId = $last+1;
		if($arr[0]['level'] >= $level) {
			$arr[] = array('text' => $text, 'start' => $start, 'level' => $level, 'tag' => $tag, 'childNodes' => array());
			return true;
		}
		
		return $this->listInsert($arr[$last]['childNodes'], $text, $level, $tag);
	}

	private function listDraw($arr) {
		if(empty($arr[0]))
			return '';

		$tag = $arr[0]['tag'];
		$start = $arr[0]['start'];
		$result = '<'.($tag=='indent'?'div class="indent"':$tag.($start!=1?' start="'.$start.'"':'')).'>';
		foreach($arr as $li) {
			$text = $this->blockParser($li['text']).$this->listDraw($li['childNodes']);
			$result .= $tag=='indent'?$text:'<li>'.$text.'</li>';
		}
		$result .= '</'.($tag=='indent'?'div':$tag).'>';
		return $result;
	}

	private function lineParser($line) {
		$result = '';
		$line_len = strlen($line);

		// comment
		if(self::startsWith($line, '##')) {
			$line = '';
		}

		// == Title ==
		if(self::startsWith($line, '=') && preg_match('/^(=+) (.*) (=+)[ ]*$/', $line, $match) && $match[1]===$match[3]) {
			$level = strlen($match[1]);
			$innertext = $this->blockParser($match[2]);
			$id = $this->tocInsert($this->toc, $innertext, $level);
			$result .= '<br><h'.$level.' id="s-'.$id.'"><a name="s-'.$id.'" href="#toc">'.$id.'</a>. '.$innertext.'</h'.$level.'><hr>';
			$line = '';
		}

		// hr
		if($line == '----') {
			$result .= '<hr>';
			$line = '';
		}

		$line = $this->blockParser($line);

		if($line != '') {
			if($this->wapRender)
				$result .= $line.'<br/><br/>';
			else
				$result .= $line.'<br/>';
		}

		return $result;
	}

	private function blockParser($block) {
		return $this->formatParser($block);
	}

	private function bracketParser($text, &$now, $bracket) {
		$len = strlen($text);
		$cnt = 0;
		$done = false;

		$openlen = strlen($bracket['open']);
		$closelen = strlen($bracket['close']);

		if(!isset($bracket['strict']))
			$bracket['strict'] = true;

		for($i=$now;$i<$len;self::nextChar($text,$i)) {
			if(self::startsWith($text, $bracket['open'], $i) && !($bracket['open']==$bracket['close'] && $cnt>0)) {
				$cnt++;
				$done = true;
				$i+=$openlen-1; // 반복될 때 더해질 것이므로
			}elseif(self::startsWith($text, $bracket['close'], $i)) {
				$cnt--;
				$i+=$closelen-1;
			}elseif(!$bracket['multiline'] && $text[$i] == "\n")
				return false;

			if($cnt == 0 && $done) {
				$innerstr = substr($text, $now+$openlen, $i-$now-($openlen+$closelen)+1);

				if(($bracket['strict'] && $bracket['multiline'] && strpos($innerstr, "\n")===false))
					return false;
				$result = call_user_func_array($bracket['processor'],array($innerstr, $bracket['open']));
				$now = $i;
				return $result;
			}
		}
		return false;
	}

	private function formatParser($line) {
		$line_len = strlen($line);
		for($j=0;$j<$line_len;self::nextChar($line,$j)) {
			if(self::startsWith($line, 'http', $j) && preg_match('/(https?:\/\/[^ ]+\.(jpg|jpeg|png|gif))(?:\?([^ ]+))?/i', $line, $match, 0, $j)) {
				if($this->imageAsLink)
					$innerstr = '<span class="alternative">[<a class="external" target="_blank" href="'.$match[1].'">image</a>]</span>';
				else {
					$paramtxt = '';
					$csstxt = '';
					if(!empty($match[3])) {
						preg_match_all('/[&?]?([^=]+)=([^\&]+)/', htmlspecialchars_decode($match[3]), $param, PREG_SET_ORDER);
						foreach($param as $pr) {
							switch($pr[1]) {
								case 'width':
									if(preg_match('/^[0-9]+$/', $pr[2]))
										$csstxt .= 'width: '.$pr[2].'px; ';
									else
										$csstxt .= 'width: '.$pr[2].'; ';
									break;
								case 'height':
									if(preg_match('/^[0-9]+$/', $pr[2]))
										$csstxt .= 'height: '.$pr[2].'px; ';
									else
										$csstxt .= 'height: '.$pr[2].'; ';
									break;
								case 'align':
									if($pr[2]!='center')
										$csstxt .= 'float: '.$pr[2].'; ';
									break;
								default:
									$paramtxt.=' '.$pr[1].'="'.$pr[2].'"';
							}
						}
					}
					$paramtxt .= ($csstxt!=''?' style="'.$csstxt.'"':'');
					//$innerstr = '<img src="'.$match[1].'"'.$paramtxt.'>';
				}
				$line = substr($line, 0, $j).$innerstr.substr($line, $j+strlen($match[0]));
				$line_len = strlen($line);
				$j+=strlen($innerstr)-1;
				continue;
			}elseif(self::startsWith($line, 'attachment', $j) && preg_match('/attachment:([^\/]*\/)?([^ ]+\.(?:jpg|jpeg|png|gif))(?:\?([^ ]+))?/i', $line, $match, 0, $j)) {
				if($this->imageAsLink)
					$innerstr = '<span class="alternative">[<a class="external" target="_blank" href="https://attachment.namu.wiki/'.($match[1]?($match[1]=='' || substr($match[1], 0, -1)==''?'':substr($match[1], 0, -1).'__'):rawurlencode($this->WikiPage->title).'__').$match[2].'">image</a>]</span>';
				else {
					$paramtxt = '';
					$csstxt = '';
					if(!empty($match[3])) {
						preg_match_all('/([^=]+)=([^\&]+)/', $match[3], $param, PREG_SET_ORDER);
						foreach($param as $pr) {
							switch($pr[1]) {
								case 'width':
									if(preg_match('/^[0-9]+$/', $pr[2]))
										$csstxt .= 'width: '.$pr[2].'px; ';
									else
										$csstxt .= 'width: '.$pr[2].'; ';
									break;
								case 'height':
									if(preg_match('/^[0-9]+$/', $pr[2]))
										$csstxt .= 'height: '.$pr[2].'px; ';
									else
										$csstxt .= 'height: '.$pr[2].'; ';
									break;
								case 'align':
									if($pr[2]!='center')
										$csstxt .= 'float: '.$pr[2].'; ';
									break;
								default:
									$paramtxt.=' '.$pr[1].'="'.$pr[2].'"';
							}
						}
					}
					$paramtxt .= ($csstxt!=''?' style="'.$csstxt.'"':'');
					$innerstr = '<img src="https://attachment.namu.wiki/'.($match[1]?($match[1]=='' || substr($match[1], 0, -1)==''?'':substr($match[1], 0, -1).'__'):rawurlencode($this->WikiPage->title).'__').$match[2].'"'.$paramtxt.'>';
				}
				$line = substr($line, 0, $j).$innerstr.substr($line, $j+strlen($match[0]));
				$line_len = strlen($line);
				$j+=strlen($innerstr)-1;
				continue;
			} else {
				if(preg_match('/^{{{#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3}) (.*)$/', $line, $match)) {
					if(count(explode("}}}", $match[0]))<=1){
						$this->color_temp_line[] = $line;
						$this->color = $match[1];
						$this->finded_color_line = true;
						return;
					}
				}
				if(count(explode("}}}", $line))>1&&count($this->color_temp_line)>0&&$this->finded_color_line) {
					$line = implode("}}}{{{#!html <br>}}}{{{#".$this->color." ", $this->color_temp_line)." }}}{{{#!html <br>}}}{{{#".$this->color." ".$line;
					unset($this->color_temp_line);
					$this->finded_color_line = false;
				}
				if($this->finded_color_line){
					$this->color_temp_line[] = $line;
					return;
				}
				foreach($this->single_bracket as $bracket) {
					$nj=$j;
					if(self::startsWith($line, $bracket['open'], $j) && $innerstr = $this->bracketParser($line, $nj, $bracket)) {
						$line = substr($line, 0, $j).$innerstr.substr($line, $nj+1);
						$line_len = strlen($line);
						$j+=strlen($innerstr)-1;
						break;
					}
				}
			}
		}
		return $line;
	}

	private function renderProcessor($text, $type) {
		if(self::startsWithi($text, '#!html')) {
			$html = substr($text, 6);
			$html = ltrim($html);
			$html = htmlspecialchars_decode($html);
			$html = self::inlineHtml($html);
			return $html;
		}
		return '<pre><code>'.substr($text, 1).'</code></pre>';
	}
	
	private function closureProcessor($text, $type) {
		return '<div class="wiki-closure">'.$this->formatParser($text).'</div>';
	}

	private function linkProcessor($text, $type) {
		$href = explode('|', $text);
		if(preg_match('/^https?:\/\//', $href[0])) {
			$targetUrl = $href[0];
			$class = 'wiki-link-external';
			$target = '_blank';
		}
		elseif(preg_match('/^분류:(.+)$/', $href[0], $category)) {
			array_push($this->links, array('target'=>$category[0], 'type'=>'category'));
			if(!$this->included)
				array_push($this->category, $category[1]);
			return ' ';
		}
		elseif(preg_match('/^파일:(.+)$/', $href[0], $category)) {
			array_push($this->links, array('target'=>$category[0], 'type'=>'file'));
			if($this->imageAsLink)
				return '<span class="alternative">[<a target="_blank" href="'.self::encodeURI($category[0]).'">image</a>]</span>';
			
			$paramtxt = '';
			$csstxt = '';
			if(!empty($href[1])) {
				preg_match_all('/[&?]?([^=]+)=([^\&]+)/', htmlspecialchars_decode($href[1]), $param, PREG_SET_ORDER);
				foreach($param as $pr) {
					switch($pr[1]) {
						case 'width':
							if(preg_match('/^[0-9]+$/', $pr[2]))
								$csstxt .= 'width: '.$pr[2].'px; ';
							else
								$csstxt .= 'width: '.$pr[2].'; ';
							break;
						case 'height':
							if(preg_match('/^[0-9]+$/', $pr[2]))
								$csstxt .= 'height: '.$pr[2].'px; ';
							else
								$csstxt .= 'height: '.$pr[2].'; ';
							break;
						case 'align':
							if($pr[2]!='center')
								$csstxt .= 'float: '.$pr[2].'; ';
							break;
						default:
							$paramtxt.=' '.$pr[1].'="'.$pr[2].'"';
					}
				}
			}
			$paramtxt .= ($csstxt!=''?' style="'.$csstxt.'"':'');
			
			$xd = md5($category[0].rand(1,50));
			$ext = strtolower(end(explode(".", $category[0])));
			$hash = sha1($category[0], FALSE);
			
			if(is_file("../files/".$hash.".".$ext)){
				$google_photos_check = fopen("../files/".$hash.".".$ext, "r");
				$google_photos = fread($google_photos_check, 158);
				fclose($google_photos_check);
				if(substr($google_photos, 0, 8)=="https://"){
					return '<img src="'.$google_photos.'" '.trim(str_replace('style="', 'style="cursor:hand; ', $paramtxt)).'>';
				} else {
					return '<img src="/files/'.$hash.'.'.$ext.'" '.trim(str_replace('style="', 'style="cursor:hand; ', $paramtxt)).'>';
				}
			}
			$img = "SELECT * FROM file WHERE name = binary('$category[0]') LIMIT 1";
			$imgres = mysqli_query($config_db, $img);
			$imgarr = mysqli_fetch_array($imgres);
			mysqli_close($conn);
			if($imgarr['google']!=""){
				return '<img src="'.$imgarr['google'].'" '.trim(str_replace('style="', 'style="cursor:hand; ', $paramtxt)).'>';
			} else if($imgarr['dir']!=""){
				//return '[ No.'.$imgarr['no'].' ] 이미지 작업 대기중';
				return '<img src="//images.thewiki.ga/'.$imgarr['dir'].'" '.trim(str_replace('style="', 'style="cursor:hand; ', $paramtxt)).'>';
			} else {
				return '<script type="text/javascript"> $(document).ready(function(){ $.post("//thewiki.ga/API.php", {w:"'.$category[0].'", p:"'.str_replace('"', '\"', $paramtxt).'"}, function(data){ $("#ajax_file_'.$xd.'").html(data); $("#ajax_file_'.$xd.'").prepend("<input type=\'hidden\' id=\'enableajax_'.$xd.'\' value=\'false\'>"); $("#ajax_file_'.$xd.' > img").unwrap(); }, "html"); }); </script><div id="ajax_file_'.$xd.'" style="z-index:-1;"><table class="wiki-table" style=""><tbody><tr><td style="background-color:#93C572; text-align:center;"><p><span class="wiki-size size-1"><font color="006400">'.$category[0].' 이미지 표시중</font></span></p></td></tr></tbody></table></div>';
			}
		}
		elseif(preg_match('/^이미지:(.+)$/', $href[0], $category)) {
			array_push($this->links, array('target'=>$category[0], 'type'=>'file'));
			if($this->imageAsLink)
				return '<span class="alternative">[<a target="_blank" href="'.self::encodeURI($category[0]).'">image</a>]</span>';
			
			$paramtxt = '';
			$csstxt = '';
			if(!empty($href[1])) {
				preg_match_all('/[&?]?([^=]+)=([^\&]+)/', htmlspecialchars_decode($href[1]), $param, PREG_SET_ORDER);
				foreach($param as $pr) {
					switch($pr[1]) {
						case 'width':
							if(preg_match('/^[0-9]+$/', $pr[2]))
								$csstxt .= 'width: '.$pr[2].'px; ';
							else
								$csstxt .= 'width: '.$pr[2].'; ';
							break;
						case 'height':
							if(preg_match('/^[0-9]+$/', $pr[2]))
								$csstxt .= 'height: '.$pr[2].'px; ';
							else
								$csstxt .= 'height: '.$pr[2].'; ';
							break;
						case 'align':
							if($pr[2]!='center')
								$csstxt .= 'float: '.$pr[2].'; ';
							break;
						default:
							$paramtxt.=' '.$pr[1].'="'.$pr[2].'"';
					}
				}
			}
			$paramtxt .= ($csstxt!=''?' style="'.$csstxt.'"':'');
			return '<img src="/customupload/'.mb_substr($category[0], 4, strlen($category[0]), "UTF-8").'" '.trim(str_replace('style="', 'style="cursor:hand; ', $paramtxt)).'>';
		}
		else {
			if(self::startsWith($href[0], ':')) {
				$href[0] = substr($href[0], 1);
				$c=1;
			}
			$targetUrl = $this->prefix.'/'.self::encodeURI($href[0]);
			if($this->wapRender && !empty($href[1]))
				$title = $href[0];
			if(empty($c))
				array_push($this->links, array('target'=>$href[0], 'type'=>'link'));
		}
		return '<a href="'.$targetUrl.'"'.(!empty($title)?' title="'.$title.'"':'').(!empty($class)?' class="'.$class.'"':'').(!empty($target)?' target="'.$target.'"':'').'>'.(!empty($href[1])?$this->formatParser($href[1]):$href[0]).'</a>';
	}

	private function macroProcessor($text, $type) {
		$macroName = strtolower($text);
		if(!empty($this->macro_processors[$macroName]))
			return $this->macro_processors[$macroName]();
		switch($macroName) {
			case 'br':
				return '<br>';
			case 'view(count)':
				$sql = "SELECT sum(count) AS result FROM wiki_count";
				$res = mysqli_query($wiki_db, $sql);
				$row = mysqli_fetch_assoc($res); 
				if(empty($row['result'])){
					$row['result'] = ' 0';
				}
				return $row['result'];
			case 'view(recent)':
				$sql = "SELECT count(*) AS result FROM wiki_contents_history";
				$res = mysqli_query($wiki_db, $sql);
				$row = mysqli_fetch_array($res); 
				if(empty($row['result'])){
					$row['result'] = ' 0';
				}
				return $row['result'];
			case 'date':
				return date('Y-m-d H:i:s');
			case '목차':
			case 'tableofcontents':
				return $this->printToc();
			case '각주':
			case 'footnote':
				return $this->printFootnote();
			default:
				if(self::startsWithi(strtolower($text), 'include') && preg_match('/^include\((.+)\)$/i', $text, $include) && $include = $include[1]) {
					if($this->included)
						return ' ';
					$include = explode(',', $include);
					array_push($this->links, array('target'=>$include[0], 'type'=>'include'));
					
					$w = $include[0];
					if(count(explode(":", $w))>1){
						$tp = explode(":", $w);
						switch($tp[0]){
							case '틀':
								$namespace = '1';
								break;
							case '분류':
								$namespace = '2';
								break;
							case '파일':
								$namespace = '3';
								break;
							case '사용자':
								$namespace = '4';
								break;
							case '나무위키':
								$namespace = '6';
								break;
							case '휴지통':
								$namespace = '8';
								break;
							case 'TheWiki':
								$namespace = '10';
								break;
							case '이미지':
								$namespace = '11';
								break;
							default:
								$namespace = '0';
						
						}
						if($namespace>0){
							$w = str_replace($tp[0].":", "", implode(":", $tp));
						}
					}
					
					$_POST = array('namespace'=>$namespace, 'title'=>$w, 'ip'=>$_SERVER['REMOTE_ADDR'], 'option'=>'original');
					include $_SERVER['DOCUMENT_ROOT'].'/API.php';
					
					if($api_result->status!='success'||$api_result->type=='refresh'){
						return ' ';
					} else {
						$arr['text'] = $api_result->data;
						unset($api_result);
					}
					
					if(defined("isdeleted")){
						return ' ';
					}
					
					// themark 통합
					$arr['text'] = simplemark($arr['text']);
					
					// #!folding 문법 우선 적용
					$foldingstart = explode('{{{#!folding ', $arr['text']);
					for($z=1;$z<count($foldingstart);$z++){
						$foldingcheck = true;
						$foldopentemp = reset(explode("
", $foldingstart[$z]));
						if(count(explode("#!end}}}", $foldingstart[$z]))>1){
							$foldingtemp = str_replace("#!end}}}", "_(FOLDINGEND)_", $foldingstart[$z]);
							$foldingdatatemp = next(explode($foldopentemp, reset(explode("_(FOLDINGEND)_", $foldingtemp))));
							$md5 = md5(rand(1,10).$foldingdatatemp);
							$foldopen[$md5] = $foldopentemp;
							$foldingdata[$md5] = $foldingdatatemp;
							$arr['text'] = str_replace("{{{#!folding ".$foldopentemp.$foldingdatatemp."#!end}}}", "_(FOLDINGSTART)_".$md5."_(FOLDINGSTART2)_ _(FOLDINGDATA)_".$md5."_(FOLDINGDATA2)_ _(FOLDINGEND)_", $arr['text']);
						}
					}
					
					if($arr['text']!="") {
						foreach($include as $var) {
							$var = explode('=', ltrim($var));
							if(empty($var[1]))
								$var[1]='';
							$arr['text'] = str_replace('@'.$var[0].'@', $var[1], $arr['text']);
						}
						
						$wPage2 = new PlainWikiPage($arr['text']);
						$child = new NamuMark($wPage2);
						$child->prefix = $this->prefix;
						$child->imageAsLink = $this->imageAsLink;
						$child->wapRender = $this->wapRender;
						$child->included = true;
						$twPrint = $child->toHtml();
						
						// #!folding
						if($foldingcheck){
							$twPrint = str_replace('_(FOLDINGEND)_', '</div></dd></dl>', $twPrint);
							
							$getmd5 = explode("_(FOLDINGDATA)_", $twPrint);
							for($xz=1;$xz<count($getmd5);$xz++){
								$mymd5 = reset(explode("_(FOLDINGDATA2)_", $getmd5[$xz]));
								$twPrint = str_replace('_(FOLDINGSTART)_'.$mymd5.'_(FOLDINGSTART2)_', '<dl class="wiki-folding"><dt><center>'.$foldopen[$mymd5].'</center></dt><dd style="display: none;"><div class="wiki-table-wrap">', $twPrint);
								
								$fPage = new PlainWikiPage($foldingdata[$mymd5]);
								$child = new NamuMark($fPage);
								$child->prefix = $this->prefix;
								$child->imageAsLink = $this->imageAsLink;
								$child->wapRender = $this->wapRender;
								$child->included = true;
								$fwPrint = $child->toHtml();
								
								$twPrint = str_replace('<div class="wiki-table-wrap"> _(FOLDINGDATA)_'.$mymd5.'_(FOLDINGDATA2)_ </div>', '<div class="wiki-table-wrap"> '.$fwPrint.' </div>', $twPrint);
							}
						}
						
						return $twPrint;
					}
					return ' ';
				}
				elseif(self::startsWith(strtolower($text), 'youtube') && preg_match('/^youtube\((.+)\)$/i', $text, $include) && $include = $include[1]) {
					$include = explode(',', $include);
					$var = array();
					foreach($include as $v) {
						$v = explode('=', $v);
						if(empty($v[1]))
							$v[1]='';
						$var[$v[0]] = $v[1];
					}
					return '<iframe width="'.(!empty($var['width'])?$var['width']:'640').'" height="'.(!empty($var['height'])?$var['height']:'360').'" src="//www.youtube.com/embed/'.$include[0].'" frameborder="0" allowfullscreen></iframe>';
				}
				elseif(self::startsWith(strtolower($text), 'nicovideo') && preg_match('/^nicovideo\((.+)\)$/i', $text, $include) && $include = $include[1]) {
					$include = explode(',', $include);
					$var = array();
					foreach($include as $v) {
						$v = explode('=', $v);
						if(empty($v[1]))
							$v[1]='';
						$var[$v[0]] = $v[1];
					}
					return '<script type="application/javascript" src="http://embed.nicovideo.jp/watch/'.$include[0].'/script?w='.(!empty($var['width'])?$var['width']:'640').'&h='.(!empty($var['height'])?$var['height']:'360').'"></script>';
				}
				elseif(self::startsWithi(strtolower($text), 'age') && preg_match('/^age\((.+)\)$/i', $text, $include) && $include = $include[1]) {
					$include = explode('-', $include);
					$age = (date("md", date("U", mktime(0, 0, 0, $include[1], $include[2], $include[0]))) > date("md")
						? ((date("Y") - $include[0]) - 1)
						: (date("Y") - $include[0]));
					return $age;
					
				}
				elseif(self::startsWithi(strtolower($text), 'anchor') && preg_match('/^anchor\((.+)\)$/i', $text, $include) && $include = $include[1]) {
					return '<a name="'.$include.'"></a>';
				}
				elseif(self::startsWithi(strtolower($text), 'dday') && preg_match('/^dday\((.+)\)$/i', $text, $include) && $include = $include[1]) {
					$nDate = date("Y-m-d", time());
					if(strtotime($nDate)==strtotime($include)){
						return " 0";
					}
					return intval((strtotime($nDate)-strtotime($include)) / 86400);
				}
				elseif(self::startsWith($text, '*') && preg_match('/^\*([^ ]*)([ ].+)?$/', $text, $note)) {
					$notetext = !empty($note[2])?$this->blockParser($note[2]):'';
					$id = $this->fnInsert($this->fn, $notetext, $note[1]);
					$preview = $notetext;
					$preview2 = strip_tags($preview, '<img>');
					$preview = strip_tags($preview);
					$preview = str_replace('"', '\\"', $preview);
					return '<script type="text/javascript"> $(document).ready(function(){ $("#rfn-'.htmlspecialchars($id).'").hover(function(){ $("#Modalrfn-'.htmlspecialchars($id).'").attr("style", "display: block;"); }); $("#Modalrfn-'.htmlspecialchars($id).'").on("click", function(){ $("#Modalrfn-'.htmlspecialchars($id).'").attr("style", "display: none;"); }); $("#rfn-'.htmlspecialchars($id).'").bind("touchend", function(){ $("#Modalrfn-'.htmlspecialchars($id).'").attr("style", "display: block;"); }); $("#Modalrfn-'.htmlspecialchars($id).'").bind("touchstart", function(){ $("#Modalrfn-'.htmlspecialchars($id).'").attr("style", "display: none;"); }); }); </script><a id="rfn-'.htmlspecialchars($id).'" class="wiki-fn-content" href="#fn-'.rawurlencode($id).'" title="'.$preview.'">['.($note[1]?$note[1]:$id).']</a><div class="modal in" id="Modalrfn-'.htmlspecialchars($id).'" style="display: none;"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-body"> '.$preview2.'</div></div></div></div>';
				}
		}
		return '['.$text.']';
	}

	private function textProcessor($otext, $type) {
		if($type != '{{{')
			$text = $this->formatParser($otext);
		else
			$text = $otext;
		switch($type) {
			case '\'\'\'':
				return '<strong>'.$text.'</strong>';
			case '\'\'':
				return '<em>'.$text.'</em>';
			case '--':
			case '~~':
				return '<del>'.$text.'</del>';
			case '__':
				return '<u>'.$text.'</u>';
			case '^^':
				return '<sup>'.$text.'</sup>';
			case ',,':
				return '<sub>'.$text.'</sub>';
			case '{{{':
				if(self::startsWith($text, '#!html')) {
					$html = substr($text, 6);
					$html = ltrim($html);
					$html = htmlspecialchars_decode($html);
					$html = self::inlineHtml($html);
#					echo $html;
					return $html;
				}
				if(preg_match('/^#(?:([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})|([A-Za-z]+)) (.*)$/', $text, $color)) {
					if(empty($color[1]) && empty($color[2]))
						return $text;
					return '<span style="color: '.(empty($color[1])?$color[2]:'#'.$color[1]).'">'.$this->formatParser($color[3]).'</span>';
				}
				if(preg_match('/^\+([1-5]) (.*)$/', $text, $size)) {
					return '<span class="wiki-size size-'.$size[1].'">'.$this->formatParser($size[2]).'</span>';
				}
				return '<code>'.$text.'</code>';
		}
		return $type.$text.$type;
	}

	private function fnInsert(&$arr, &$text, $id = null) {
		$arr_cnt = count($arr);
		if(empty($id)) {
			$multi = false;
			$id = ++$this->fn_cnt;
		}
		else {
			$multi = true;
			for($i=0;$i<$arr_cnt;$i++) {
				if($arr[$i]['id']==$id) {
					$arr[$i]['count']++;
					if(!empty(trim($text)))
						$arr[$i]['text'] = $text;
					else
						$text = $arr[$i]['text'];
					return $id.'-'.$arr[$i]['count'];
				}
			}
		}
		$arr[] = array('id' => $id, 'text' => $text, 'count' => 1);
		return $multi?$id.'-1':$id;
	}

	private function printFootnote() {
		if(count($this->fn)==0)
			return '';

		$result = $this->wapRender?'<hr>':'<div class="wiki-macro-footnote">';
		foreach($this->fn as $k => $fn) {
			$result .= $this->wapRender?'<p>':'<span class="footnote-list">';
			if($fn['count']>1) {
				$result .= '['.$fn['id'].'] ';
				for($i=0;$i<$fn['count'];$i++) {
					$result .= '<span class="target" id="fn-'.htmlspecialchars($fn['id']).'-'.($i+1).'"></span><a href="#rfn-'.rawurlencode($fn['id']).'-'.($i+1).'">'.chr(ord('A') + $i).'</a> ';
				}
			}
			else {
				$result .= '<a id="fn-'.htmlspecialchars($fn['id']).'" href="#rfn-'.$fn['id'].'">['.$fn['id'].']</a> ';
			}
			$result .= $this->blockParser($fn['text'])
								.($this->wapRender?'<br>':'</span>');
		}
		$result .= $this->wapRender?'':'</div>';
		$this->fn = array();
		return $result;
	}

	private function tocInsert(&$arr, $text, $level, $path = '') {
		if(empty($arr[0])) {
			$arr[0] = array('name' => $text, 'level' => $level, 'childNodes' => array());
			return $path.'1';
		}

		$last = count($arr)-1;
		$readableId = $last+1;
		if($arr[0]['level'] >= $level) {
			$arr[] = array('name' => $text, 'level' => $level, 'childNodes' => array());
			return $path.($readableId+1);
		}
		
		return $this->tocInsert($arr[$last]['childNodes'], $text, $level, $path.$readableId.'.');
	}

	private function hParse(&$text) {
		$lines = explode("\n", $text);
		$result = '';
		foreach($lines as $line) {
			$matched = false;

			foreach($this->h_tag as $tag_ar) {
				$tag = $tag_ar[0];
				$level = $tag_ar[1];
				if(!empty($tag) && preg_match($tag, $line, $match)) {
					$this->tocInsert($this->toc, $this->blockParser($match[1]), $level);
					$matched = true;
					break;
				}
			}
		}

		return $result;
	}

	private function printToc(&$arr = null, $level = -1, $path = '') {
		if($level == -1) {
			$bak = $this->toc;
			$this->toc = array();
			$this->hParse($this->WikiPage->text);
			$result = ''
				.'<div id="toc" class="wiki-macro-toc">'
			//		.($this->wapRender!==false?'<h2>목차</h2>':'')
					.$this->printToc($this->toc, 0)
				.'</div>'
				.'';
			$this->toc = $bak;
			return $result;
		}

		if(empty($arr[0]))
			return '';

		$result  = '<div class="toc-indent">';
		foreach($arr as $i => $item) {
			$readableId = $i+1;
			$result .= '<div><a href="#s-'.$path.$readableId.'">'.$path.$readableId.'</a>. '.$item['name'].'</div>'
							.$this->printToc($item['childNodes'], $level+1, $path.$readableId.'.')
							.'';
		}
		$result .= '</div>';
		return $result;
	}

	private static function getChar($string, $pointer){
		if(!isset($string[$pointer])) return false;
		$char = ord($string[$pointer]);
		if($char < 128){
			return $string[$pointer];
		}else{
			if($char < 224){
				$bytes = 2;
			}elseif($char < 240){
				$bytes = 3;
			}elseif($char < 248){
				$bytes = 4;
			}elseif($char == 252){
				$bytes = 5;
			}else{
				$bytes = 6;
			}
			$str = substr($string, $pointer, $bytes);
			return $str;
		}
	}

	private static function nextChar($string, &$pointer){
		if(!isset($string[$pointer])) return false;
		$char = ord($string[$pointer]);
		if($char < 128){
			return $string[$pointer++];
		}else{
			if($char < 224){
				$bytes = 2;
			}elseif($char < 240){
				$bytes = 3;
			}elseif($char < 248){
				$bytes = 4;
			}elseif($char == 252){
				$bytes = 5;
			}else{
				$bytes = 6;
			}
			$str = substr($string, $pointer, $bytes);
			$pointer += $bytes;
			return $str;
		}
	}

	private static function startsWith($haystack, $needle, $offset = 0) {
		$len = strlen($needle);
		if(($offset+$len)>strlen($haystack))
			return false;
		return $needle == substr($haystack, $offset, $len);
	}

	private static function startsWithi($haystack, $needle, $offset = 0) {
		$len = strlen($needle);
		if(($offset+$len)>strlen($haystack))
			return false;
		return strtolower($needle) == strtolower(substr($haystack, $offset, $len));
	}

	private static function seekEndOfLine($text, $offset=0) {
		return self::seekStr($text, "\n", $offset);
	}

	private static function seekStr($text, $str, $offset=0) {
		if($offset >= strlen($text) || $offset < 0)
			return strlen($text);
		return ($r=strpos($text, $str, $offset))===false?strlen($text):$r;
	}

	private static function inlineHtml($html) {
		$html = str_replace("\n", '', $html);
		$html = preg_replace('/<\/?(?:object|param)[^>]*>/', '', $html);
		$html = preg_replace('/<embed([^>]+)>/', '<iframe$1 frameborder="0"></iframe>', $html);
		$html = preg_replace('/(<img[^>]*[ ]+src=[\'\"]?)(https?\:[^\'\"\s]+)([\'\"]?)/', '$1$2$3', $html);
		return $html;
	}

	function encodeURI($str) {
		return str_replace(array('%3A', '%2F', '%23', '%28', '%29'), array(':', '/', '#', '(', ')'), rawurlencode($str));
	}
}

class HTMLElement {
	public $tagName, $innerHTML, $attributes;
	function __construct($tagname) {
		$this->tagName = $tagname;
		$this->innerHTML = null;
		$this->attributes = array();
		$this->style = array();
	}

	public function toString() {
		$style = $attr = '';
		if(!empty($this->style)) {
			foreach($this->style as $key => $value) {
				$value = str_replace('\\', '\\\\', $value);
				$value = str_replace('"', '\\"', $value);
				$style.=$key.':'.$value.';';
			}
			$this->attributes['style'] = substr($style, 0, -1);
		}
		if(!empty($this->attributes)) {
			foreach($this->attributes as $key => $value) {
				$value = str_replace('\\', '\\\\', $value);
				$value = str_replace('"', '\\"', $value);
				$attr.=' '.$key.'="'.$value.'"';
			}
		}
		return '<'.$this->tagName.$attr.'>'.$this->innerHTML.'</'.$this->tagName.'>';
	}
}